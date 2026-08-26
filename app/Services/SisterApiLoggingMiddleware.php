<?php

namespace App\Services;

use App\Models\SisterApiLog;
use GuzzleHttp\Promise\Create;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;

/**
 * Guzzle global middleware — logs every request/response sent to the SISTER API
 * into `sister_api_logs`, regardless of which service class made the call.
 *
 * Registered once via Http::globalMiddleware() in AppServiceProvider::boot().
 */
class SisterApiLoggingMiddleware
{
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            if (! $this->shouldLog($request)) {
                return $handler($request, $options);
            }

            $start = microtime(true);

            return $handler($request, $options)->then(
                function (ResponseInterface $response) use ($request, $start) {
                    $this->logResponse($request, $response, $start);

                    return $response;
                },
                function (mixed $reason) use ($request, $start) {
                    $this->logFailure($request, $reason, $start);

                    return Create::rejectionFor($reason);
                }
            );
        };
    }

    private function shouldLog(RequestInterface $request): bool
    {
        $sisterHost = parse_url((string) config('services.sister.host'), PHP_URL_HOST);

        return $sisterHost !== null && $sisterHost !== false
            && strcasecmp($request->getUri()->getHost(), $sisterHost) === 0;
    }

    private function logResponse(RequestInterface $request, ResponseInterface $response, float $start): void
    {
        SisterApiLog::create([
            'method' => $request->getMethod(),
            'url' => (string) $request->getUri(),
            'request_headers' => $this->redactHeaders($request->getHeaders()),
            'request_body' => $this->redactBody($this->readBody($request->getBody())),
            'response_status' => $response->getStatusCode(),
            'response_headers' => $this->redactHeaders($response->getHeaders()),
            'response_body' => $this->redactBody($this->readBody($response->getBody())),
            'duration_ms' => $this->elapsedMs($start),
        ]);
    }

    private function logFailure(RequestInterface $request, mixed $reason, float $start): void
    {
        SisterApiLog::create([
            'method' => $request->getMethod(),
            'url' => (string) $request->getUri(),
            'request_headers' => $this->redactHeaders($request->getHeaders()),
            'request_body' => $this->redactBody($this->readBody($request->getBody())),
            'duration_ms' => $this->elapsedMs($start),
            'error' => $reason instanceof Throwable ? $reason->getMessage() : (string) $reason,
        ]);
    }

    private function elapsedMs(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }

    /**
     * Read a PSR-7 stream fully for logging, then rewind it so downstream code
     * (Laravel's Response::body()/json(), or a retry resending the request)
     * can still read it from the start.
     */
    private function readBody(StreamInterface $body): string
    {
        $contents = (string) $body;

        if ($body->isSeekable()) {
            $body->rewind();
        }

        return $contents;
    }

    /**
     * @return array<string, string[]>
     */
    private function redactHeaders(array $headers): array
    {
        foreach ($headers as $name => $values) {
            if (strtolower($name) === 'authorization') {
                $headers[$name] = ['Bearer [REDACTED]'];
            }
        }

        return $headers;
    }

    /**
     * @return array<mixed>|null
     */
    private function redactBody(string $raw): ?array
    {
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['_raw' => $raw];
        }

        if (is_array($decoded)) {
            foreach (['password', 'token'] as $key) {
                if (array_key_exists($key, $decoded)) {
                    $decoded[$key] = '[REDACTED]';
                }
            }

            return $decoded;
        }

        return ['_raw' => $decoded];
    }
}

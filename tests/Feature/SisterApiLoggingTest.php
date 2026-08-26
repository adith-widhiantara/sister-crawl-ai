<?php

namespace Tests\Feature;

use App\Models\SisterApiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SisterApiLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.sister.host' => 'https://sister-api.example.test']);
    }

    public function test_logs_a_successful_request_and_response(): void
    {
        Http::fake([
            'sister-api.example.test/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $response = Http::baseUrl('https://sister-api.example.test')
            ->withToken('secret-token')
            ->get('/jabatan_fungsional', ['id_sdm' => 'abc']);

        $this->assertTrue($response->successful());
        // Middleware must not break the caller's ability to read the response normally.
        $this->assertSame(['status' => 'ok'], $response->json());

        $this->assertDatabaseCount('sister_api_logs', 1);

        $log = SisterApiLog::first();
        $this->assertSame('GET', $log->method);
        $this->assertStringContainsString('/jabatan_fungsional', $log->url);
        $this->assertSame(200, $log->response_status);
        $this->assertIsInt($log->duration_ms);
        $this->assertSame(['status' => 'ok'], $log->response_body);
        $this->assertSame(['Bearer [REDACTED]'], $log->request_headers['Authorization']);
    }

    public function test_redacts_password_and_token_on_the_authorize_call(): void
    {
        Http::fake([
            'sister-api.example.test/*' => Http::response(['token' => 'super-secret-token', 'role' => 'admin'], 200),
        ]);

        Http::baseUrl('https://sister-api.example.test')->post('/authorize', [
            'username' => 'someone',
            'password' => 'super-secret-password',
            'id_pengguna' => 'x',
        ]);

        $log = SisterApiLog::first();

        $this->assertSame('[REDACTED]', $log->request_body['password']);
        $this->assertSame('someone', $log->request_body['username']);
        $this->assertSame('[REDACTED]', $log->response_body['token']);
    }

    public function test_does_not_log_requests_to_other_hosts(): void
    {
        Http::fake([
            'other-service.example.test/*' => Http::response(['ok' => true], 200),
        ]);

        Http::get('https://other-service.example.test/ping');

        $this->assertDatabaseCount('sister_api_logs', 0);
    }
}

<?php

namespace App\Jobs;

use App\Models\CrawlItem;
use App\Services\SisterEndpointRegistry;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class CrawlEndpointJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    public function __construct(private int $crawlItemId) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $item = CrawlItem::find($this->crawlItemId);

        if (! $item) {
            return;
        }

        $item->update(['status' => 'processing']);

        try {
            $endpoint = SisterEndpointRegistry::get($item->endpoint);
            $service = app($endpoint['service']);
            $model = $endpoint['model'];

            $rows = $service->crawl($item->id_sdm);

            $fillable = (new $model)->getFillable();

            foreach ($rows as $row) {
                $model::updateOrCreate(
                    ['sister_id' => $row['id']],
                    [...array_intersect_key($row, array_flip($fillable)), 'id_sdm' => $item->id_sdm]
                );
            }

            $item->update(['status' => 'success', 'error' => null]);
        } catch (Throwable $e) {
            $item->update(['status' => 'failed', 'error' => $e->getMessage()]);

            throw $e; // let the batch count it as a failed job
        }
    }
}

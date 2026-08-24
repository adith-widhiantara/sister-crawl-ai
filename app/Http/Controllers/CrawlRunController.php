<?php

namespace App\Http\Controllers;

use App\Jobs\CrawlEndpointJob;
use App\Models\CrawlItem;
use App\Models\Sdm;
use App\Services\SisterEndpointRegistry;
use App\Services\SisterReferensiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CrawlRunController extends Controller
{
    public function index(): View
    {
        $runs = DB::table('job_batches')->orderByDesc('id')->limit(50)->get();

        return view('crawl-runs.index', [
            'runs' => $runs,
            'endpoints' => SisterEndpointRegistry::all(),
            'sdmCount' => Sdm::count(),
        ]);
    }

    /**
     * Fetch /referensi/sdm and upsert into the `sdms` master table.
     * Safe to run repeatedly — never creates duplicates, only refreshes data.
     */
    public function syncSdm(SisterReferensiService $referensi): RedirectResponse
    {
        $sdmList = $referensi->getSdm([]);

        $columns = ['id_sdm', 'nama_sdm', 'nidn', 'nip', 'nuptk', 'nama_status_aktif', 'nama_status_pegawai', 'jenis_sdm'];

        $rows = collect($sdmList)->map(fn ($sdm) => [
            ...array_intersect_key($sdm, array_flip($columns)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sdm::upsert(
            $rows->toArray(),
            uniqueBy: ['id_sdm'],
            update: ['nama_sdm', 'nidn', 'nip', 'nuptk', 'nama_status_aktif', 'nama_status_pegawai', 'jenis_sdm', 'updated_at']
        );

        return redirect()->route('crawl-runs.index')->with('status', "Synced {$rows->count()} SDM.");
    }

    public function start(Request $request): RedirectResponse
    {
        $endpoints = $request->validate([
            'endpoints' => 'required|array|min:1',
            'endpoints.*' => 'in:'.implode(',', SisterEndpointRegistry::keys()),
        ])['endpoints'];

        $sdms = Sdm::query()->select('id_sdm', 'nama_sdm')->get();

        if ($sdms->isEmpty()) {
            return redirect()->route('crawl-runs.index')->with('status', 'Belum ada data SDM. Klik "Sync SDM" dulu.');
        }

        $batchIds = array_map(fn ($endpoint) => $this->dispatchRun($endpoint, $sdms), $endpoints);

        return count($batchIds) === 1
            ? redirect()->route('crawl-runs.show', $batchIds[0])
            : redirect()->route('crawl-runs.index')->with('status', count($batchIds).' run dimulai.');
    }

    private function dispatchRun(string $endpoint, \Illuminate\Support\Collection $sdms): string
    {
        // ponytail: real batch id is only known after dispatch, so stage rows under a
        // throwaway token in the same batch_id column, then swap it for the real id.
        $stagingToken = (string) Str::uuid();

        $items = $sdms->map(fn ($sdm) => [
            'batch_id' => $stagingToken,
            'endpoint' => $endpoint,
            'id_sdm' => $sdm->id_sdm,
            'nama_sdm' => $sdm->nama_sdm,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        CrawlItem::insert($items->toArray());

        $itemIds = CrawlItem::where('batch_id', $stagingToken)->pluck('id');

        $label = SisterEndpointRegistry::get($endpoint)['label'];

        $batch = Bus::batch(
            $itemIds->map(fn ($id) => new CrawlEndpointJob($id))->all()
        )->name("Crawl: {$label}")->allowFailures()->dispatch();

        CrawlItem::where('batch_id', $stagingToken)->update(['batch_id' => $batch->id]);

        return $batch->id;
    }

    public function show(string $batchId): View
    {
        return view('crawl-runs.show', ['batchId' => $batchId]);
    }

    public function status(string $batchId): JsonResponse
    {
        $batch = Bus::findBatch($batchId);

        if (! $batch) {
            return response()->json(['message' => 'Batch not found'], 404);
        }

        $items = CrawlItem::where('batch_id', $batchId)->orderBy('id')->get();

        return response()->json([
            'name' => $batch->name,
            'total' => $batch->totalJobs,
            'pending' => $batch->pendingJobs,
            'failed' => $batch->failedJobs,
            'processed' => $batch->processedJobs(),
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
            'cancelled' => $batch->cancelled(),
            'items' => $items,
        ]);
    }
}

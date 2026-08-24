<?php

use App\Http\Controllers\AiSearchController;
use App\Http\Controllers\CrawlRunController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ai-search', [AiSearchController::class, 'index'])->name('ai-search.index');
Route::post('/ai-search/ask', [AiSearchController::class, 'askStream'])->name('ai-search.ask');

Route::get('/crawl-runs', [CrawlRunController::class, 'index'])->name('crawl-runs.index');
Route::post('/crawl-runs/sync-sdm', [CrawlRunController::class, 'syncSdm'])->name('crawl-runs.sync-sdm');
Route::post('/crawl-runs', [CrawlRunController::class, 'start'])->name('crawl-runs.start');
Route::get('/crawl-runs/{batchId}', [CrawlRunController::class, 'show'])->name('crawl-runs.show');
Route::get('/crawl-runs/{batchId}/status', [CrawlRunController::class, 'status'])->name('crawl-runs.status');

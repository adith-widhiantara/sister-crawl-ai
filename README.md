# SISTER API Crawler

Laravel app buat narik data pegawai (SDM) dari **SISTER Web Service** (Kemdiktisaintek) secara massal, disimpan ke database lokal. Terintegrasi ke API sandbox:

```
https://sister-api.kemdiktisaintek.go.id/ws-sandbox.php/1.0
```

## Cara kerja

1. **Sync SDM** — narik daftar seluruh pegawai dari `/referensi/sdm`, disimpan sebagai master data di tabel `sdms` (upsert by `id_sdm`, jadi aman dijalankan berkali-kali, gak bikin duplikat).
2. **Start Run** — pilih 1 atau lebih endpoint (checkbox), lalu tiap SDM di-queue jadi 1 job (`CrawlEndpointJob`) yang hit endpoint tersebut dengan `id_sdm` masing-masing. Job dieksekusi lewat Laravel [job batching](https://laravel.com/docs/queues#job-batching) supaya progress-nya bisa dipantau.
3. **Live tracking** — halaman detail run nge-poll status tiap 2 detik, nampilin progress bar + status per-SDM (pending/processing/success/failed), mirip halaman build Jenkins.
4. **Cari Data (AI)** — halaman `/ai-search` buat tanya data hasil crawl pakai bahasa natural (misal *"cari dosen dengan publikasi lebih dari 5"*), dijawab AI lewat tool-calling ke database, hasil tabel-nya dinamis sesuai pertanyaan.
5. **Log request/response SISTER API** — semua call ke SISTER API (auth, referensi/sdm, jabatan_fungsional, dst) otomatis kecatat ke tabel `sister_api_logs` (method, url, request/response header, request/response body, status, durasi) — kredensial (`password`, bearer token) di-redact sebelum disimpan. Berguna buat debug kalau ada crawl yang gagal atau data yang aneh, tanpa perlu ubah kode service manapun.

Endpoint SISTER yang sudah didukung (lihat [`SisterEndpointRegistry`](app/Services/SisterEndpointRegistry.php)):

| Endpoint | Model hasil |
|---|---|
| `/jabatan_fungsional` | `JabatanFungsional` |
| `/pendidikan_formal` | `PendidikanFormal` |
| `/publikasi` (paginated) | `Publikasi` |
| `/sertifikasi_dosen` | `SertifikasiDosen` |
| `/sertifikasi_profesi` | `SertifikasiProfesi` |

Nambah endpoint baru cukup: 1 service class (extend `SisterEndpointService`), 1 model+migration (kolom disamain nama field-nya dengan response API), lalu daftarkan di registry — job & controller-nya generik, gak perlu ditulis ulang.

## Arsitektur singkat

```
routes/api.php   → SisterAuthController, SisterReferensiController   (akses API SISTER langsung)
routes/web.php   → CrawlRunController                                (UI crawl + live tracking)
                 → AiSearchController                                 (UI cari data pakai AI)

app/Services/
  SisterAuthService          → POST /authorize, cache token 55 menit
  SisterEndpointService      → base class, HTTP GET + bearer token
  Sister*Service (x5)        → 1 per endpoint SISTER
  SisterEndpointRegistry     → mapping endpoint key → service + model
  SisterApiLoggingMiddleware → Guzzle global middleware, log semua request/response ke sister_api_logs

app/Jobs/CrawlEndpointJob.php → 1 job generik, dipakai semua endpoint lewat registry

app/Ai/
  Tools/SearchCrawledSdmData → tool read-only, whitelist kolom, join sdms + 5 tabel hasil crawl
  Agents/SisterDataAgent     → orkestrasi tool call, provider failover (gemini → openrouter)
```

## Setup

Sekali langkah, dari clone sampai siap dipakai (butuh Docker, gak perlu PHP/Composer lokal):

```bash
./setup.sh
```

Script ini idempotent (aman dijalankan ulang) — meng-install dependencies, generate `APP_KEY`, download binary FrankenPHP, jalankan container, migrasi database, dan build asset. Di akhir dia bakal bilang kalau ada kredensial yang masih kosong di `.env`:

```env
SISTER_API_HOST=https://sister-api.kemdiktisaintek.go.id/ws-sandbox.php/1.0
SISTER_API_USERNAME=...       # kredensial sandbox SISTER
SISTER_API_PASSWORD=...
SISTER_API_ID_PENGGUNA=...

GEMINI_API_KEY=...            # aistudio.google.com/apikey — buat fitur /ai-search
OPENROUTER_API_KEY=...        # openrouter.ai — opsional, fallback kalau Gemini overload
```

Setelah diisi, restart container aplikasi biar `.env` yang baru terbaca:

```bash
docker restart laravel-sister-api-crawler-data-laravel.test-1
```

Buka:
- `http://localhost:8082/crawl-runs` — klik **Sync SDM**, pilih endpoint, **Start Run**.
- `http://localhost:8082/ai-search` — tanya data pakai bahasa natural.

> Queue worker (job batching) jalan otomatis di container `queue` (`restart: unless-stopped`) — gak perlu dijalankan manual.
>
> Defaultnya jalan **4 worker paralel** (bukan 1 per 1), diatur lewat `QUEUE_WORKERS` di `.env`. Tiap worker adalah proses `queue:work` terpisah yang ambil job dari tabel `jobs` — aman dipakai bareng-bareng karena driver `database` mengunci (`reserved_at`) tiap job saat diambil, jadi gak ada 2 worker yang ngerjain SDM yang sama. Mau nambah/kurangin jumlah worker: ubah `QUEUE_WORKERS` di `.env` lalu `docker compose up -d --scale queue=$QUEUE_WORKERS queue` (atau langsung `sail up -d --scale queue=4`), atau restart container biar `deploy.replicas` di `compose.yaml` yang baca ulang env-nya.

Mau share instance lokal keluar (misal lewat [ngrok](https://ngrok.com/)) buat testing? Tinggal jalanin `ngrok http 8082` — app-nya udah trust reverse proxy header (`X-Forwarded-Proto`), jadi link yang di-generate otomatis ikut `https://` sesuai domain tunnel-nya, gak perlu ubah `APP_URL`.

## Stack

- **Laravel 13** + PHP 8.5, disajikan lewat **[Octane](https://laravel.com/docs/octane) + FrankenPHP** (bukan `artisan serve` default Sail) buat throughput lebih tinggi saat crawl ratusan/ribuan SDM.
- **PostgreSQL** (data), **Redis** (cache/queue driver tersedia, default queue pakai `database`).
- **Laravel job batching** buat tracking progress crawl, tanpa perlu WebSocket — UI cukup polling.
- **[Laravel AI SDK](https://laravel.com/docs/ai-sdk)** — agent + tool-calling buat halaman `/ai-search`. Provider utama **Gemini**, fallback otomatis ke **OpenRouter** (model gratis) kalau Gemini overload. Progress AI di-stream real-time ke halaman lewat SSE.
- **[Laravel Boost](https://laravel.com/docs/ai)** terpasang sebagai dev-tool: MCP server yang kasih AI coding agent (Claude Code) akses introspeksi DB/routes/tinker + dokumentasi Laravel versi-spesifik. Lihat `CLAUDE.md` & `.mcp.json`.

## Development

```bash
sail artisan tinker          # eksplorasi data
sail artisan queue:work      # kalau mau jalanin worker manual (di luar container queue)
sail artisan octane:start --server=frankenphp --watch   # dev dengan auto-reload
```

> ⚠️ **Penting soal Octane:** container `laravel.test` menjalankan Octane, yang nge-boot aplikasi sekali lalu nyimpennya di memory antar-request — **beda dari `artisan serve` biasa**. Setiap ubah kode PHP (job, service, controller) *maupun* file Blade, perubahan gak langsung kelihatan sampai worker di-restart:
>
> ```bash
> docker restart laravel-sister-api-crawler-data-laravel.test-1
> ```
>
> Kalau ubah kode yang dipakai job/queue, restart juga worker-nya: `sail artisan queue:restart`.

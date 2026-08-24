# SISTER API Crawler

Laravel app buat narik data pegawai (SDM) dari **SISTER Web Service** (Kemdiktisaintek) secara massal, disimpan ke database lokal. Terintegrasi ke API sandbox:

```
https://sister-api.kemdiktisaintek.go.id/ws-sandbox.php/1.0
```

## Cara kerja

1. **Sync SDM** — narik daftar seluruh pegawai dari `/referensi/sdm`, disimpan sebagai master data di tabel `sdms` (upsert by `id_sdm`, jadi aman dijalankan berkali-kali, gak bikin duplikat).
2. **Start Run** — pilih 1 atau lebih endpoint (checkbox), lalu tiap SDM di-queue jadi 1 job (`CrawlEndpointJob`) yang hit endpoint tersebut dengan `id_sdm` masing-masing. Job dieksekusi lewat Laravel [job batching](https://laravel.com/docs/queues#job-batching) supaya progress-nya bisa dipantau.
3. **Live tracking** — halaman detail run nge-poll status tiap 2 detik, nampilin progress bar + status per-SDM (pending/processing/success/failed), mirip halaman build Jenkins.

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

app/Services/
  SisterAuthService          → POST /authorize, cache token 55 menit
  SisterEndpointService      → base class, HTTP GET + bearer token
  Sister*Service (x5)        → 1 per endpoint SISTER
  SisterEndpointRegistry     → mapping endpoint key → service + model

app/Jobs/CrawlEndpointJob.php → 1 job generik, dipakai semua endpoint lewat registry
```

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Isi kredensial SISTER sandbox di `.env`:

```env
SISTER_API_HOST=https://sister-api.kemdiktisaintek.go.id/ws-sandbox.php/1.0
SISTER_API_USERNAME=...
SISTER_API_PASSWORD=...
SISTER_API_ID_PENGGUNA=...
```

Jalankan lewat [Sail](https://laravel.com/docs/sail):

```bash
sail up -d
sail artisan migrate
```

Buka `http://localhost:8082/crawl-runs`, klik **Sync SDM**, pilih endpoint, klik **Start Run**.

> Queue worker (job batching) jalan otomatis di container `queue` (`restart: unless-stopped`) — gak perlu dijalankan manual. Setiap ubah kode job/service, restart workernya: `sail artisan queue:restart`.

## Stack

- **Laravel 13** + PHP 8.5, disajikan lewat **[Octane](https://laravel.com/docs/octane) + FrankenPHP** (bukan `artisan serve` default Sail) buat throughput lebih tinggi saat crawl ratusan/ribuan SDM.
- **PostgreSQL** (data), **Redis** (cache/queue driver tersedia, default queue pakai `database`).
- **Laravel job batching** buat tracking progress crawl, tanpa perlu WebSocket — UI cukup polling.
- **[Laravel Boost](https://laravel.com/docs/ai)** terpasang sebagai dev-tool: MCP server yang kasih AI coding agent (Claude Code) akses introspeksi DB/routes/tinker + dokumentasi Laravel versi-spesifik. Lihat `CLAUDE.md` & `.mcp.json`.

## Development

```bash
sail artisan tinker          # eksplorasi data
sail artisan queue:work      # kalau mau jalanin worker manual (di luar container queue)
sail artisan octane:start --server=frankenphp --watch   # dev dengan auto-reload
```

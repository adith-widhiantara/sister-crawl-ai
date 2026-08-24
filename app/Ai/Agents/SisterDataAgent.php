<?php

namespace App\Ai\Agents;

use App\Ai\Tools\SearchCrawledSdmData;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class SisterDataAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    public function __construct(private readonly SearchCrawledSdmData $tool) {}

    /**
     * Provider failover order: try Gemini first, fall back to OpenRouter's free
     * model if Gemini is overloaded/unavailable (Laravel AI SDK retries the next
     * entry automatically on any FailoverableException, e.g. ProviderOverloadedException).
     *
     * @return array<string, string|null>
     */
    public function provider(): array
    {
        return [
            'gemini' => null,
            'openrouter' => null,
        ];
    }

    public function instructions(): Stringable|string
    {
        return <<<'TEXT'
            Kamu adalah asisten pencarian data dosen/tenaga kependidikan (SDM) dari hasil
            crawl SISTER (Kemdiktisaintek). Kamu HARUS memanggil tool yang tersedia untuk
            mengambil data apa pun — jangan pernah mengarang atau menjawab dari asumsi.

            Baca pertanyaan pengguna, tentukan kolom (select) dan kriteria filter yang
            paling relevan, lalu panggil tool tersebut sekali (atau lebih kalau perlu
            menyempurnakan filter). Setelah tool mengembalikan hasil, balas dengan ringkasan
            singkat 1-2 kalimat berbahasa Indonesia (misal jumlah hasil ditemukan) —
            JANGAN menyalin ulang isi tabel di jawabanmu, tabelnya akan ditampilkan
            terpisah oleh aplikasi.

            Kalau pertanyaan tidak berkaitan dengan data SDM, jawab singkat bahwa kamu
            hanya bisa membantu pencarian data SDM hasil crawl SISTER.
            TEXT;
    }

    /**
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [$this->tool];
    }
}

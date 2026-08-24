<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Read-only, whitelisted search over the SISTER crawl results (sdms + the 5 result
 * tables), joined by id_sdm. The AI can only select/filter by the columns below —
 * no raw SQL, so it can't touch anything outside this shape.
 */
class SearchCrawledSdmData implements Tool
{
    /**
     * The exact data returned to the caller, kept here so the controller can render
     * the table from the real query result instead of asking the model to repeat it.
     */
    public ?array $lastResult = null;

    /**
     * The raw select/filters/limit arguments the AI called this tool with, kept for logging.
     */
    public ?array $lastArguments = null;

    private const COLUMNS = [
        'nama_sdm', 'nidn', 'nip', 'nuptk', 'jenis_sdm', 'status_aktif', 'status_pegawai',
        'jumlah_publikasi', 'jumlah_jabatan_fungsional', 'jumlah_pendidikan_formal',
        'jumlah_sertifikasi_dosen', 'jumlah_sertifikasi_profesi',
        'jabatan_fungsional_terakhir', 'jenjang_pendidikan_terakhir', 'gelar_akademik_terakhir',
    ];

    private const OPERATORS = ['=', '!=', '>', '>=', '<', '<=', 'like'];

    public function description(): Stringable|string
    {
        return 'Cari data dosen/tenaga kependidikan (SDM) dari hasil crawl SISTER. Read-only. '
            .'Kolom yang bisa dipilih (select) atau difilter (filters): '.implode(', ', self::COLUMNS).'. '
            .'Kolom berawalan jumlah_ adalah hitungan riwayat (jabatan fungsional, pendidikan, publikasi, sertifikasi) '
            .'per orang — pakai ini untuk pertanyaan seperti "publikasi lebih dari 5".';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->lastArguments = $request->all();

        $select = array_values(array_intersect($request->array('select', []), self::COLUMNS));

        if ($select === []) {
            $select = ['nama_sdm', 'nidn'];
        } elseif (! in_array('nama_sdm', $select, true)) {
            array_unshift($select, 'nama_sdm');
        }

        $filters = collect($request->array('filters', []))
            ->filter(fn ($f) => in_array($f['field'] ?? null, self::COLUMNS, true)
                && in_array($f['operator'] ?? null, self::OPERATORS, true)
                && array_key_exists('value', $f));

        $limit = min(max((int) $request->integer('limit', 50), 1), 200);

        $query = $this->baseQuery();

        foreach ($filters as $filter) {
            $column = $filter['field'];
            $isLike = $filter['operator'] === 'like';
            $value = str_starts_with($column, 'jumlah_') && ! $isLike ? (float) $filter['value'] : $filter['value'];

            $query->where($column, $isLike ? 'ilike' : $filter['operator'], $isLike ? "%{$value}%" : $value);
        }

        $rows = $query->limit($limit)->get()
            ->map(fn ($row) => collect((array) $row)->only($select)->all())
            ->values()
            ->all();

        $this->lastResult = [
            'columns' => $select,
            'rows' => $rows,
            'count' => count($rows),
        ];

        return json_encode($this->lastResult, JSON_UNESCAPED_UNICODE) ?: '{"columns":[],"rows":[],"count":0}';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'select' => $schema->array()
                ->items($schema->string()->enum(self::COLUMNS))
                ->description('Kolom yang ditampilkan di hasil. Kosongkan untuk default (nama_sdm, nidn).'),
            'filters' => $schema->array()
                ->items($schema->object([
                    'field' => $schema->string()->enum(self::COLUMNS)->required(),
                    'operator' => $schema->string()->enum(self::OPERATORS)->required(),
                    'value' => $schema->string()->required()
                        ->description('Nilai pembanding, tulis sebagai string meski kolomnya angka.'),
                ])->withoutAdditionalProperties())
                ->description('Kriteria filter, semua digabung dengan AND.'),
            'limit' => $schema->integer()->min(1)->max(200)->default(50)
                ->description('Maksimal jumlah baris hasil.'),
        ];
    }

    private function baseQuery(): \Illuminate\Database\Query\Builder
    {
        $countOf = fn (string $table) => DB::table($table)
            ->selectRaw('count(*)')
            ->whereColumn("{$table}.id_sdm", 'sdms.id_sdm');

        $latestOf = fn (string $table, string $column, string $orderBy) => DB::table($table)
            ->select($column)
            ->whereColumn("{$table}.id_sdm", 'sdms.id_sdm')
            ->orderByDesc($orderBy)
            ->limit(1);

        $inner = DB::table('sdms')->select([
            'sdms.nama_sdm', 'sdms.nidn', 'sdms.nip', 'sdms.nuptk', 'sdms.jenis_sdm',
            'sdms.nama_status_aktif as status_aktif',
            'sdms.nama_status_pegawai as status_pegawai',
        ])
            ->selectSub($countOf('publikasis'), 'jumlah_publikasi')
            ->selectSub($countOf('jabatan_fungsionals'), 'jumlah_jabatan_fungsional')
            ->selectSub($countOf('pendidikan_formals'), 'jumlah_pendidikan_formal')
            ->selectSub($countOf('sertifikasi_dosens'), 'jumlah_sertifikasi_dosen')
            ->selectSub($countOf('sertifikasi_profesis'), 'jumlah_sertifikasi_profesi')
            ->selectSub($latestOf('jabatan_fungsionals', 'jabatan_fungsional', 'tanggal_mulai'), 'jabatan_fungsional_terakhir')
            ->selectSub($latestOf('pendidikan_formals', 'jenjang_pendidikan', 'tahun_lulus'), 'jenjang_pendidikan_terakhir')
            ->selectSub($latestOf('pendidikan_formals', 'gelar_akademik', 'tahun_lulus'), 'gelar_akademik_terakhir');

        return DB::query()->fromSub($inner, 'sdm_view');
    }
}

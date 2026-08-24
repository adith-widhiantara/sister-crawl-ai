<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Crawl Runs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-2xl font-bold">Crawl Runs — SISTER</h1>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500">{{ $sdmCount }} SDM tersinkron</span>
                <a href="{{ route('ai-search.index') }}" class="text-sm text-blue-600">Cari Data (AI) &rarr;</a>
            </div>
        </div>

        @if (session('status'))
            <div class="bg-blue-50 text-blue-700 text-sm rounded p-3 mb-4">{{ session('status') }}</div>
        @endif

        <div class="flex items-center gap-3 mb-6">
            <form method="POST" action="{{ route('crawl-runs.sync-sdm') }}">
                @csrf
                <button class="bg-gray-700 text-white px-4 py-2 rounded font-medium hover:bg-gray-800">
                    ⟳ Sync SDM
                </button>
            </form>

            <form method="POST" action="{{ route('crawl-runs.start') }}" class="flex items-center gap-3 bg-white border rounded px-3 py-2" id="start-run-form">
                @csrf
                <label class="text-sm text-blue-600 cursor-pointer underline">
                    <input type="checkbox" id="select-all" class="hidden">Semua
                </label>
                @foreach ($endpoints as $key => $endpoint)
                    <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                        <input type="checkbox" name="endpoints[]" value="{{ $key }}" class="endpoint-checkbox rounded">
                        {{ $endpoint['label'] }}
                    </label>
                @endforeach
                <button class="bg-green-600 text-white px-4 py-2 rounded font-medium hover:bg-green-700 whitespace-nowrap">
                    ▶ Start Run
                </button>
            </form>

            <script>
                document.getElementById('select-all').addEventListener('change', (e) => {
                    document.querySelectorAll('#start-run-form .endpoint-checkbox').forEach(cb => cb.checked = e.target.checked);
                });
            </script>
        </div>

        <div class="bg-white rounded shadow divide-y">
            @forelse ($runs as $run)
                @php
                    $total = $run->total_jobs;
                    $failed = $run->failed_jobs;
                    $processed = $total - $run->pending_jobs;
                    $finished = $run->finished_at !== null;
                    $status = $finished ? ($failed > 0 ? 'failed' : 'success') : 'running';
                    $color = ['failed' => 'bg-red-500', 'success' => 'bg-green-500', 'running' => 'bg-blue-500 animate-pulse'][$status];
                @endphp
                <a href="{{ route('crawl-runs.show', $run->id) }}" class="flex items-center gap-4 p-4 hover:bg-gray-50">
                    <span class="w-3 h-3 rounded-full {{ $color }}"></span>
                    <span class="flex-1">
                        <span class="font-medium">{{ $run->name }}</span>
                        <span class="text-xs text-gray-400 font-mono block">{{ $run->id }}</span>
                    </span>
                    <span class="text-sm text-gray-600">{{ $processed }}/{{ $total }} processed</span>
                    @if ($failed > 0)
                        <span class="text-sm text-red-600 font-medium">{{ $failed }} failed</span>
                    @endif
                    <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($run->created_at)->diffForHumans() }}</span>
                </a>
            @empty
                <p class="p-6 text-gray-500">Belum ada run. Sync SDM dulu, lalu klik "Start New Run".</p>
            @endforelse
        </div>
    </div>
</body>
</html>

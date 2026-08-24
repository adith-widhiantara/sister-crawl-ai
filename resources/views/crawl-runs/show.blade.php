<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Crawl Run {{ $batchId }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <a href="{{ route('crawl-runs.index') }}" class="text-sm text-blue-600">&larr; Semua run</a>

        <div class="flex items-center justify-between mt-2 mb-4">
            <h1 class="text-xl font-bold font-mono">{{ $batchId }}</h1>
            <span id="run-status" class="px-3 py-1 rounded text-sm font-medium text-white bg-blue-500">running</span>
        </div>

        <div class="bg-white rounded shadow p-4 mb-4">
            <div class="flex justify-between text-sm text-gray-600 mb-1">
                <span id="progress-label">0 / 0 processed</span>
                <span id="progress-pct">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded h-3 overflow-hidden">
                <div id="progress-bar" class="bg-blue-500 h-3 transition-all" style="width:0%"></div>
            </div>
            <p class="text-sm text-red-600 mt-2" id="failed-label"></p>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500">
                    <tr>
                        <th class="p-3">Status</th>
                        <th class="p-3">ID SDM</th>
                        <th class="p-3">Nama</th>
                        <th class="p-3">Error</th>
                    </tr>
                </thead>
                <tbody id="items-body"></tbody>
            </table>
        </div>
    </div>

    <script>
        const badge = {
            pending: 'bg-gray-200 text-gray-600',
            processing: 'bg-blue-100 text-blue-700 animate-pulse',
            success: 'bg-green-100 text-green-700',
            failed: 'bg-red-100 text-red-700',
        };

        async function poll() {
            const res = await fetch('{{ route('crawl-runs.status', $batchId) }}');
            const data = await res.json();

            document.getElementById('progress-label').textContent = `${data.processed} / ${data.total} processed`;
            document.getElementById('progress-pct').textContent = `${data.progress}%`;
            document.getElementById('progress-bar').style.width = `${data.progress}%`;
            document.getElementById('failed-label').textContent = data.failed > 0 ? `${data.failed} failed` : '';

            const statusEl = document.getElementById('run-status');
            if (data.cancelled) {
                statusEl.textContent = 'cancelled';
                statusEl.className = 'px-3 py-1 rounded text-sm font-medium text-white bg-gray-500';
            } else if (data.finished) {
                const ok = data.failed === 0;
                statusEl.textContent = ok ? 'success' : 'finished with failures';
                statusEl.className = `px-3 py-1 rounded text-sm font-medium text-white ${ok ? 'bg-green-500' : 'bg-red-500'}`;
            }

            document.getElementById('items-body').innerHTML = data.items.map(item => `
                <tr class="border-t">
                    <td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-medium ${badge[item.status]}">${item.status}</span></td>
                    <td class="p-3 font-mono text-xs">${item.id_sdm}</td>
                    <td class="p-3">${item.nama_sdm ?? '-'}</td>
                    <td class="p-3 text-red-600 text-xs">${item.error ?? ''}</td>
                </tr>
            `).join('');

            if (!data.finished && !data.cancelled) {
                setTimeout(poll, 2000);
            }
        }

        poll();
    </script>
</body>
</html>

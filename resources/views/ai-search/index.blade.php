<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cari Data SDM — AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold">Cari Data SDM (AI)</h1>
            <a href="{{ route('crawl-runs.index') }}" class="text-sm text-blue-600">Crawl Runs &rarr;</a>
        </div>

        <p class="text-sm text-gray-500 mb-4">
            Tulis pertanyaan bebas, misal: <em>"cari dosen dengan publikasi lebih dari 5"</em> atau
            <em>"dosen S3 di bidang teknik"</em>. AI akan menyusun kolom &amp; filter yang sesuai dari data hasil crawl.
        </p>

        <form id="ask-form" class="bg-white rounded shadow p-4 mb-4">
            <textarea
                id="question"
                name="question"
                rows="3"
                class="w-full border rounded p-3 text-sm"
                placeholder="Contoh: cari dosen dengan jumlah publikasi lebih dari 5"
            ></textarea>
            <div class="flex justify-end mt-2">
                <button type="submit" id="submit-btn" class="bg-blue-600 text-white px-4 py-2 rounded font-medium hover:bg-blue-700">
                    Tanya AI
                </button>
            </div>
        </form>

        <div id="status" class="hidden text-sm text-blue-600 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 bg-blue-600 rounded-full animate-pulse"></span>
            <span id="status-text">AI sedang berpikir…</span>
        </div>

        <div id="error" class="hidden bg-red-50 text-red-700 text-sm rounded p-3 mb-4"></div>

        <div id="summary" class="hidden bg-blue-50 text-blue-800 text-sm rounded p-3 mb-4"></div>

        <div id="result-wrap" class="hidden bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500">
                    <tr id="table-head"></tr>
                </thead>
                <tbody id="table-body"></tbody>
            </table>
        </div>
    </div>

    <script>
        const form = document.getElementById('ask-form');
        const statusEl = document.getElementById('status');
        const statusText = document.getElementById('status-text');
        const errorEl = document.getElementById('error');
        const summaryEl = document.getElementById('summary');
        const resultWrap = document.getElementById('result-wrap');
        const tableHead = document.getElementById('table-head');
        const tableBody = document.getElementById('table-body');
        const submitBtn = document.getElementById('submit-btn');

        function humanize(column) {
            return column.replaceAll('_', ' ');
        }

        function renderResult(data) {
            if (data.summary) {
                summaryEl.textContent = data.summary;
                summaryEl.classList.remove('hidden');
            }

            const columns = data.columns || [];
            const rows = data.rows || [];

            tableHead.innerHTML = columns.map(c => `<th class="p-3 capitalize">${humanize(c)}</th>`).join('');

            tableBody.innerHTML = rows.length
                ? rows.map(row => `
                    <tr class="border-t">
                        ${columns.map(c => `<td class="p-3">${row[c] ?? '-'}</td>`).join('')}
                    </tr>
                `).join('')
                : `<tr><td class="p-3 text-gray-500" colspan="${columns.length || 1}">Tidak ada hasil.</td></tr>`;

            resultWrap.classList.remove('hidden');
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const question = document.getElementById('question').value.trim();
            if (!question) return;

            errorEl.classList.add('hidden');
            summaryEl.classList.add('hidden');
            resultWrap.classList.add('hidden');
            statusEl.classList.remove('hidden');
            statusText.textContent = 'Mengirim pertanyaan ke AI…';
            submitBtn.disabled = true;

            try {
                const res = await fetch('{{ route('ai-search.ask') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'text/event-stream',
                    },
                    body: JSON.stringify({ question }),
                });

                if (!res.ok || !res.body) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.error || data.message || 'Terjadi kesalahan.');
                }

                const reader = res.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const chunks = buffer.split('\n\n');
                    buffer = chunks.pop(); // keep any incomplete trailing chunk for next read

                    for (const chunk of chunks) {
                        if (!chunk.startsWith('data: ')) continue;

                        const event = JSON.parse(chunk.slice(6));

                        if (event.type === 'status') {
                            statusText.textContent = event.message;
                        } else if (event.type === 'error') {
                            throw new Error(event.message);
                        } else if (event.type === 'done') {
                            renderResult(event);
                        }
                    }
                }
            } catch (err) {
                errorEl.textContent = err.message;
                errorEl.classList.remove('hidden');
            } finally {
                statusEl.classList.add('hidden');
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>

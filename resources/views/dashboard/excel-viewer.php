<section class="excel-viewer-page" data-excel-viewer data-file-url="<?= e($fileUrl) ?>">
    <header class="excel-viewer-bar">
        <a class="button ghost" href="<?= e($backUrl) ?>">戻る</a>
        <div class="excel-viewer-title">
            <span><?= e((string) ($grid['title'] ?? 'Excelビューア')) ?></span>
            <strong><?= e($fileName) ?></strong>
        </div>
        <label class="excel-viewer-sheet">
            <span>シート</span>
            <select data-excel-sheet-select aria-label="シート選択"></select>
        </label>
        <a class="button primary" href="<?= e($fileUrl) ?>" download>ダウンロード</a>
    </header>

    <div class="excel-viewer-meta">
        <div class="excel-viewer-status" data-excel-status>読み込み中...</div>
        <button class="button ghost" type="button" data-excel-load-more hidden>さらに表示</button>
    </div>
    <div class="excel-viewer-table-wrap" data-excel-table-wrap></div>
</section>

<script src="<?= e(asset_url('assets/js/xlsx.full.min.js')) ?>"></script>
<script>
(() => {
    const viewer = document.querySelector('[data-excel-viewer]');
    const sheetSelect = document.querySelector('[data-excel-sheet-select]');
    const status = document.querySelector('[data-excel-status]');
    const tableWrap = document.querySelector('[data-excel-table-wrap]');
    const loadMoreButton = document.querySelector('[data-excel-load-more]');
    let workbook = null;
    let activeRows = [];
    let visibleRowCount = 300;
    const pageSize = 300;

    if (!viewer || !sheetSelect || !status || !tableWrap || !loadMoreButton) {
        return;
    }

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const resizeTable = () => {
        const rect = tableWrap.getBoundingClientRect();
        const bottomPadding = window.matchMedia('(max-width: 700px)').matches ? 12 : 24;
        const minHeight = window.matchMedia('(max-width: 700px)').matches ? 300 : 360;
        const nextHeight = Math.max(minHeight, window.innerHeight - rect.top - bottomPadding);
        tableWrap.style.height = `${nextHeight}px`;
    };

    const renderRows = () => {
        if (activeRows.length === 0) {
            status.textContent = '表示できるデータがありません。';
            tableWrap.innerHTML = '';
            loadMoreButton.hidden = true;
            return;
        }

        const visibleRows = activeRows.slice(0, visibleRowCount);
        const columnCount = visibleRows.reduce((max, row) => Math.max(max, row.length), 0);
        const htmlRows = visibleRows.map((row, rowIndex) => {
            const cells = [];
            for (let columnIndex = 0; columnIndex < columnCount; columnIndex += 1) {
                const tag = rowIndex === 0 ? 'th' : 'td';
                cells.push(`<${tag}>${escapeHtml(row[columnIndex] ?? '')}</${tag}>`);
            }
            return `<tr>${cells.join('')}</tr>`;
        });

        const shown = Math.min(visibleRowCount, activeRows.length);
        status.textContent = activeRows.length > shown
            ? `${activeRows.length}行中 ${shown}行を表示しています。`
            : `${activeRows.length}行を表示しています。`;
        tableWrap.innerHTML = `<table class="excel-viewer-table"><tbody>${htmlRows.join('')}</tbody></table>`;
        loadMoreButton.hidden = shown >= activeRows.length;
        resizeTable();
    };

    const renderSheet = (sheetName) => {
        if (!workbook || !workbook.Sheets[sheetName]) {
            return;
        }

        activeRows = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], {
            header: 1,
            blankrows: false,
            defval: ''
        });
        visibleRowCount = pageSize;
        renderRows();
    };

    if (typeof XLSX === 'undefined') {
        status.textContent = 'Excelビューア機能を読み込めませんでした。ダウンロードして確認してください。';
        return;
    }

    fetch(viewer.dataset.fileUrl, { credentials: 'same-origin' })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Excel file could not be loaded.');
            }
            return response.arrayBuffer();
        })
        .then((buffer) => {
            workbook = XLSX.read(buffer, { type: 'array' });
            sheetSelect.innerHTML = '';
            workbook.SheetNames.forEach((sheetName) => {
                const option = document.createElement('option');
                option.value = sheetName;
                option.textContent = sheetName;
                sheetSelect.appendChild(option);
            });
            renderSheet(workbook.SheetNames[0]);
        })
        .catch(() => {
            status.textContent = 'Excelファイルを表示できませんでした。ダウンロードして確認してください。';
        });

    sheetSelect.addEventListener('change', (event) => {
        renderSheet(event.target.value);
    });

    loadMoreButton.addEventListener('click', () => {
        visibleRowCount += pageSize;
        renderRows();
    });

    window.addEventListener('resize', resizeTable);
    requestAnimationFrame(resizeTable);
})();
</script>

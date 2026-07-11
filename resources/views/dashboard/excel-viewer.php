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

    <div class="excel-viewer-status" data-excel-status>読み込み中...</div>
    <div class="excel-viewer-table-wrap" data-excel-table-wrap></div>
</section>

<script src="<?= e(asset_url('assets/js/xlsx.full.min.js')) ?>"></script>
<script>
(() => {
    const viewer = document.querySelector('[data-excel-viewer]');
    const sheetSelect = document.querySelector('[data-excel-sheet-select]');
    const status = document.querySelector('[data-excel-status]');
    const tableWrap = document.querySelector('[data-excel-table-wrap]');
    let workbook = null;

    if (!viewer || !sheetSelect || !status || !tableWrap) {
        return;
    }

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const renderSheet = (sheetName) => {
        if (!workbook || !workbook.Sheets[sheetName]) {
            return;
        }

        const rows = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], {
            header: 1,
            blankrows: false,
            defval: ''
        });

        if (rows.length === 0) {
            status.textContent = '表示できるデータがありません。';
            tableWrap.innerHTML = '';
            return;
        }

        const limitedRows = rows.slice(0, 300);
        const columnCount = limitedRows.reduce((max, row) => Math.max(max, row.length), 0);
        const htmlRows = limitedRows.map((row, rowIndex) => {
            const cells = [];
            for (let columnIndex = 0; columnIndex < columnCount; columnIndex += 1) {
                const tag = rowIndex === 0 ? 'th' : 'td';
                cells.push(`<${tag}>${escapeHtml(row[columnIndex] ?? '')}</${tag}>`);
            }
            return `<tr>${cells.join('')}</tr>`;
        });

        status.textContent = rows.length > limitedRows.length
            ? `先頭${limitedRows.length}行を表示しています。`
            : '';
        tableWrap.innerHTML = `<table class="excel-viewer-table"><tbody>${htmlRows.join('')}</tbody></table>`;
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
})();
</script>

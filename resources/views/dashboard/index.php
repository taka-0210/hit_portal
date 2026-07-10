<?php
$newEntryDays = max(0, (int) ($portalSettings['new_entry_days'] ?? 5));
$isNewEntry = static function (array $entry) use ($newEntryDays): bool {
    if ($newEntryDays <= 0 || empty($entry['created_at'])) {
        return false;
    }

    $createdAt = strtotime((string) $entry['created_at']);
    if ($createdAt === false) {
        return false;
    }

    return $createdAt >= strtotime('-' . $newEntryDays . ' days');
};
?>
<?php if (($portalSettings['hero_message'] ?? '') !== ''): ?>
    <section class="portal-hero">
        <p class="portal-message"><?= e($portalSettings['hero_message']) ?></p>
    </section>
<?php endif; ?>

<?php
$portalAreas = $gridAreas ?? ['common' => $gridColumns];
$portalAreaTitles = [
    'common' => '共通グリッド',
    'company' => '会社共通グリッド',
    'store_shared' => '店舗共通グリッド',
    'store' => '店舗専用グリッド',
];
?>
<?php foreach ($portalAreas as $areaKey => $areaColumns): ?>
    <?php if (array_sum(array_map('count', $areaColumns)) === 0) { continue; } ?>
    <section class="portal-area">
        <?php if (isset($gridAreas)): ?>
            <div class="portal-area-heading">
                <h2><?= e($portalAreaTitles[$areaKey] ?? '') ?></h2>
            </div>
        <?php endif; ?>
        <div class="portal-board">
    <?php foreach ($areaColumns as $column): ?>
        <div class="portal-column">
            <?php foreach ($column as $grid): ?>
                <?php
                $isCollapsed = ($grid['expand_type'] ?? 'open') === 'collapsed';
                $bodyId = 'portal-grid-body-' . (int) ($grid['id'] ?? 0);
                $canPostToGrid = $user !== null && (($grid['scope_type'] ?? 'all') !== 'all' || ($grid['post_permission'] ?? 'allowed') !== 'denied');
                ?>
                <article class="portal-section section-<?= e($grid['tone'] ?? 'green') ?> <?= $isCollapsed ? 'is-collapsible is-collapsed' : '' ?>">
                    <?php $entryDialogId = 'portal-entry-dialog-' . (int) ($grid['id'] ?? 0); ?>
                    <h2>
                        <?php if ($isCollapsed): ?>
                            <button class="portal-expand-button" type="button" data-toggle-grid="<?= e($bodyId) ?>" aria-expanded="false" aria-controls="<?= e($bodyId) ?>">▼</button>
                        <?php endif; ?>
                        <span><?= e($grid['title'] ?? '') ?></span>
                        <?php if ($canPostToGrid): ?>
                            <button class="portal-add-button" type="button" data-open-dialog="<?= e($entryDialogId) ?>" aria-label="<?= e($grid['title'] ?? 'グリッド') ?>へ投稿">＋</button>
                        <?php endif; ?>
                    </h2>
                    <div class="portal-section-body" id="<?= e($bodyId) ?>" <?= $isCollapsed ? 'hidden' : '' ?>>

                    <?php if (($grid['registration_type'] ?? '') === 'todo'): ?>
                        <?php
                        $todoEntries = [];
                        foreach (($grid['groups'] ?? []) as $group) {
                            foreach (($group['entries'] ?? []) as $entry) {
                                $todoEntries[] = $entry;
                            }
                        }
                        $progressLabels = [
                            'not_started' => '未着手',
                            'in_progress' => '進行中',
                            'done' => '完了',
                        ];
                        ?>
                        <div class="portal-todo-list">
                            <?php foreach ($todoEntries as $todoIndex => $todoEntry): ?>
                                <?php
                                $progress = (string) ($todoEntry['progress'] ?? 'not_started');
                                $todoImageUrl = isset($todoEntry['file_id']) ? route_url('grid.file', ['grid_id' => (int) $grid['id'], 'file_id' => $todoEntry['file_id']]) : '';
                                $imageDialogId = 'portal-todo-image-dialog-' . (int) ($grid['id'] ?? 0) . '-' . (int) $todoIndex;
                                ?>
                                <div class="portal-todo-card">
                                    <div class="portal-todo-status-cell">
                                    <form class="portal-todo-progress-form" method="post" action="<?= route_url('grid.todoProgress') ?>">
                                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="grid_id" value="<?= (int) $grid['id'] ?>">
                                        <input type="hidden" name="todo_index" value="<?= (int) $todoIndex ?>">
                                        <select class="portal-todo-progress is-<?= e($progress) ?>" name="progress" aria-label="進捗" data-current-progress="<?= e($progress) ?>">
                                            <?php foreach ($progressLabels as $key => $label): ?>
                                                <option value="<?= e($key) ?>" <?= $progress === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                    <?php if ($isNewEntry($todoEntry)): ?>
                                        <span class="portal-new-badge">NEW</span>
                                    <?php endif; ?>
                                    </div>
                                    <?php if (($todoEntry['label'] ?? '') !== ''): ?>
                                        <strong><?= e($todoEntry['label']) ?></strong>
                                    <?php endif; ?>
                                    <?php if (($todoEntry['description'] ?? '') !== ''): ?>
                                        <p><?= nl2br(e($todoEntry['description'])) ?></p>
                                    <?php endif; ?>
                                    <?php if ($todoImageUrl !== ''): ?>
                                        <button class="portal-todo-image-button portal-todo-image-icon" type="button" data-open-dialog="<?= e($imageDialogId) ?>" aria-label="Image preview"></button>
                                        <dialog class="portal-modal portal-image-modal" id="<?= e($imageDialogId) ?>">
                                            <div class="portal-modal-panel">
                                                <div class="portal-modal-heading section-<?= e($grid['tone'] ?? 'green') ?>">
                                                    <h3><?= e($todoEntry['label'] ?? $grid['title'] ?? '') ?></h3>
                                                    <button type="button" aria-label="閉じる" data-close-dialog>×</button>
                                                </div>
                                                <div class="portal-modal-body">
                                                    <img class="portal-expanded-image portal-photo-image" src="<?= e($todoImageUrl) ?>" alt="">
                                                </div>
                                            </div>
                                        </dialog>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (($grid['registration_type'] ?? '') === 'manual'): ?>
                        <?php
                        $manualItems = [];
                        foreach (($grid['groups'] ?? []) as $group) {
                            foreach (($group['entries'] ?? []) as $entry) {
                                $label = (string) ($entry['label'] ?? '');
                                if ($label === '') {
                                    continue;
                                }

                                $manualItems[] = [
                                    'group' => (string) ($group['label'] ?? ''),
                                    'label' => $label,
                                    'is_new' => $isNewEntry($entry),
                                ];
                            }
                        }

                        $hasLongItem = false;
                        foreach ($manualItems as $item) {
                            $length = function_exists('mb_strlen') ? mb_strlen($item['label']) : strlen($item['label']);
                            if ($length > 56) {
                                $hasLongItem = true;
                                break;
                            }
                        }

                        $needsModal = count($manualItems) >= 3 || $hasLongItem;
                        $previewItems = $needsModal ? array_slice($manualItems, 0, 2) : $manualItems;
                        $dialogId = 'portal-manual-dialog-' . (int) ($grid['id'] ?? 0);
                        ?>
                        <div class="portal-manual-list">
                            <?php foreach ($previewItems as $item): ?>
                                <div class="portal-manual-row">
                                    <?php if ($item['group'] !== ''): ?>
                                        <small><?= e($item['group']) ?></small>
                                    <?php endif; ?>
                                    <span><?= e($item['label']) ?></span>
                                    <?php if (!empty($item['is_new'])): ?>
                                        <span class="portal-new-badge">NEW</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($needsModal): ?>
                                <button class="portal-more-button" type="button" data-open-dialog="<?= e($dialogId) ?>">すべて表示</button>
                            <?php endif; ?>
                        </div>

                        <?php if ($needsModal): ?>
                            <dialog class="portal-modal" id="<?= e($dialogId) ?>">
                                <div class="portal-modal-panel">
                                    <div class="portal-modal-heading section-<?= e($grid['tone'] ?? 'green') ?>">
                                        <h3><?= e($grid['title'] ?? '') ?></h3>
                                        <button type="button" aria-label="閉じる" data-close-dialog>×</button>
                                    </div>
                                    <div class="portal-modal-body">
                                        <?php foreach ($manualItems as $item): ?>
                                            <div class="portal-manual-row">
                                                <?php if ($item['group'] !== ''): ?>
                                                    <small><?= e($item['group']) ?></small>
                                                <?php endif; ?>
                                                <span><?= e($item['label']) ?></span>
                                                <?php if (!empty($item['is_new'])): ?>
                                                    <span class="portal-new-badge">NEW</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </dialog>
                        <?php endif; ?>
                    <?php elseif (($grid['display_type'] ?? 'list') === 'grouped'): ?>
                        <div class="portal-groups">
                            <?php foreach (($grid['groups'] ?? []) as $group): ?>
                                <div class="portal-group-card">
                                    <?php if (($group['label'] ?? '') !== ''): ?>
                                        <h3><?= e($group['label']) ?></h3>
                                    <?php endif; ?>
                                    <div class="portal-link-grid">
                                        <?php foreach (($group['entries'] ?? []) as $entry): ?>
                                            <?php
                                            $qrCodeId = (int) ($entry['qr_code_id'] ?? 0);
                                            $entryUrl = isset($entry['file_id']) ? route_url('grid.file', ['grid_id' => (int) $grid['id'], 'file_id' => $entry['file_id']]) : (($qrCodeUrlMap[$qrCodeId] ?? null) ?: ($entry['url'] ?? '#'));
                                            $isQrEntry = !isset($entry['file_id']) && ($qrCodeId > 0 && !empty($qrCodeUrlMap[$qrCodeId]) || strpos((string) $entryUrl, '/uploads/qr-codes/') !== false);
                                            ?>
                                            <a class="portal-link-card <?= isset($entry['file_id']) ? 'is-file' : ($isQrEntry ? 'is-qr' : 'is-link') ?>" href="<?= e($entryUrl) ?>" <?= $isQrEntry ? 'data-qr-image-url="' . e($entryUrl) . '" data-qr-title="' . e($entry['label'] ?? '') . '" data-qr-tone="' . e($grid['tone'] ?? 'green') . '"' : '' ?>>
                                                <span><?= e($entry['label'] ?? '') ?></span>
                                                <?php if ($isNewEntry($entry)): ?>
                                                    <span class="portal-new-badge">NEW</span>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="portal-link-list">
                            <?php foreach (($grid['groups'] ?? []) as $group): ?>
                                <?php foreach (($group['entries'] ?? []) as $entry): ?>
                                    <?php
                                    $qrCodeId = (int) ($entry['qr_code_id'] ?? 0);
                                    $entryUrl = isset($entry['file_id']) ? route_url('grid.file', ['grid_id' => (int) $grid['id'], 'file_id' => $entry['file_id']]) : (($qrCodeUrlMap[$qrCodeId] ?? null) ?: ($entry['url'] ?? '#'));
                                    $isQrEntry = !isset($entry['file_id']) && ($qrCodeId > 0 && !empty($qrCodeUrlMap[$qrCodeId]) || strpos((string) $entryUrl, '/uploads/qr-codes/') !== false);
                                    ?>
                                    <a class="portal-link-card <?= isset($entry['file_id']) ? 'is-file' : ($isQrEntry ? 'is-qr' : 'is-link') ?>" href="<?= e($entryUrl) ?>" <?= $isQrEntry ? 'data-qr-image-url="' . e($entryUrl) . '" data-qr-title="' . e($entry['label'] ?? '') . '" data-qr-tone="' . e($grid['tone'] ?? 'green') . '"' : '' ?>>
                                        <span><?= e($entry['label'] ?? '') ?></span>
                                        <?php if ($isNewEntry($entry)): ?>
                                            <span class="portal-new-badge">NEW</span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    </div>

                    <?php if ($canPostToGrid): ?>
                        <dialog class="portal-modal" id="<?= e($entryDialogId) ?>">
                            <div class="portal-modal-panel">
                                <div class="portal-modal-heading section-<?= e($grid['tone'] ?? 'green') ?>">
                                    <h3><?= e($grid['title'] ?? '') ?>へ投稿</h3>
                                    <button type="button" aria-label="閉じる" data-close-dialog>×</button>
                                </div>
                                <form class="portal-entry-form" method="post" action="<?= route_url('grid.entryStore') ?>" enctype="multipart/form-data">
                                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="grid_id" value="<?= (int) ($grid['id'] ?? 0) ?>">

                                    <?php if (($grid['registration_type'] ?? '') === 'links'): ?>
                                        <label>
                                            <span>グループ名</span>
                                            <input name="entry_group" placeholder="例: 基幹システム">
                                        </label>
                                        <label>
                                            <span>リンク名</span>
                                            <input name="entry_label" required>
                                        </label>
                                        <?php if (!empty($qrCodes)): ?>
                                            <label>
                                                <span>登録QRコード</span>
                                                <select name="entry_qr_code_id" data-qr-code-select>
                                                    <option value="0" data-url="">直接URLを入力</option>
                                                    <?php foreach ($qrCodes as $qrCode): ?>
                                                        <option value="<?= (int) ($qrCode['id'] ?? 0) ?>" data-url="<?= e($qrCode['url'] ?? '') ?>" data-title="<?= e($qrCode['title'] ?? '') ?>">
                                                            <?= e($qrCode['title'] ?? '') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                        <?php endif; ?>
                                        <label>
                                            <span>URL</span>
                                            <input name="entry_url" type="url" placeholder="https://example.com" data-entry-url-input>
                                        </label>
                                    <?php elseif (($grid['registration_type'] ?? '') === 'files'): ?>
                                        <label>
                                            <span>グループ名</span>
                                            <input name="entry_group" placeholder="例: 店舗別">
                                        </label>
                                        <label>
                                            <span>表示名</span>
                                            <input name="entry_label">
                                        </label>
                                        <label>
                                            <span>ファイル</span>
                                            <input type="file" name="entry_file" required>
                                        </label>
                                    <?php elseif (($grid['registration_type'] ?? '') === 'todo'): ?>
                                        <label>
                                            <span>記入欄1</span>
                                            <input name="todo_field1" required>
                                        </label>
                                        <label>
                                            <span>記入欄2</span>
                                            <textarea name="todo_field2" rows="4"></textarea>
                                        </label>
                                        <label>
                                            <span>進捗</span>
                                            <select name="todo_progress">
                                                <option value="not_started">未着手</option>
                                                <option value="in_progress">進行中</option>
                                                <option value="done">完了</option>
                                            </select>
                                        </label>
                                        <label>
                                            <span>写真</span>
                                            <input type="file" name="todo_image" accept="image/*">
                                        </label>
                                    <?php else: ?>
                                        <label>
                                            <span>内容</span>
                                            <textarea name="entry_content" rows="5" required></textarea>
                                        </label>
                                    <?php endif; ?>

                                    <div class="form-actions">
                                        <button class="button primary" type="submit">登録</button>
                                        <button class="button ghost" type="button" data-close-dialog>閉じる</button>
                                    </div>
                                </form>
                            </div>
                        </dialog>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<dialog class="portal-modal portal-image-modal" id="portal-qr-dialog">
    <div class="portal-modal-panel">
        <div class="portal-modal-heading section-green" data-qr-modal-heading>
            <h3 data-qr-modal-title>QRコード</h3>
            <button type="button" aria-label="閉じる" data-close-dialog>×</button>
        </div>
        <div class="portal-modal-body portal-qr-modal-body">
            <img class="portal-expanded-image portal-qr-image" src="" alt="" data-qr-modal-image>
        </div>
    </div>
</dialog>

<script>
(() => {
    document.addEventListener('click', (event) => {
        const toggleButton = event.target.closest('[data-toggle-grid]');
        if (toggleButton) {
            const body = document.getElementById(toggleButton.dataset.toggleGrid);
            const section = toggleButton.closest('.portal-section');
            const isOpening = body?.hasAttribute('hidden') ?? false;
            if (body) {
                body.hidden = !isOpening;
            }
            section?.classList.toggle('is-collapsed', !isOpening);
            toggleButton.setAttribute('aria-expanded', isOpening ? 'true' : 'false');
            toggleButton.textContent = isOpening ? '▲' : '▼';
            return;
        }

        const qrLink = event.target.closest('[data-qr-image-url]');
        if (qrLink) {
            event.preventDefault();
            const dialog = document.getElementById('portal-qr-dialog');
            const image = dialog?.querySelector('[data-qr-modal-image]');
            const title = dialog?.querySelector('[data-qr-modal-title]');
            const heading = dialog?.querySelector('[data-qr-modal-heading]');
            if (image) {
                image.src = qrLink.dataset.qrImageUrl || '';
                image.alt = qrLink.dataset.qrTitle || 'QR';
            }
            if (title) {
                title.textContent = qrLink.dataset.qrTitle || 'QR';
            }
            if (heading) {
                heading.className = `portal-modal-heading section-${qrLink.dataset.qrTone || 'green'}`;
                heading.setAttribute('data-qr-modal-heading', '');
            }
            dialog?.showModal();
            return;
        }

        const openButton = event.target.closest('[data-open-dialog]');
        if (openButton) {
            const dialog = document.getElementById(openButton.dataset.openDialog);
            dialog?.showModal();
            return;
        }

        const closeButton = event.target.closest('[data-close-dialog]');
        if (closeButton) {
            closeButton.closest('dialog')?.close();
            return;
        }

        if (event.target instanceof HTMLDialogElement) {
            event.target.close();
        }
    });

    document.addEventListener('change', (event) => {
        const qrSelect = event.target.closest('[data-qr-code-select]');
        if (qrSelect) {
            const form = qrSelect.closest('form');
            const selectedOption = qrSelect.selectedOptions[0];
            const urlInput = form?.querySelector('[data-entry-url-input]');
            const labelInput = form?.querySelector('[name="entry_label"]');
            const selectedUrl = selectedOption?.dataset.url || '';
            const selectedTitle = selectedOption?.dataset.title || '';
            if (urlInput && selectedUrl !== '') {
                urlInput.value = selectedUrl;
            }
            if (labelInput && labelInput.value.trim() === '' && selectedTitle !== '') {
                labelInput.value = selectedTitle;
            }
            return;
        }

        const progressSelect = event.target.closest('.portal-todo-progress-form select');
        if (!progressSelect) {
            return;
        }

        const applyProgressTone = (value) => {
            progressSelect.classList.remove('is-not_started', 'is-in_progress', 'is-done');
            progressSelect.classList.add(`is-${value}`);
        };

        const form = progressSelect.form;
        if (!form) {
            return;
        }

        const previousValue = progressSelect.dataset.currentProgress || progressSelect.value;
        applyProgressTone(progressSelect.value);
        progressSelect.dataset.currentProgress = progressSelect.value;
        const formData = new FormData(form);
        progressSelect.disabled = true;
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Failed to update progress.');
                }

                return response.text();
            })
            .then((text) => {
                if (text.trim() === '') {
                    return { progress: progressSelect.value };
                }

                try {
                    return JSON.parse(text);
                } catch (error) {
                    return { progress: progressSelect.value };
                }
            })
            .then((data) => {
                const savedProgress = data.progress || progressSelect.value;
                progressSelect.value = savedProgress;
                applyProgressTone(savedProgress);
                progressSelect.dataset.currentProgress = savedProgress;
                if (savedProgress === 'done' && previousValue !== 'done' && data.completion_message) {
                    alert(data.completion_message);
                }
            })
            .catch(() => {
                progressSelect.value = previousValue;
                applyProgressTone(previousValue);
                progressSelect.dataset.currentProgress = previousValue;
                alert('ステータス保存の確認ができませんでした。画面更新後に状態をご確認ください。');
            })
            .finally(() => {
                progressSelect.disabled = false;
            });
    });
})();
</script>

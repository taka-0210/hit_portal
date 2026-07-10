<?php
$isEdit = $mode === 'edit';
$action = $isEdit ? route_url('admin.grids.update') : route_url('admin.grids.store');
?>
<section class="page-header">
    <p class="eyebrow">Administration</p>
    <h1><?= $isEdit ? 'グリッド編集' : 'グリッド新規作成' ?></h1>
    <p class="lead">色、対象範囲、登録方法、表示方法を設定します。表示位置はグリッド管理画面の矢印で調整します。</p>
</section>

<form class="panel staff-form grid-editor-form" method="post" action="<?= $action ?>" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int) $grid['id'] ?>">
    <?php endif; ?>

    <div class="form-section">
        <h2>基本設定</h2>
        <div class="form-grid">
            <label><span>グリッド名</span><input name="title" value="<?= e($grid['title'] ?? '') ?>" required></label>
            <fieldset class="choice-field">
                <legend>色設定</legend>
                <div class="tone-picker">
                    <?php foreach ($toneLabels as $key => $label): ?>
                        <label class="tone-option">
                            <input type="radio" name="tone" value="<?= e($key) ?>" <?= ($grid['tone'] ?? 'green') === $key ? 'checked' : '' ?>>
                            <span class="tone-chip tone-<?= e($key) ?>"></span>
                            <span><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <label>
                <span>対象範囲</span>
                <select name="scope_type" data-scope-type-select>
                    <?php foreach ($scopeTypeLabels as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($grid['scope_type'] ?? 'all') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label data-scope-target-field><span>対象名</span><input name="scope_target" value="<?= e($grid['scope_target'] ?? '') ?>" placeholder="例: 直営 / 播磨店"></label>
            <label>
                <span>登録方法</span>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="registration_type" value="<?= e($grid['registration_type'] ?? 'links') ?>">
                <?php endif; ?>
                <select name="registration_type" <?= $isEdit ? 'disabled' : '' ?>>
                    <?php foreach ($registrationTypeLabels as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($grid['registration_type'] ?? 'links') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isEdit): ?>
                    <small class="field-hint">登録方法は作成後に変更できません。データの持ち方が変わるため、新しい登録方法が必要な場合は別グリッドを作成します。</small>
                <?php endif; ?>
            </label>
            <label>
                <span>表示方法</span>
                <select name="display_type" data-display-type-select>
                    <?php foreach ($displayTypeLabels as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($grid['display_type'] ?? 'list') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>展開パターン</span>
                <select name="expand_type">
                    <?php foreach ($expandTypeLabels as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($grid['expand_type'] ?? 'open') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>公開設定</span>
                <select name="status">
                    <option value="published" <?= ($grid['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>公開</option>
                    <option value="draft" <?= ($grid['status'] ?? 'draft') !== 'published' ? 'selected' : '' ?>>非公開</option>
                </select>
            </label>
        </div>
    </div>

    <div class="form-section">
        <h2>登録内容</h2>
        <div class="grid-content-editor" data-registration-panel="links">
            <div class="link-row link-row-head">
                <span>グループ名</span>
                <span>リンク名</span>
                <span>URL</span>
                <span></span>
            </div>
            <div class="link-rows" data-link-rows>
                <?php foreach ($linkRows as $row): ?>
                    <div class="link-row">
                        <input name="link_group[]" value="<?= e($row['group']) ?>" placeholder="例: 基幹システム">
                        <input name="link_label[]" value="<?= e($row['label']) ?>" placeholder="例: 厨房君PRO">
                        <input name="link_url[]" value="<?= e($row['url']) ?>" placeholder="https://example.com">
                        <input type="hidden" name="link_created_at[]" value="<?= e($row['created_at'] ?? '') ?>">
                        <div class="row-actions">
                            <button class="button ghost icon-button" type="button" data-move-link-row="up" aria-label="上へ移動">&#9650;</button>
                            <button class="button ghost icon-button" type="button" data-move-link-row="down" aria-label="下へ移動">&#9660;</button>
                            <button class="button ghost" type="button" data-remove-link-row>削除</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="button ghost" type="button" data-add-link-row>行を追加</button>
            <small class="field-hint">グループ名を同じにすると、TOPでは同じグループにまとまります。グループ不要の場合は空欄で登録できます。</small>
        </div>

        <div class="grid-content-editor" data-registration-panel="files">
            <div class="file-row file-row-head">
                <span>グループ名</span>
                <span>表示名</span>
                <span>ファイル</span>
                <span></span>
            </div>
            <div class="file-rows" data-file-rows>
                <?php foreach ($fileRows as $row): ?>
                    <div class="file-row">
                        <input name="file_group[]" value="<?= e($row['group']) ?>" placeholder="例: 部門別">
                        <input name="file_label[]" value="<?= e($row['label']) ?>" placeholder="例: 有給休暇管理一覧表">
                        <div class="file-input-stack">
                            <?php if (($row['original_name'] ?? '') !== ''): ?>
                                <small class="field-hint">登録済み: <?= e($row['original_name']) ?></small>
                            <?php endif; ?>
                            <input type="file" name="grid_files[]">
                            <input type="hidden" name="existing_file_id[]" value="<?= e($row['file_id']) ?>">
                            <input type="hidden" name="existing_original_name[]" value="<?= e($row['original_name']) ?>">
                            <input type="hidden" name="existing_storage_path[]" value="<?= e($row['storage_path']) ?>">
                            <input type="hidden" name="existing_mime_type[]" value="<?= e($row['mime_type']) ?>">
                            <input type="hidden" name="existing_file_size[]" value="<?= (int) $row['file_size'] ?>">
                            <input type="hidden" name="file_created_at[]" value="<?= e($row['created_at'] ?? '') ?>">
                        </div>
                        <button class="button ghost" type="button" data-remove-file-row>削除</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="button ghost" type="button" data-add-file-row>行を追加</button>
            <small class="field-hint">グループ名を同じにすると、TOPでは同じグループにまとまります。編集時に新しいファイルを選ぶと、その行のファイルを差し替えます。</small>
        </div>

        <div class="grid-content-editor todo-editor" data-registration-panel="todo">
            <div class="todo-rows" data-todo-rows>
                <?php foreach ($todoRows as $row): ?>
                    <div class="todo-row">
                        <label>
                            <span>記入欄1</span>
                            <input name="todo_field1[]" value="<?= e($row['field1'] ?? '') ?>" placeholder="例: 店舗入口の掲示物を更新">
                        </label>
                        <label>
                            <span>記入欄2</span>
                            <textarea name="todo_field2[]" rows="4" placeholder="補足、対応内容、期限など"><?= e($row['field2'] ?? '') ?></textarea>
                        </label>
                        <label>
                            <span>進捗</span>
                            <select name="todo_progress[]">
                                <?php foreach (['not_started' => '未着手', 'in_progress' => '進行中', 'done' => '完了'] as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= ($row['progress'] ?? 'not_started') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span>画像</span>
                            <?php if (($row['original_name'] ?? '') !== ''): ?>
                                <small class="field-hint">登録済み: <?= e($row['original_name']) ?></small>
                            <?php endif; ?>
                            <input type="file" name="todo_images[]" accept="image/*">
                            <input type="hidden" name="existing_todo_file_id[]" value="<?= e($row['file_id'] ?? '') ?>">
                            <input type="hidden" name="existing_todo_original_name[]" value="<?= e($row['original_name'] ?? '') ?>">
                            <input type="hidden" name="existing_todo_storage_path[]" value="<?= e($row['storage_path'] ?? '') ?>">
                            <input type="hidden" name="existing_todo_mime_type[]" value="<?= e($row['mime_type'] ?? '') ?>">
                            <input type="hidden" name="existing_todo_file_size[]" value="<?= (int) ($row['file_size'] ?? 0) ?>">
                            <input type="hidden" name="todo_created_at[]" value="<?= e($row['created_at'] ?? '') ?>">
                            <input type="hidden" name="todo_completed_at[]" value="<?= e($row['completed_at'] ?? '') ?>">
                        </label>
                        <button class="button ghost" type="button" data-remove-todo-row>削除</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="button ghost" type="button" data-add-todo-row>TO DOを追加</button>
            <small class="field-hint">TO DOは複数登録できます。画像は各TO DOにつき1枚だけ登録できます。</small>
        </div>

        <label class="form-stack grid-content-editor" data-registration-panel="manual">
            <span>内容</span>
            <textarea name="content" rows="10" placeholder="表示名 | URL&#10;グループ名 | 表示名 | URL"><?= e($contentText) ?></textarea>
            <small class="field-hint">行数が多い場合はTOPでポップアップ表示になります。</small>
        </label>
    </div>

    <div class="form-actions">
        <button class="button primary" type="submit"><?= $isEdit ? '更新' : '作成' ?></button>
        <a class="button ghost" href="<?= route_url('admin.grids') ?>">戻る</a>
    </div>
</form>

<template id="link-row-template">
    <div class="link-row">
        <input name="link_group[]" placeholder="例: 基幹システム">
        <input name="link_label[]" placeholder="例: 厨房君PRO">
        <input name="link_url[]" placeholder="https://example.com">
        <input type="hidden" name="link_created_at[]">
        <div class="row-actions">
            <button class="button ghost icon-button" type="button" data-move-link-row="up" aria-label="上へ移動">&#9650;</button>
            <button class="button ghost icon-button" type="button" data-move-link-row="down" aria-label="下へ移動">&#9660;</button>
            <button class="button ghost" type="button" data-remove-link-row>削除</button>
        </div>
    </div>
</template>

<template id="file-row-template">
    <div class="file-row">
        <input name="file_group[]" placeholder="例: 部門別">
        <input name="file_label[]" placeholder="例: 有給休暇管理一覧表">
        <div class="file-input-stack">
            <input type="file" name="grid_files[]">
            <input type="hidden" name="existing_file_id[]">
            <input type="hidden" name="existing_original_name[]">
            <input type="hidden" name="existing_storage_path[]">
            <input type="hidden" name="existing_mime_type[]">
            <input type="hidden" name="existing_file_size[]" value="0">
            <input type="hidden" name="file_created_at[]">
        </div>
        <button class="button ghost" type="button" data-remove-file-row>削除</button>
    </div>
</template>

<template id="todo-row-template">
    <div class="todo-row">
        <label>
            <span>記入欄1</span>
            <input name="todo_field1[]" placeholder="例: 店舗入口の掲示物を更新">
        </label>
        <label>
            <span>記入欄2</span>
            <textarea name="todo_field2[]" rows="4" placeholder="補足、対応内容、期限など"></textarea>
        </label>
        <label>
            <span>進捗</span>
            <select name="todo_progress[]">
                <option value="not_started">未着手</option>
                <option value="in_progress">進行中</option>
                <option value="done">完了</option>
            </select>
        </label>
        <label>
            <span>画像</span>
            <input type="file" name="todo_images[]" accept="image/*">
            <input type="hidden" name="existing_todo_file_id[]">
            <input type="hidden" name="existing_todo_original_name[]">
            <input type="hidden" name="existing_todo_storage_path[]">
            <input type="hidden" name="existing_todo_mime_type[]">
            <input type="hidden" name="existing_todo_file_size[]" value="0">
            <input type="hidden" name="todo_created_at[]">
            <input type="hidden" name="todo_completed_at[]">
        </label>
        <button class="button ghost" type="button" data-remove-todo-row>削除</button>
    </div>
</template>

<script>
(() => {
    const registrationSelect = document.querySelector('[name="registration_type"]');
    const displayTypeSelect = document.querySelector('[data-display-type-select]');
    const scopeTypeSelect = document.querySelector('[data-scope-type-select]');
    const scopeTargetField = document.querySelector('[data-scope-target-field]');
    const panels = document.querySelectorAll('[data-registration-panel]');
    const linkRows = document.querySelector('[data-link-rows]');
    const linkTemplate = document.querySelector('#link-row-template');
    const addButton = document.querySelector('[data-add-link-row]');
    const fileRows = document.querySelector('[data-file-rows]');
    const fileTemplate = document.querySelector('#file-row-template');
    const addFileButton = document.querySelector('[data-add-file-row]');
    const todoRows = document.querySelector('[data-todo-rows]');
    const todoTemplate = document.querySelector('#todo-row-template');
    const addTodoButton = document.querySelector('[data-add-todo-row]');

    const syncPanels = () => {
        const value = registrationSelect?.value || 'links';
        panels.forEach((panel) => {
            const isActive = panel.dataset.registrationPanel === value;
            panel.hidden = !isActive;
        });
        if (displayTypeSelect) {
            const isListOnly = ['manual', 'todo'].includes(value);
            if (isListOnly) {
                displayTypeSelect.value = 'list';
            }
            displayTypeSelect.disabled = isListOnly;
        }
    };

    const syncScopeTarget = () => {
        if (!scopeTypeSelect || !scopeTargetField) {
            return;
        }

        scopeTargetField.hidden = !['company', 'store'].includes(scopeTypeSelect.value);
    };

    addButton?.addEventListener('click', () => {
        if (!linkRows || !linkTemplate) {
            return;
        }

        linkRows.appendChild(linkTemplate.content.cloneNode(true));
    });

    addFileButton?.addEventListener('click', () => {
        if (!fileRows || !fileTemplate) {
            return;
        }

        fileRows.appendChild(fileTemplate.content.cloneNode(true));
    });

    addTodoButton?.addEventListener('click', () => {
        if (!todoRows || !todoTemplate) {
            return;
        }

        todoRows.appendChild(todoTemplate.content.cloneNode(true));
    });

    linkRows?.addEventListener('click', (event) => {
        const moveButton = event.target.closest('[data-move-link-row]');
        if (moveButton) {
            const row = moveButton.closest('.link-row');
            if (!row) {
                return;
            }

            if (moveButton.dataset.moveLinkRow === 'up' && row.previousElementSibling) {
                linkRows.insertBefore(row, row.previousElementSibling);
            }
            if (moveButton.dataset.moveLinkRow === 'down' && row.nextElementSibling) {
                linkRows.insertBefore(row.nextElementSibling, row);
            }
            return;
        }

        const button = event.target.closest('[data-remove-link-row]');
        if (!button) {
            return;
        }

        const rows = linkRows.querySelectorAll('.link-row');
        if (rows.length <= 1) {
            const row = button.closest('.link-row');
            row?.querySelectorAll('input').forEach((input) => input.value = '');
            return;
        }

        button.closest('.link-row')?.remove();
    });

    fileRows?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-file-row]');
        if (!button) {
            return;
        }

        const rows = fileRows.querySelectorAll('.file-row');
        if (rows.length <= 1) {
            const row = button.closest('.file-row');
            row?.querySelectorAll('input').forEach((input) => input.value = input.type === 'hidden' && input.name === 'existing_file_size[]' ? '0' : '');
            return;
        }

        button.closest('.file-row')?.remove();
    });

    todoRows?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-todo-row]');
        if (!button) {
            return;
        }

        const rows = todoRows.querySelectorAll('.todo-row');
        if (rows.length <= 1) {
            const row = button.closest('.todo-row');
            row?.querySelectorAll('input, textarea').forEach((input) => input.value = input.type === 'hidden' && input.name === 'existing_todo_file_size[]' ? '0' : '');
            const select = row?.querySelector('select');
            if (select) {
                select.value = 'not_started';
            }
            return;
        }

        button.closest('.todo-row')?.remove();
    });

    registrationSelect?.addEventListener('change', syncPanels);
    scopeTypeSelect?.addEventListener('change', syncScopeTarget);
    syncPanels();
    syncScopeTarget();
})();
</script>

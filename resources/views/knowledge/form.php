<?php
$isEdit = $article !== null;
$action = $isEdit ? route_url('knowledge.update') : route_url('knowledge.store');
$tagValue = $isEdit ? implode(', ', $article['tags'] ?? []) : '';
?>
<section class="page-header">
    <p class="eyebrow">Knowledge Editor</p>
    <h1><?= $isEdit ? 'ナレッジ編集' : 'ナレッジ登録' ?></h1>
    <p class="lead">完璧な記事より、あとで改善できる記録を優先します。</p>
</section>

<form class="editor-layout" method="post" action="<?= $action ?>" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
    <?php endif; ?>

    <section class="panel form-stack">
        <label>
            <span>タイトル</span>
            <input name="title" value="<?= e($article['title'] ?? '') ?>" required>
        </label>
        <label>
            <span>要約</span>
            <textarea name="summary" rows="3"><?= e($article['summary'] ?? '') ?></textarea>
        </label>
        <label>
            <span>本文</span>
            <textarea name="body" rows="12" required><?= e($article['body'] ?? '') ?></textarea>
        </label>
    </section>

    <aside class="panel form-stack">
        <label>
            <span>カテゴリ</span>
            <select name="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= (int) ($article['category_id'] ?? 1) === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>種別</span>
            <select name="type" required>
                <?php foreach ($types as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= ($article['type'] ?? 'document') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>タグ</span>
            <input name="tags" value="<?= e($tagValue) ?>" placeholder="例: メーカー, 商品, 改善">
        </label>
        <label>
            <span>ソース名</span>
            <input name="source_name" value="<?= e($article['source_name'] ?? '') ?>" placeholder="資料名、動画名など">
        </label>
        <label>
            <span>ソースURL / 保管場所</span>
            <input name="source_url" value="<?= e($article['source_url'] ?? '') ?>" placeholder="https://... または C:\共有\資料.pdf">
            <small class="field-hint">Web URL はリンク表示します。ローカルパスは保管場所として表示します。</small>
        </label>
        <label>
            <span>添付ファイル</span>
            <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.mp4,.webm,.mov">
            <small class="field-hint">PDF、画像、動画を添付できます。1ファイル 50MB まで。</small>
        </label>

        <?php if ($attachments !== []): ?>
            <div class="attached-summary">
                <strong>登録済みファイル</strong>
                <?php foreach ($attachments as $attachment): ?>
                    <a href="<?= route_url('knowledge.file', ['id' => (int) $attachment['id']]) ?>" target="_blank" rel="noopener">
                        <?= e($attachment['original_name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button class="button primary" type="submit"><?= $isEdit ? '更新' : '登録' ?></button>
        <a class="button ghost" href="<?= route_url('knowledge') ?>">戻る</a>
    </aside>
</form>

<section class="page-header">
    <p class="eyebrow">Administration</p>
    <h1>取扱説明管理</h1>
    <p class="lead">総務部や店舗スタッフが確認する取扱説明を編集します。</p>
</section>

<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash-message"><?= e($_SESSION['flash']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<form class="panel staff-form" method="post" action="<?= route_url('admin.guide.update') ?>">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

    <div class="form-section">
        <h2>取扱説明</h2>
        <label class="form-stack">
            <span>タイトル</span>
            <input name="guide_title" value="<?= e($settings['guide_title'] ?? 'HIT Portal 取扱説明') ?>" required>
        </label>
        <label class="form-stack">
            <span>本文</span>
            <textarea name="guide_body" rows="22" required><?= e($settings['guide_body'] ?? '') ?></textarea>
            <small class="field-hint">改行はそのまま表示されます。実証実験中に気づいたことを追記して運用できます。</small>
        </label>
    </div>

    <div class="form-actions">
        <button class="button primary" type="submit">更新</button>
        <a class="button ghost" href="<?= route_url('guide') ?>">取扱説明を見る</a>
    </div>
</form>

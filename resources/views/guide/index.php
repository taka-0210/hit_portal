<section class="page-header">
    <p class="eyebrow">Guide</p>
    <h1><?= e($title ?? 'HIT Portal 取扱説明') ?></h1>
    <p class="lead">実証実験中の使い方、投稿ルール、困った時の確認先をまとめています。</p>
</section>

<section class="panel guide-panel">
    <div class="guide-body">
        <?= nl2br(e($body ?? '')) ?>
    </div>
</section>

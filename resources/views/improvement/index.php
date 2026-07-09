<section class="page-header">
    <p class="eyebrow">Improvement Log</p>
    <h1>改善ログ</h1>
    <p class="lead">ポータルの改善内容や変更履歴を残します。</p>
</section>

<section class="panel">
    <div class="panel-heading">
        <h2>改善メモ一覧</h2>
    </div>

    <div class="improvement-list">
        <?php foreach ($improvements as $index => $item): ?>
            <?php
            $updatedAt = strtotime((string) ($item['updated_at'] ?? ''));
            $dateLabel = $updatedAt === false ? '' : date('Y.n.j', $updatedAt);
            ?>
            <article class="improvement-item">
                <div>
                    <div class="meta-line">
                        <span class="badge">No.<?= (int) $index + 1 ?></span>
                        <span class="badge"><?= e($reasonLabels[$item['reason'] ?? 'other'] ?? '改善') ?></span>
                        <span class="badge muted"><?= e($item['status'] ?? 'idea') ?></span>
                        <span><?= e($dateLabel) ?></span>
                    </div>
                    <h2><?= e($item['title'] ?? '') ?></h2>
                    <?php if (!empty($item['note'])): ?>
                        <p><?= nl2br(e($item['note'])) ?></p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if ($improvements === []): ?>
            <div class="empty-state">
                <h2>改善メモはまだありません</h2>
                <p>ポータルの改善内容をここに残していきます。</p>
            </div>
        <?php endif; ?>
    </div>
</section>

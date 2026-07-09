<section class="page-header inline-actions">
    <div>
        <p class="eyebrow">Knowledge Detail</p>
        <h1><?= e($article['title']) ?></h1>
        <p class="lead"><?= e($article['summary']) ?></p>
    </div>
    <a class="button" href="<?= route_url('knowledge.edit', ['id' => $article['id']]) ?>">編集</a>
</section>

<?php if (($_GET['improvement'] ?? '') === 'created'): ?>
    <section class="flash-message">
        改善メモを登録しました。小さな気づきを残すことが、ポータルを育てる最初の一歩です。
    </section>
<?php endif; ?>

<article class="detail-layout">
    <section class="panel article-body">
        <?= nl2br(e($article['body'])) ?>

        <?php if ($attachments !== []): ?>
            <div class="attachment-section">
                <h2>添付資料</h2>
                <div class="attachment-grid">
                    <?php foreach ($attachments as $attachment): ?>
                        <?php $fileUrl = route_url('knowledge.file', ['id' => (int) $attachment['id']]); ?>
                        <article class="attachment-card">
                            <div>
                                <strong><?= e($attachment['original_name']) ?></strong>
                                <small><?= e($attachment['mime_type']) ?> / <?= number_format(((int) $attachment['file_size']) / 1024, 1) ?> KB</small>
                            </div>

                            <?php if ($repository->fileStorage()->isPreviewableImage($attachment)): ?>
                                <a href="<?= $fileUrl ?>" target="_blank" rel="noopener">
                                    <img src="<?= $fileUrl ?>" alt="<?= e($attachment['original_name']) ?>">
                                </a>
                            <?php elseif ($repository->fileStorage()->isPdf($attachment)): ?>
                                <iframe src="<?= $fileUrl ?>" title="<?= e($attachment['original_name']) ?>"></iframe>
                            <?php elseif ($repository->fileStorage()->isPreviewableVideo($attachment)): ?>
                                <video controls src="<?= $fileUrl ?>"></video>
                            <?php endif; ?>

                            <a class="button ghost" href="<?= $fileUrl ?>" target="_blank" rel="noopener">資料を開く</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <aside class="panel side-panel">
        <h2>メタ情報</h2>
        <dl class="definition-list">
            <dt>カテゴリ</dt>
            <dd><?= e($repository->categoryName((int) $article['category_id'])) ?></dd>
            <dt>種別</dt>
            <dd><?= e($article['type']) ?></dd>
            <dt>更新日時</dt>
            <dd><?= e($article['updated_at'] ?? '') ?></dd>
            <dt>ソース</dt>
            <dd>
                <?php if (!empty($article['source_url'])): ?>
                    <?php $source = $repository->displaySource($article['source_url']); ?>
                    <?php if ($repository->isExternalSource($source)): ?>
                        <a href="<?= e($source) ?>" target="_blank" rel="noopener"><?= e($article['source_name'] ?: $source) ?></a>
                    <?php else: ?>
                        <div class="source-path">
                            <strong><?= e($article['source_name'] ?: '保管場所') ?></strong>
                            <code><?= e($source) ?></code>
                            <small>ローカルパスはブラウザの制限によりリンクとして開けないため、保管場所として表示しています。</small>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    未設定
                <?php endif; ?>
            </dd>
        </dl>

        <h2>タグ</h2>
        <div class="tag-row">
            <?php foreach (($article['tags'] ?? []) as $tag): ?>
                <span>#<?= e($tag) ?></span>
            <?php endforeach; ?>
        </div>

        <div class="improvement-box">
            <strong>この情報を改善する</strong>
            <p>古い、足りない、探しにくい。気づいた時点で短く残してください。</p>

            <form class="improvement-form" method="post" action="<?= route_url('improvements.storeFromKnowledge') ?>">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="knowledge_id" value="<?= (int) $article['id'] ?>">
                <label>
                    <span>理由</span>
                    <select name="reason">
                        <option value="needs_update">情報が古い</option>
                        <option value="missing_info">情報が足りない</option>
                        <option value="hard_to_find">探しにくい</option>
                        <option value="hard_to_understand">分かりにくい</option>
                        <option value="other">その他</option>
                    </select>
                </label>
                <label>
                    <span>タイトル</span>
                    <input name="title" value="<?= e($article['title']) ?> の改善">
                </label>
                <label>
                    <span>メモ</span>
                    <textarea name="note" rows="4" placeholder="例: 型番の情報が足りない、PDFが古い、検索で見つけにくい"></textarea>
                </label>
                <button class="button primary" type="submit">改善メモを残す</button>
            </form>
        </div>
    </aside>
</article>

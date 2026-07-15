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
$glossaryIndexLabels = ['あ', 'い', 'う', 'え', 'お', 'か', 'き', 'く', 'け', 'こ', 'さ', 'し', 'す', 'せ', 'そ', 'た', 'ち', 'つ', 'て', 'と', 'な', 'に', 'ぬ', 'ね', 'の', 'は', 'ひ', 'ふ', 'へ', 'ほ', 'ま', 'み', 'む', 'め', 'も', 'や', 'ゆ', 'よ', 'ら', 'り', 'る', 'れ', 'ろ', 'わ', 'を', 'ん', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'その他'];
$firstCharacter = static function (string $value): string {
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, 1, 'UTF-8');
    }

    if (preg_match('/^./u', $value, $matches) === 1) {
        return $matches[0];
    }

    return substr($value, 0, 1);
};
$glossaryIndexKey = static function (string $term) use ($firstCharacter): string {
    $term = trim($term);
    $term = preg_replace('/^[\s\x{3000}]+|[\s\x{3000}]+$/u', '', $term) ?? $term;
    if ($term === '') {
        return 'その他';
    }

    $alphabetReading = function_exists('mb_convert_kana') ? mb_convert_kana($term, 'KVc', 'UTF-8') : $term;
    $alphabetReadings = [
        'えー' => 'A', 'えい' => 'A',
        'びー' => 'B', 'しー' => 'C',
        'でぃー' => 'D', 'でー' => 'D',
        'いー' => 'E', 'えふ' => 'F', 'じー' => 'G',
        'えいち' => 'H', 'えっち' => 'H',
        'あい' => 'I', 'じぇー' => 'J',
        'けー' => 'K', 'える' => 'L', 'えむ' => 'M', 'えぬ' => 'N',
        'おー' => 'O', 'ぴー' => 'P', 'きゅー' => 'Q',
        'あーる' => 'R', 'えす' => 'S',
        'てぃー' => 'T', 'てぃ' => 'T', 'てー' => 'T',
        'ゆー' => 'U', 'ぶい' => 'V', 'ヴぃー' => 'V', 'ゔぃー' => 'V', 'ゔい' => 'V',
        'だぶりゅー' => 'W', 'だぶる' => 'W',
        'えっくす' => 'X', 'わい' => 'Y', 'ぜっと' => 'Z',
    ];
    foreach ($alphabetReadings as $prefix => $letter) {
        $length = function_exists('mb_strlen') ? mb_strlen($prefix, 'UTF-8') : strlen($prefix);
        $start = function_exists('mb_substr') ? mb_substr($alphabetReading, 0, $length, 'UTF-8') : substr($alphabetReading, 0, $length);
        if ($start === $prefix) {
            return $letter;
        }
    }

    $first = $firstCharacter($term);
    if (preg_match('/^[A-Za-z]/', $first) === 1) {
        return strtoupper($first);
    }

    $normalizedTerm = function_exists('mb_convert_kana') ? mb_convert_kana($term, 'KVc', 'UTF-8') : $term;
    if (preg_match('/^(ヴ|ゔ|ウ゛|う゛|ｳﾞ)/u', $normalizedTerm) === 1) {
        return 'う';
    }

    if (function_exists('mb_convert_kana')) {
        $first = mb_convert_kana($first, 'KVc', 'UTF-8');
    }

    $map = [
        'が' => 'か', 'ぎ' => 'き', 'ぐ' => 'く', 'げ' => 'け', 'ご' => 'こ',
        'ざ' => 'さ', 'じ' => 'し', 'ず' => 'す', 'ぜ' => 'せ', 'ぞ' => 'そ',
        'だ' => 'た', 'ぢ' => 'ち', 'づ' => 'つ', 'で' => 'て', 'ど' => 'と',
        'ば' => 'は', 'び' => 'ひ', 'ぶ' => 'ふ', 'べ' => 'へ', 'ぼ' => 'ほ',
        'ぱ' => 'は', 'ぴ' => 'ひ', 'ぷ' => 'ふ', 'ぺ' => 'へ', 'ぽ' => 'ほ',
        'ヴ' => 'う', 'ゔ' => 'う', 'ヵ' => 'か', 'ヶ' => 'け', 'ゕ' => 'か', 'ゖ' => 'け', 'ゎ' => 'わ',
        'ヷ' => 'わ', 'ヸ' => 'い', 'ヹ' => 'え', 'ヺ' => 'を',
    ];
    $first = $map[$first] ?? $first;
    $kana = ['あ', 'い', 'う', 'え', 'お', 'か', 'き', 'く', 'け', 'こ', 'さ', 'し', 'す', 'せ', 'そ', 'た', 'ち', 'つ', 'て', 'と', 'な', 'に', 'ぬ', 'ね', 'の', 'は', 'ひ', 'ふ', 'へ', 'ほ', 'ま', 'み', 'む', 'め', 'も', 'や', 'ゆ', 'よ', 'ら', 'り', 'る', 'れ', 'ろ', 'わ', 'を', 'ん'];

    return in_array($first, $kana, true) ? $first : 'その他';
};
$kanaIndexKey = static function (string $term) use ($firstCharacter): string {
    $term = trim($term);
    $term = preg_replace('/^[\s\x{3000}]+|[\s\x{3000}]+$/u', '', $term) ?? $term;
    if ($term === '') {
        return 'その他';
    }

    $normalizedTerm = function_exists('mb_convert_kana') ? mb_convert_kana($term, 'KVc', 'UTF-8') : $term;
    $normalizedTerm = str_replace(['ヴ', 'ゔ', 'ウ゛', 'う゛', 'ｳﾞ'], 'う', $normalizedTerm);

    $first = $firstCharacter($normalizedTerm);
    if (function_exists('mb_convert_kana')) {
        $first = mb_convert_kana($first, 'KVc', 'UTF-8');
    }

    $map = [
        'ア' => 'あ', 'イ' => 'い', 'ウ' => 'う', 'エ' => 'え', 'オ' => 'お',
        'カ' => 'か', 'キ' => 'き', 'ク' => 'く', 'ケ' => 'け', 'コ' => 'こ',
        'サ' => 'さ', 'シ' => 'し', 'ス' => 'す', 'セ' => 'せ', 'ソ' => 'そ',
        'タ' => 'た', 'チ' => 'ち', 'ツ' => 'つ', 'テ' => 'て', 'ト' => 'と',
        'ナ' => 'な', 'ニ' => 'に', 'ヌ' => 'ぬ', 'ネ' => 'ね', 'ノ' => 'の',
        'ハ' => 'は', 'ヒ' => 'ひ', 'フ' => 'ふ', 'ヘ' => 'へ', 'ホ' => 'ほ',
        'マ' => 'ま', 'ミ' => 'み', 'ム' => 'む', 'メ' => 'め', 'モ' => 'も',
        'ヤ' => 'や', 'ユ' => 'ゆ', 'ヨ' => 'よ',
        'ラ' => 'ら', 'リ' => 'り', 'ル' => 'る', 'レ' => 'れ', 'ロ' => 'ろ',
        'ワ' => 'わ', 'ヲ' => 'を', 'ン' => 'ん',
        'ヴ' => 'う', 'ヵ' => 'か', 'ヶ' => 'け',
        'ヷ' => 'わ', 'ヸ' => 'い', 'ヹ' => 'え', 'ヺ' => 'を',
        'ガ' => 'か', 'ギ' => 'き', 'グ' => 'く', 'ゲ' => 'け', 'ゴ' => 'こ',
        'ザ' => 'さ', 'ジ' => 'し', 'ズ' => 'す', 'ゼ' => 'せ', 'ゾ' => 'そ',
        'ダ' => 'た', 'ヂ' => 'ち', 'ヅ' => 'つ', 'デ' => 'て', 'ド' => 'と',
        'バ' => 'は', 'ビ' => 'ひ', 'ブ' => 'ふ', 'ベ' => 'へ', 'ボ' => 'ほ',
        'パ' => 'は', 'ピ' => 'ひ', 'プ' => 'ふ', 'ペ' => 'へ', 'ポ' => 'ほ',
        'ぁ' => 'あ', 'ぃ' => 'い', 'ぅ' => 'う', 'ぇ' => 'え', 'ぉ' => 'お',
        'ゃ' => 'や', 'ゅ' => 'ゆ', 'ょ' => 'よ', 'っ' => 'つ',
        'ゔ' => 'う', 'ゕ' => 'か', 'ゖ' => 'け', 'ゎ' => 'わ',
        'が' => 'か', 'ぎ' => 'き', 'ぐ' => 'く', 'げ' => 'け', 'ご' => 'こ',
        'ざ' => 'さ', 'じ' => 'し', 'ず' => 'す', 'ぜ' => 'せ', 'ぞ' => 'そ',
        'だ' => 'た', 'ぢ' => 'ち', 'づ' => 'つ', 'で' => 'て', 'ど' => 'と',
        'ば' => 'は', 'び' => 'ひ', 'ぶ' => 'ふ', 'べ' => 'へ', 'ぼ' => 'ほ',
        'ぱ' => 'は', 'ぴ' => 'ひ', 'ぷ' => 'ふ', 'ぺ' => 'へ', 'ぽ' => 'ほ',
    ];
    $first = $map[$first] ?? $first;
    $kana = ['あ', 'い', 'う', 'え', 'お', 'か', 'き', 'く', 'け', 'こ', 'さ', 'し', 'す', 'せ', 'そ', 'た', 'ち', 'つ', 'て', 'と', 'な', 'に', 'ぬ', 'ね', 'の', 'は', 'ひ', 'ふ', 'へ', 'ほ', 'ま', 'み', 'む', 'め', 'も', 'や', 'ゆ', 'よ', 'ら', 'り', 'る', 'れ', 'ろ', 'わ', 'を', 'ん'];

    return in_array($first, $kana, true) ? $first : 'その他';
};
$manufacturerIndexKeys = static function (string $name, string $reading, bool $useNameIndex) use ($kanaIndexKey): array {
    $keys = [$kanaIndexKey($reading !== '' ? $reading : $name)];
    if ($useNameIndex && preg_match('/^[A-Za-z]/', $name) === 1) {
        $keys[] = strtoupper(substr($name, 0, 1));
    }

    return array_values(array_unique($keys));
};
?>
<!-- dashboard-index-version: kana-normalize-20260712-2 -->
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
                $canPostToGrid = $user !== null && ($grid['post_permission'] ?? 'allowed') !== 'denied';
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

                    <?php if (($grid['registration_type'] ?? '') === 'manufacturer_links'): ?>
                        <?php
                        $manufacturerEntries = [];
                        foreach (($grid['groups'] ?? []) as $groupIndex => $group) {
                            foreach (($group['entries'] ?? []) as $entryIndex => $entry) {
                                $name = trim((string) ($entry['label'] ?? ''));
                                if ($name === '') {
                                    continue;
                                }
                                $reading = trim((string) ($entry['reading'] ?? ''));
                                $entry['index_keys'] = $manufacturerIndexKeys($name, $reading, !empty($entry['use_name_index']));
                                $entry['index_key'] = $entry['index_keys'][0] ?? 'その他';
                                $entry['group_index'] = $groupIndex;
                                $entry['entry_index'] = $entryIndex;
                                $manufacturerEntries[] = $entry;
                            }
                        }
                        usort($manufacturerEntries, static function (array $a, array $b): int {
                            $aReading = trim((string) ($a['reading'] ?? ''));
                            $bReading = trim((string) ($b['reading'] ?? ''));
                            return strcmp($aReading !== '' ? $aReading : (string) ($a['label'] ?? ''), $bReading !== '' ? $bReading : (string) ($b['label'] ?? ''));
                        });
                        $availableManufacturerKeys = [];
                        foreach ($manufacturerEntries as $entry) {
                            foreach (($entry['index_keys'] ?? [$entry['index_key'] ?? 'その他']) as $indexKey) {
                                $availableManufacturerKeys[(string) $indexKey] = true;
                            }
                        }
                        $manufacturerGridId = (int) ($grid['id'] ?? 0);
                        $manufacturerBrowserId = 'portal-manufacturer-browser-' . $manufacturerGridId;
                        ?>
                        <div class="portal-glossary" data-glossary-grid="<?= $manufacturerGridId ?>">
                            <div class="portal-glossary-index">
                                <button type="button" data-open-glossary-browser="<?= e($manufacturerBrowserId) ?>" data-glossary-filter="all">ALL</button>
                                <?php foreach ($glossaryIndexLabels as $indexLabel): ?>
                                    <button type="button" data-open-glossary-browser="<?= e($manufacturerBrowserId) ?>" data-glossary-filter="<?= e($indexLabel) ?>" <?= empty($availableManufacturerKeys[$indexLabel]) ? 'disabled' : '' ?>><?= e($indexLabel) ?></button>
                                <?php endforeach; ?>
                            </div>
                            <dialog class="portal-modal portal-glossary-modal" id="<?= e($manufacturerBrowserId) ?>">
                                <div class="portal-modal-panel">
                                    <div class="portal-modal-heading section-<?= e($grid['tone'] ?? 'green') ?>">
                                        <h3><span data-glossary-active-index>ALL</span> / <?= e($grid['title'] ?? '') ?></h3>
                                        <button type="button" aria-label="閉じる" data-close-dialog>×</button>
                                    </div>
                                    <div class="portal-modal-body portal-glossary-browser">
                                        <div class="portal-glossary-list" data-glossary-list>
                                <?php foreach ($manufacturerEntries as $manufacturerIndex => $entry): ?>
                                    <?php
                                    $manufacturerDetailId = 'portal-manufacturer-detail-' . $manufacturerGridId . '-' . $manufacturerIndex;
                                    $manufacturerKeys = $entry['index_keys'] ?? [$entry['index_key'] ?? 'その他'];
                                    ?>
                                    <button class="portal-glossary-term" type="button" data-glossary-key="<?= e($manufacturerKeys[0] ?? 'その他') ?>" data-glossary-keys="<?= e(implode(' ', $manufacturerKeys)) ?>" data-glossary-detail="<?= e($manufacturerDetailId) ?>">
                                        <span><?= e($entry['label'] ?? '') ?></span>
                                        <?php if (($entry['reading'] ?? '') !== ''): ?>
                                            <small><?= e($entry['reading']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($isNewEntry($entry)): ?>
                                            <span class="portal-new-badge">NEW</span>
                                        <?php endif; ?>
                                    </button>
                                    <div class="portal-glossary-detail" id="<?= e($manufacturerDetailId) ?>" hidden>
                                        <div class="portal-manufacturer-detail-view" data-glossary-detail-view>
                                            <div class="portal-glossary-detail-head">
                                                <?php if ($canPostToGrid): ?>
                                                    <button class="button ghost" type="button" data-glossary-edit>編集</button>
                                                <?php endif; ?>
                                                <button class="button ghost" type="button" data-glossary-back>閉じる</button>
                                            </div>
                                            <h4><?= e($entry['label'] ?? '') ?></h4>
                                            <?php if (($entry['url'] ?? '') !== '' && ($entry['url'] ?? '#') !== '#'): ?>
                                                <a class="portal-manufacturer-url" href="<?= e($entry['url']) ?>" target="_blank" rel="noopener">メーカーサイトを開く</a>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($canPostToGrid): ?>
                                        <form class="portal-entry-form portal-glossary-edit-form" method="post" action="<?= route_url('grid.glossaryUpdate') ?>" hidden>
                                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="grid_id" value="<?= $manufacturerGridId ?>">
                                            <input type="hidden" name="group_index" value="<?= (int) ($entry['group_index'] ?? 0) ?>">
                                            <input type="hidden" name="entry_index" value="<?= (int) ($entry['entry_index'] ?? 0) ?>">
                                            <label class="manufacturer-name-line">
                                                <span class="field-title-line">
                                                    <span>メーカー名</span>
                                                    <span class="inline-check">
                                                        <input type="checkbox" name="manufacturer_use_name_index" value="1" <?= !empty($entry['use_name_index']) ? 'checked' : '' ?>>
                                                        <span>検索で使用</span>
                                                    </span>
                                                </span>
                                                <input name="manufacturer_name" value="<?= e($entry['label'] ?? '') ?>" required>
                                            </label>
                                            <label>
                                                <span>読み（検索用）</span>
                                                <input name="manufacturer_reading" value="<?= e($entry['reading'] ?? '') ?>" placeholder="例: フクシマガリレイ">
                                            </label>
                                            <label>
                                                <span>URL</span>
                                                <input name="manufacturer_url" type="url" value="<?= e($entry['url'] ?? '') ?>" required>
                                            </label>
                                            <div class="form-actions">
                                                <button class="button primary" type="submit">更新</button>
                                                <button class="button ghost" type="button" data-glossary-edit-cancel>戻る</button>
                                            </div>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </dialog>
                        </div>
                    <?php elseif (($grid['registration_type'] ?? '') === 'glossary'): ?>
                        <?php
                        $glossaryEntries = [];
                        foreach (($grid['groups'] ?? []) as $groupIndex => $group) {
                            foreach (($group['entries'] ?? []) as $entryIndex => $entry) {
                                $term = trim((string) ($entry['label'] ?? ''));
                                if ($term === '') {
                                    continue;
                                }
                                $reading = trim((string) ($entry['reading'] ?? ''));
                                $entry['index_key'] = $glossaryIndexKey($reading !== '' ? $reading : $term);
                                $entry['group_index'] = $groupIndex;
                                $entry['entry_index'] = $entryIndex;
                                $glossaryEntries[] = $entry;
                            }
                        }
                        usort($glossaryEntries, static function (array $a, array $b): int {
                            $aReading = trim((string) ($a['reading'] ?? ''));
                            $bReading = trim((string) ($b['reading'] ?? ''));
                            return strcmp($aReading !== '' ? $aReading : (string) ($a['label'] ?? ''), $bReading !== '' ? $bReading : (string) ($b['label'] ?? ''));
                        });
                        $availableGlossaryKeys = [];
                        foreach ($glossaryEntries as $entry) {
                            $availableGlossaryKeys[(string) ($entry['index_key'] ?? 'その他')] = true;
                        }
                        $glossaryGridId = (int) ($grid['id'] ?? 0);
                        $glossaryBrowserId = 'portal-glossary-browser-' . $glossaryGridId;
                        ?>
                        <div class="portal-glossary" data-glossary-grid="<?= $glossaryGridId ?>">
                            <div class="portal-glossary-index">
                                <button type="button" data-open-glossary-browser="<?= e($glossaryBrowserId) ?>" data-glossary-filter="all">ALL</button>
                                <?php foreach ($glossaryIndexLabels as $indexLabel): ?>
                                    <button type="button" data-open-glossary-browser="<?= e($glossaryBrowserId) ?>" data-glossary-filter="<?= e($indexLabel) ?>" <?= empty($availableGlossaryKeys[$indexLabel]) ? 'disabled' : '' ?>><?= e($indexLabel) ?></button>
                                <?php endforeach; ?>
                            </div>
                            <dialog class="portal-modal portal-glossary-modal" id="<?= e($glossaryBrowserId) ?>">
                                <div class="portal-modal-panel">
                                    <div class="portal-modal-heading section-<?= e($grid['tone'] ?? 'green') ?>">
                                        <h3><span data-glossary-active-index>ALL</span> / <?= e($grid['title'] ?? '') ?></h3>
                                        <button type="button" aria-label="閉じる" data-close-dialog>×</button>
                                    </div>
                                    <div class="portal-modal-body portal-glossary-browser">
                                        <div class="portal-glossary-list" data-glossary-list>
                                <?php foreach ($glossaryEntries as $glossaryIndex => $entry): ?>
                                    <?php
                                    $glossaryDialogId = 'portal-glossary-detail-' . $glossaryGridId . '-' . $glossaryIndex;
                                    $glossaryImageUrl = isset($entry['file_id']) ? route_url('grid.file', ['grid_id' => $glossaryGridId, 'file_id' => $entry['file_id']]) : '';
                                    ?>
                                    <button class="portal-glossary-term" type="button" data-glossary-key="<?= e($entry['index_key'] ?? 'その他') ?>" data-glossary-detail="<?= e($glossaryDialogId) ?>">
                                        <span><?= e($entry['label'] ?? '') ?></span>
                                        <?php if (($entry['reading'] ?? '') !== ''): ?>
                                            <small><?= e($entry['reading']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($isNewEntry($entry)): ?>
                                            <span class="portal-new-badge">NEW</span>
                                        <?php endif; ?>
                                        <?php if ($glossaryImageUrl !== ''): ?>
                                            <span class="portal-glossary-photo" aria-hidden="true"></span>
                                        <?php endif; ?>
                                    </button>
                                    <div class="portal-glossary-detail" id="<?= e($glossaryDialogId) ?>" hidden>
                                        <div data-glossary-detail-view>
                                            <div class="portal-glossary-detail-head">
                                                <?php if ($canPostToGrid): ?>
                                                    <button class="button ghost" type="button" data-glossary-edit>編集</button>
                                                <?php endif; ?>
                                                <button class="button ghost" type="button" data-glossary-back>閉じる</button>
                                            </div>
                                            <h4><?= e($entry['label'] ?? '') ?></h4>
                                            <?php if (($entry['reading'] ?? '') !== ''): ?>
                                                <small><?= e($entry['reading']) ?></small>
                                            <?php endif; ?>
                                            <?php if (($entry['description'] ?? '') !== ''): ?>
                                                <p><?= nl2br(e($entry['description'])) ?></p>
                                            <?php endif; ?>
                                            <?php if ($glossaryImageUrl !== ''): ?>
                                                <img class="portal-expanded-image portal-photo-image" src="<?= e($glossaryImageUrl) ?>" alt="">
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($canPostToGrid): ?>
                                        <form class="portal-entry-form portal-glossary-edit-form" method="post" action="<?= route_url('grid.glossaryUpdate') ?>" enctype="multipart/form-data" hidden>
                                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="grid_id" value="<?= $glossaryGridId ?>">
                                            <input type="hidden" name="group_index" value="<?= (int) ($entry['group_index'] ?? 0) ?>">
                                            <input type="hidden" name="entry_index" value="<?= (int) ($entry['entry_index'] ?? 0) ?>">
                                            <label>
                                                <span>用語</span>
                                                <input name="glossary_term" value="<?= e($entry['label'] ?? '') ?>" required>
                                            </label>
                                            <label>
                                                <span>読み</span>
                                                <input name="glossary_reading" value="<?= e($entry['reading'] ?? '') ?>" placeholder="例: サキイレサキダシ">
                                            </label>
                                            <label>
                                                <span>説明</span>
                                                <textarea name="glossary_description" rows="5" required><?= e($entry['description'] ?? '') ?></textarea>
                                            </label>
                                            <label>
                                                <span>写真</span>
                                                <?php if ($glossaryImageUrl !== ''): ?>
                                                    <img class="portal-upload-preview" src="<?= e($glossaryImageUrl) ?>" alt="" data-upload-preview>
                                                    <div class="portal-check-row">
                                                        <input type="checkbox" name="delete_glossary_image" value="1">
                                                        <span>登録済み写真を削除</span>
                                                    </div>
                                                <?php else: ?>
                                                    <img class="portal-upload-preview" src="" alt="" data-upload-preview hidden>
                                                <?php endif; ?>
                                                <input type="file" name="glossary_image" accept="image/*" data-preview-upload>
                                            </label>
                                            <div class="form-actions">
                                                <button class="button primary" type="submit">更新</button>
                                                <button class="button ghost" type="button" data-glossary-edit-cancel>戻る</button>
                                            </div>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                                    </div>
                                </div>
                            </dialog>
                        </div>
                    <?php elseif (($grid['registration_type'] ?? '') === 'todo'): ?>
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
                                            $fileName = (string) ($entry['original_name'] ?? $entry['label'] ?? '');
                                            $isExcelEntry = isset($entry['file_id']) && preg_match('/\.(xlsx|xls)$/i', $fileName) === 1;
                                            $entryUrl = isset($entry['file_id'])
                                                ? route_url($isExcelEntry ? 'grid.excelViewer' : 'grid.file', ['grid_id' => (int) $grid['id'], 'file_id' => $entry['file_id']])
                                                : (($qrCodeUrlMap[$qrCodeId] ?? null) ?: ($entry['url'] ?? '#'));
                                            $isQrEntry = !isset($entry['file_id']) && ($qrCodeId > 0 && !empty($qrCodeUrlMap[$qrCodeId]) || strpos((string) $entryUrl, '/uploads/qr-codes/') !== false);
                                            ?>
                                            <a class="portal-link-card <?= isset($entry['file_id']) ? 'is-file' : ($isQrEntry ? 'is-qr' : 'is-link') ?>" href="<?= e($entryUrl) ?>" <?= !$isQrEntry ? 'target="_blank" rel="noopener"' : '' ?> <?= $isQrEntry ? 'data-qr-image-url="' . e($entryUrl) . '" data-qr-title="' . e($entry['label'] ?? '') . '" data-qr-tone="' . e($grid['tone'] ?? 'green') . '"' : '' ?>>
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
                                    $fileName = (string) ($entry['original_name'] ?? $entry['label'] ?? '');
                                    $isExcelEntry = isset($entry['file_id']) && preg_match('/\.(xlsx|xls)$/i', $fileName) === 1;
                                    $entryUrl = isset($entry['file_id'])
                                        ? route_url($isExcelEntry ? 'grid.excelViewer' : 'grid.file', ['grid_id' => (int) $grid['id'], 'file_id' => $entry['file_id']])
                                        : (($qrCodeUrlMap[$qrCodeId] ?? null) ?: ($entry['url'] ?? '#'));
                                    $isQrEntry = !isset($entry['file_id']) && ($qrCodeId > 0 && !empty($qrCodeUrlMap[$qrCodeId]) || strpos((string) $entryUrl, '/uploads/qr-codes/') !== false);
                                    ?>
                                    <a class="portal-link-card <?= isset($entry['file_id']) ? 'is-file' : ($isQrEntry ? 'is-qr' : 'is-link') ?>" href="<?= e($entryUrl) ?>" <?= !$isQrEntry ? 'target="_blank" rel="noopener"' : '' ?> <?= $isQrEntry ? 'data-qr-image-url="' . e($entryUrl) . '" data-qr-title="' . e($entry['label'] ?? '') . '" data-qr-tone="' . e($grid['tone'] ?? 'green') . '"' : '' ?>>
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
                                    <?php elseif (($grid['registration_type'] ?? '') === 'glossary'): ?>
                                        <label>
                                            <span>用語</span>
                                            <input name="glossary_term" required>
                                        </label>
                                        <label>
                                            <span>読み</span>
                                            <input name="glossary_reading" placeholder="例: サキイレサキダシ">
                                        </label>
                                        <label>
                                            <span>説明</span>
                                            <textarea name="glossary_description" rows="5" required></textarea>
                                        </label>
                                        <label>
                                            <span>写真</span>
                                            <img class="portal-upload-preview" src="" alt="" data-upload-preview hidden>
                                            <input type="file" name="glossary_image" accept="image/*" data-preview-upload>
                                        </label>
                                    <?php elseif (($grid['registration_type'] ?? '') === 'manufacturer_links'): ?>
                                        <label class="manufacturer-name-line">
                                            <span class="field-title-line">
                                                <span>メーカー名</span>
                                                <span class="inline-check">
                                                    <input type="checkbox" name="manufacturer_use_name_index" value="1">
                                                    <span>検索で使用</span>
                                                </span>
                                            </span>
                                            <input name="manufacturer_name" required>
                                        </label>
                                        <label>
                                            <span>読み（検索用）</span>
                                            <input name="manufacturer_reading" placeholder="例: フクシマガリレイ">
                                        </label>
                                        <label>
                                            <span>URL</span>
                                            <input name="manufacturer_url" type="url" placeholder="https://example.com" required>
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
        const glossaryItemHasKey = (item, key) => {
            if (key === 'all') {
                return true;
            }
            const keys = (item.dataset.glossaryKeys || item.dataset.glossaryKey || '').split(/\s+/).filter(Boolean);
            return keys.includes(key);
        };

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

        const glossaryOpener = event.target.closest('[data-open-glossary-browser]');
        if (glossaryOpener) {
            const dialog = document.getElementById(glossaryOpener.dataset.openGlossaryBrowser);
            const key = glossaryOpener.dataset.glossaryFilter || 'all';
            const activeIndex = dialog?.querySelector('[data-glossary-active-index]');
            if (activeIndex) {
                activeIndex.textContent = key === 'all' ? 'ALL' : key;
            }
            if (dialog) {
                dialog.dataset.glossaryActiveKey = key;
            }
            dialog?.querySelectorAll('[data-glossary-key]').forEach((item) => {
                item.hidden = !glossaryItemHasKey(item, key);
                item.classList.remove('is-selected');
            });
            dialog?.querySelectorAll('.portal-glossary-detail').forEach((detail) => {
                detail.hidden = true;
                detail.classList.remove('is-editing');
            });
            dialog?.showModal();
            return;
        }

        const glossaryTerm = event.target.closest('[data-glossary-detail]');
        if (glossaryTerm) {
            const dialog = glossaryTerm.closest('dialog');
            const detail = document.getElementById(glossaryTerm.dataset.glossaryDetail);
            dialog?.querySelectorAll('[data-glossary-key]').forEach((item) => {
                item.hidden = item !== glossaryTerm;
                item.classList.toggle('is-selected', item === glossaryTerm);
            });
            dialog?.querySelectorAll('.portal-glossary-detail').forEach((item) => {
                item.hidden = item !== detail;
                item.classList.remove('is-editing');
            });
            detail?.querySelector('[data-glossary-detail-view]')?.removeAttribute('hidden');
            detail?.querySelector('.portal-glossary-edit-form')?.setAttribute('hidden', '');
            return;
        }

        const glossaryBack = event.target.closest('[data-glossary-back]');
        if (glossaryBack) {
            const dialog = glossaryBack.closest('dialog');
            const key = dialog?.dataset.glossaryActiveKey || 'all';
            dialog?.querySelectorAll('.portal-glossary-detail').forEach((detail) => {
                detail.hidden = true;
                detail.classList.remove('is-editing');
            });
            dialog?.querySelectorAll('[data-glossary-key]').forEach((item) => {
                item.hidden = !glossaryItemHasKey(item, key);
                item.classList.remove('is-selected');
            });
            return;
        }

        const glossaryEdit = event.target.closest('[data-glossary-edit]');
        if (glossaryEdit) {
            const detail = glossaryEdit.closest('.portal-glossary-detail');
            detail?.classList.add('is-editing');
            detail?.querySelector('[data-glossary-detail-view]')?.setAttribute('hidden', '');
            detail?.querySelector('.portal-glossary-edit-form')?.removeAttribute('hidden');
            return;
        }

        const glossaryEditCancel = event.target.closest('[data-glossary-edit-cancel]');
        if (glossaryEditCancel) {
            const detail = glossaryEditCancel.closest('.portal-glossary-detail');
            detail?.classList.remove('is-editing');
            detail?.querySelector('.portal-glossary-edit-form')?.setAttribute('hidden', '');
            detail?.querySelector('[data-glossary-detail-view]')?.removeAttribute('hidden');
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
        const previewUpload = event.target.closest('[data-preview-upload]');
        if (previewUpload) {
            const form = previewUpload.closest('form');
            const preview = form?.querySelector('[data-upload-preview]');
            const deleteCheckbox = form?.querySelector('[name="delete_glossary_image"]');
            const file = previewUpload.files?.[0];
            if (file && preview) {
                preview.src = URL.createObjectURL(file);
                preview.hidden = false;
                if (deleteCheckbox) {
                    deleteCheckbox.checked = false;
                }
            }
            return;
        }

        const deleteGlossaryImage = event.target.closest('[name="delete_glossary_image"]');
        if (deleteGlossaryImage) {
            const form = deleteGlossaryImage.closest('form');
            const preview = form?.querySelector('[data-upload-preview]');
            if (preview) {
                preview.hidden = deleteGlossaryImage.checked;
            }
            return;
        }

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

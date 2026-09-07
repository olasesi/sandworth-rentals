<?php
/** @var array $pageContentBlocks */
/** @var array $portfolio */

$pageContentBlocks = isset($pageContentBlocks) ? $pageContentBlocks : array();
$activeAdminPage = 'editorial';
$adminTitle = 'Admin desk | Manage web page content.';
$adminDescription = 'Edit the structured blocks stored in page_content_blocks on a page built specifically for content operations.';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Editorial pages</span>
                <h2>Manage the page content blocks that power the public pages.</h2>
                <p class="muted-text">Each block below writes directly to <code>page_content_blocks</code>. Use valid JSON so the page templates can keep rendering structured content safely.</p>
            </div>
        </div>

        <div class="admin-content-layout">
            <aside class="detail-card compact-card admin-content-sidebar">
                <span class="eyebrow">Add or update block</span>
                <h3>New editorial block</h3>
                <p>Create another block for an existing page, or overwrite an existing block by reusing the same page key and block key.</p>

                <form action="<?= htmlspecialchars(app_url('admin-page-content-save'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-content-create-form">
                    <div class="admin-field">
                        <label for="editor-page-key">Page key</label>
                        <input id="editor-page-key" name="page_key" list="admin-page-keys" placeholder="home" required>
                        <datalist id="admin-page-keys">
                            <?php foreach ($pageContentBlocks as $pageContentGroup): ?>
                                <option value="<?= htmlspecialchars((string) $pageContentGroup['pageKey'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="admin-field">
                        <label for="editor-block-key">Block key</label>
                        <input id="editor-block-key" name="block_key" type="text" placeholder="hero" required>
                    </div>

                    <div class="admin-field">
                        <label for="editor-content-json">Content JSON</label>
                        <textarea id="editor-content-json" name="content_json" rows="10" placeholder="{&#10;  &quot;title&quot;: &quot;New section title&quot;&#10;}" required></textarea>
                    </div>

                    <button type="submit" class="solid-button">Save content block</button>
                </form>
            </aside>

            <div class="admin-content-pages">
                <?php foreach ($pageContentBlocks as $pageIndex => $pageContentGroup): ?>
                    <details class="admin-content-page" <?= $pageIndex === 0 ? 'open' : '' ?>>
                        <summary>
                            <div class="admin-content-page-heading">
                                <div>
                                    <span class="eyebrow"><?= htmlspecialchars((string) $pageContentGroup['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <strong><?= htmlspecialchars((string) $pageContentGroup['pageKey'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <span class="type-pill"><?= count($pageContentGroup['blocks']) ?> blocks</span>
                            </div>
                        </summary>

                        <div class="admin-content-blocks">
                            <?php foreach ($pageContentGroup['blocks'] as $block): ?>
                                <form action="<?= htmlspecialchars(app_url('admin-page-content-save'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-content-form">
                                    <input type="hidden" name="page_key" value="<?= htmlspecialchars((string) $block['pageKey'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="block_key" value="<?= htmlspecialchars((string) $block['blockKey'], ENT_QUOTES, 'UTF-8') ?>">

                                    <div class="admin-content-form-head">
                                        <div>
                                            <span class="eyebrow"><?= htmlspecialchars((string) $block['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <h3><?= htmlspecialchars((string) $block['blockKey'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        </div>
                                        <span class="muted-text">Updated <?= htmlspecialchars((string) $block['updatedAt'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>

                                    <div class="admin-field">
                                        <label for="content-block-<?= (int) $block['id'] ?>">Content JSON</label>
                                        <textarea id="content-block-<?= (int) $block['id'] ?>" name="content_json" rows="12" class="admin-code-field" spellcheck="false" required><?= htmlspecialchars((string) $block['contentJson'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </div>

                                    <div class="admin-form-actions">
                                        <button type="submit" class="solid-button">Save <?= htmlspecialchars((string) $block['blockKey'], ENT_QUOTES, 'UTF-8') ?></button>
                                    </div>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</section>

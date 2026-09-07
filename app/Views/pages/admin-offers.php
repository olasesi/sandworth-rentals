<?php
/** @var array $portfolio */
/** @var array $offers */
$activeAdminPage = 'offers';
$adminTitle = 'Manage buyer offers';
$adminDescription = 'Review purchase terms, respond quickly, and keep sales conversations moving.';
require __DIR__ . '/_admin_phase1_header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Offer desk</span>
                <h2>Submitted buyer offers</h2>
            </div>
        </div>

        <?php if ($offers === array()): ?>
            <article class="empty-state">
                <h2>No buyer offers yet.</h2>
                <p>New sales offers from property pages will appear here.</p>
            </article>
        <?php else: ?>
            <div class="results-list">
                <?php foreach ($offers as $offer): ?>
                    <article class="result-card">
                        <div class="result-card-body result-card-body-wide">
                            <div class="result-card-top">
                                <div>
                                    <h2><?= htmlspecialchars((string) $offer['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p class="muted-text"><?= htmlspecialchars((string) $offer['fullName'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $offer['email'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <span class="admin-status-pill <?= htmlspecialchars((string) $offer['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ucfirst($offer['status']), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p><strong>Offer:</strong> <?= htmlspecialchars(app_currency($offer['offerAmount']), ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if ($offer['timeline'] !== ''): ?>
                                <p><strong>Timeline:</strong> <?= htmlspecialchars((string) $offer['timeline'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php if ($offer['terms'] !== ''): ?>
                                <p><?= nl2br(htmlspecialchars((string) $offer['terms'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php endif; ?>
                            <form action="<?= htmlspecialchars(app_url('admin-offer-update'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="tour-request-actions">
                                <input type="hidden" name="offer_id" value="<?= (int) $offer['id'] ?>">
                                <div class="admin-field">
                                    <label for="offer-notes-<?= (int) $offer['id'] ?>">Admin notes</label>
                                    <textarea id="offer-notes-<?= (int) $offer['id'] ?>" name="admin_notes" rows="3"><?= htmlspecialchars((string) $offer['adminNotes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div class="tour-inline-actions">
                                    <button type="submit" class="ghost-button" name="status" value="reviewing">Mark reviewing</button>
                                    <button type="submit" class="ghost-button" name="status" value="countered">Counter / update</button>
                                    <button type="submit" class="solid-button" name="status" value="accepted">Accept</button>
                                    <button type="submit" class="ghost-button" name="status" value="declined">Decline</button>
                                </div>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

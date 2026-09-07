<?php
/** @var array $offers */
$offers = isset($offers) ? $offers : array();
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">Purchase offers</span>
        <h1>Review buyer offers on homes for sale.</h1>
        <p>Accept, counter, or decline buyer expressions of interest. Your response is shown to the buyer in their offer tracker.</p>
    </div>
</section>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Offers</span>
                <h2>Buyer pipeline</h2>
            </div>
        </div>

        <div class="results-list">
            <?php if ($offers === array()): ?>
                <article class="empty-state">
                    <h2>No offers yet.</h2>
                    <p>Buyer expressions of interest on homes for sale will appear here.</p>
                </article>
            <?php else: ?>
                <?php foreach ($offers as $offer): ?>
                    <article class="result-card">
                        <div class="result-card-body result-card-body-wide">
                            <div class="result-card-top">
                                <div>
                                    <h2><?= htmlspecialchars((string) $offer['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p class="muted-text"><?= htmlspecialchars((string) $offer['fullName'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $offer['email'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $offer['phone'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <span class="admin-status-pill <?= htmlspecialchars((string) $offer['status'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string) ucfirst($offer['status']), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <p><strong>Offer:</strong> <?= htmlspecialchars(app_currency($offer['offerAmount']), ENT_QUOTES, 'UTF-8') ?>
                               &nbsp;·&nbsp; Asking: <?= htmlspecialchars(app_currency($offer['property']['askingPrice']), ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if ($offer['terms'] !== ''): ?>
                                <p>Terms: <?= htmlspecialchars((string) $offer['terms'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php if ($offer['timeline'] !== ''): ?>
                                <p>Timeline: <?= htmlspecialchars((string) $offer['timeline'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <p class="muted-text" style="font-size:.8rem">Submitted: <?= htmlspecialchars((string) $offer['submittedAt'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if ($offer['adminNotes'] !== ''): ?>
                                <p>Previous response: <?= htmlspecialchars((string) $offer['adminNotes'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <form action="<?= htmlspecialchars(app_url('admin-offer-update'), ENT_QUOTES, 'UTF-8') ?>" method="post" style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end">
                                <input type="hidden" name="offer_id" value="<?= (int) $offer['id'] ?>">
                                <div class="admin-field" style="flex:1; min-width:180px">
                                    <label for="offer-status-<?= (int) $offer['id'] ?>">Decision</label>
                                    <select id="offer-status-<?= (int) $offer['id'] ?>" name="status">
                                        <option value="accepted" <?= $offer['status'] === 'accepted' ? 'selected' : '' ?>>Accept</option>
                                        <option value="countered" <?= $offer['status'] === 'countered' ? 'selected' : '' ?>>Counter offer</option>
                                        <option value="rejected" <?= $offer['status'] === 'rejected' ? 'selected' : '' ?>>Decline</option>
                                    </select>
                                </div>
                                <div class="admin-field" style="flex:2; min-width:240px">
                                    <label for="offer-notes-<?= (int) $offer['id'] ?>">Response to buyer</label>
                                    <input id="offer-notes-<?= (int) $offer['id'] ?>" name="admin_notes" type="text" placeholder="e.g. We accept — confirm financing within 30 days.">
                                </div>
                                <button type="submit" class="solid-button">Update offer</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</section>

<?php
/** @var array $offers */
$offers = isset($offers) ? $offers : array();
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">My offers</span>
        <h1>Your purchase offers.</h1>
        <p>Track the offers you've made on homes for sale and any responses from the sales desk.</p>
    </div>
</section>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Offers</span>
            <h2><?= count($offers) ?> offer<?= count($offers) === 1 ? '' : 's' ?> submitted</h2>
        </div>
        <a class="ghost-button" href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>"
           style="background:transparent; border-color:var(--line); color:var(--muted)">Browse more homes</a>
    </div>

    <?php if ($offers === array()): ?>
        <article class="empty-state">
            <h2>No offers yet.</h2>
            <p>Open a home for sale and submit your offer amount, terms, and timeline.</p>
            <a class="solid-button" href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>" style="margin-top:20px">Browse homes for sale</a>
        </article>
    <?php else: ?>
        <div class="results-list">
            <?php foreach ($offers as $offer): ?>
                <article class="result-card">
                    <div class="result-card-body result-card-body-wide">
                        <div class="result-card-top">
                            <div>
                                <h2><?= htmlspecialchars((string) $offer['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="muted-text"><?= htmlspecialchars((string) $offer['property']['location'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="admin-status-pill <?= htmlspecialchars((string) $offer['status'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) ucfirst($offer['status']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <p><strong>Your offer:</strong> <?= htmlspecialchars(app_currency($offer['offerAmount']), ENT_QUOTES, 'UTF-8') ?>
                           &nbsp;·&nbsp; Asking: <?= htmlspecialchars(app_currency($offer['property']['askingPrice']), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if ($offer['terms'] !== ''): ?>
                            <p>Terms: <?= htmlspecialchars((string) $offer['terms'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ($offer['timeline'] !== ''): ?>
                            <p>Timeline: <?= htmlspecialchars((string) $offer['timeline'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ($offer['adminNotes'] !== ''): ?>
                            <p style="color:var(--primary)">Sales desk response: <?= htmlspecialchars((string) $offer['adminNotes'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <p class="muted-text" style="font-size:.8rem; margin-bottom:0">Submitted: <?= htmlspecialchars((string) $offer['submittedAt'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
/** @var array $favorites */
$favorites = isset($favorites) ? $favorites : array();
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">Saved properties</span>
        <h1>Your shortlist.</h1>
        <p>Homes, rentals, and commercial spaces you saved while browsing. Remove any that no longer fit.</p>
    </div>
</section>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Shortlist</span>
            <h2><?= count($favorites) ?> saved listing<?= count($favorites) === 1 ? '' : 's' ?></h2>
        </div>
        <a class="ghost-button" href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>"
           style="background:transparent; border-color:var(--line); color:var(--muted)">Browse homes</a>
    </div>

    <?php if ($favorites === array()): ?>
        <article class="empty-state">
            <h2>Nothing saved yet.</h2>
            <p>Open any listing and tap "Save" to keep it here for later.</p>
            <a class="solid-button" href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>" style="margin-top:20px">Start browsing</a>
        </article>
    <?php else: ?>
        <div class="listing-grid">
            <?php foreach ($favorites as $favorite): ?>
                <?php $savedListing = $favorite['property']; ?>
                <div class="listing-card">
                    <a style="display:block" href="<?= htmlspecialchars(app_url('property', array('id' => $savedListing['id'])), ENT_QUOTES, 'UTF-8') ?>">
                        <div style="overflow:hidden">
                            <img src="<?= htmlspecialchars((string) $savedListing['image'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars((string) $savedListing['title'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="listing-body">
                            <p class="listing-price"><?= htmlspecialchars((string) $savedListing['price'], ENT_QUOTES, 'UTF-8') ?></p>
                            <h3><?= htmlspecialchars((string) $savedListing['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="muted-text"><?= htmlspecialchars((string) $savedListing['location'], ENT_QUOTES, 'UTF-8') ?></p>
                            <span class="market-listing-link">View details &rarr;</span>
                        </div>
                    </a>
                    <form action="<?= htmlspecialchars(app_url('favorite-toggle'), ENT_QUOTES, 'UTF-8') ?>" method="post" style="padding:0 14px 14px">
                        <input type="hidden" name="property_id" value="<?= (int) $savedListing['id'] ?>">
                        <button type="submit" class="ghost-button" style="width:100%; justify-content:center; border-color:var(--line); color:var(--muted); background:transparent">Remove from saved</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

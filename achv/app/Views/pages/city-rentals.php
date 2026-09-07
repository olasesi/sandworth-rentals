<?php
/** @var array $listings */
/** @var array $marketStats */
/** @var array $nearbyMarkets */
/** @var string $marketName */
/** @var array $content */

$hero = isset($content['hero']) ? $content['hero'] : array();
$chips = isset($content['chips']) && is_array($content['chips']) ? $content['chips'] : array();
$nearbySection = isset($content['nearbySection']) ? $content['nearbySection'] : array();
$nearbyEyebrow = isset($nearbySection['eyebrow']) ? $nearbySection['eyebrow'] : 'Explore nearby';
$nearbyTitle = isset($nearbySection['title']) ? $nearbySection['title'] : 'Nearby neighbourhoods';
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow"><?= htmlspecialchars((string) $hero['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
        <h1><?= htmlspecialchars($marketName, ENT_QUOTES, 'UTF-8') ?> rentals &amp; apartments</h1>
        <p><?= htmlspecialchars((string) $hero['description'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</section>

<section class="manager-stats" style="margin:24px 0">
    <article>
        <span>Average rent</span>
        <strong><?= htmlspecialchars((string) $marketStats['averageRent'], ENT_QUOTES, 'UTF-8') ?></strong>
    </article>
    <article>
        <span>New listings</span>
        <strong><?= htmlspecialchars((string) $marketStats['newListings'], ENT_QUOTES, 'UTF-8') ?></strong>
    </article>
    <article>
        <span>Tour-ready rentals</span>
        <strong><?= htmlspecialchars((string) $marketStats['tourReady'], ENT_QUOTES, 'UTF-8') ?></strong>
    </article>
    <article>
        <span>Active listings</span>
        <strong><?= count($listings) ?></strong>
    </article>
</section>

<section class="section-block section-tight">
    <div class="results-header">
        <div>
            <strong><?= count($listings) ?></strong>
            <span>rentals available in <?= htmlspecialchars($marketName, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <a class="ghost-button" href="<?= htmlspecialchars(app_url('rentals', array('city' => $marketName)), ENT_QUOTES, 'UTF-8') ?>">Open filter view</a>
    </div>

    <?php if ($chips !== array()): ?>
        <div class="chip-row" style="margin-top:12px">
            <?php foreach ($chips as $chip): ?>
                <span><?= htmlspecialchars((string) $chip, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="search-layout-map">
    <div class="results-panel" id="results-list-city-rentals">
        <div class="results-list" style="padding-bottom:32px">
            <?php if ($listings === array()): ?>
                <article class="empty-state">
                    <h2>No listings found in <?= htmlspecialchars($marketName, ENT_QUOTES, 'UTF-8') ?>.</h2>
                    <p>Check back soon or browse the wider rental market.</p>
                </article>
            <?php endif; ?>

            <?php foreach ($listings as $listing): ?>
                <?php
                ?>
                <article class="result-card" data-listing-id="<?= (int) $listing['id'] ?>">
                    <img src="<?= htmlspecialchars((string) $listing['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="result-card-body">
                        <div class="result-card-top">
                            <div>
                                <p class="listing-price"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?></p>
                                <h2><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="muted-text"><?= htmlspecialchars((string) $listing['location'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="type-pill"><?= htmlspecialchars((string) $listing['type'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <p class="listing-meta">
                            <?= htmlspecialchars((string) $listing['beds'], ENT_QUOTES, 'UTF-8') ?> bd |
                            <?= htmlspecialchars((string) $listing['baths'], ENT_QUOTES, 'UTF-8') ?> ba |
                            <?= htmlspecialchars((string) $listing['area'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <p><?= htmlspecialchars((string) $listing['summary'], ENT_QUOTES, 'UTF-8') ?></p>

                        <div class="badge-row">
                            <?php foreach ($listing['badges'] as $badge): ?>
                                <span><?= htmlspecialchars((string) $badge, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="result-actions">
                            <span class="muted-text"><?= htmlspecialchars((string) $listing['availableDate'], ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="result-action-links">
                                <?= app_share_button_markup($listing) ?>
                                <a class="text-link" href="<?= htmlspecialchars(app_property_url($listing), ENT_QUOTES, 'UTF-8') ?>">See rental</a>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="map-pane">
        <div class="map-canvas" data-mode="search" data-zoom="11" data-list-panel="#results-list-city-rentals" data-pins="<?= app_map_payload($listings) ?>"></div>
    </div>
</section>

<?php if ($nearbyMarkets !== array()): ?>
    <section class="section-block">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><?= htmlspecialchars((string) $nearbyEyebrow, ENT_QUOTES, 'UTF-8') ?></span>
                <h2><?= htmlspecialchars((string) $nearbyTitle, ENT_QUOTES, 'UTF-8') ?></h2>
            </div>
        </div>

        <div class="chip-row">
            <?php foreach ($nearbyMarkets as $nearby): ?>
                <a class="ghostlower-button" href="<?= htmlspecialchars(app_url('city-rentals', array('market' => $nearby)), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string) $nearby, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php

/** @var array<int, array<string, mixed>> $featuredHomes */
/** @var array<int, array<string, mixed>> $featuredRentals */
/** @var array<int, array<string, mixed>> $featuredCommercial */
/** @var array<int, array<string, mixed>> $freshListings */
/** @var array<int, array<string, mixed>> $marketSpotlights */
/** @var array<string, int> $stats */
/** @var array<string, mixed> $content */

$hero = isset($content['hero']) ? $content['hero'] : array();
$statsContent = isset($content['stats']) ? $content['stats'] : array();
$homesShowcase = isset($content['homesShowcase']) ? $content['homesShowcase'] : array();
$rentalsShowcase = isset($content['rentalsShowcase']) ? $content['rentalsShowcase'] : array();
$activeRentalsLabel = isset($statsContent['activeRentalsLabel']) ? $statsContent['activeRentalsLabel'] : 'Active rentals';
$preQualifiedRentersLabel = isset($statsContent['preQualifiedRentersLabel']) ? $statsContent['preQualifiedRentersLabel'] : 'Verified renters';
$avgDaysToLeaseLabel = isset($statsContent['avgDaysToLeaseLabel']) ? $statsContent['avgDaysToLeaseLabel'] : 'Average days to lease';
$homesEyebrow = isset($homesShowcase['eyebrow']) ? $homesShowcase['eyebrow'] : 'Homes for sale';
$homesTitle = isset($homesShowcase['title']) ? $homesShowcase['title'] : 'Featured homes';
$homesLinkLabel = isset($homesShowcase['linkLabel']) ? $homesShowcase['linkLabel'] : 'See all homes';
$rentalsEyebrow = isset($rentalsShowcase['eyebrow']) ? $rentalsShowcase['eyebrow'] : 'Featured rentals';
$rentalsTitle = isset($rentalsShowcase['title']) ? $rentalsShowcase['title'] : 'Featured rentals';
$rentalsLinkLabel = isset($rentalsShowcase['linkLabel']) ? $rentalsShowcase['linkLabel'] : 'See all rentals';
?>
<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow"><?= htmlspecialchars((string) $hero['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
        <h1><?= htmlspecialchars((string) $hero['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars((string) $hero['description'], ENT_QUOTES, 'UTF-8') ?></p>

        <div class="tab-search hero-tabs" role="tablist" aria-label="Choose what to search">
            <button type="button" class="tab-search-item is-active" data-target="rentals" data-field="city" data-field-label="City, district, or estate">Rent</button>
            <button type="button" class="tab-search-item" data-target="homes" data-field="location" data-field-label="City, district, or estate">Buy</button>
            <button type="button" class="tab-search-item" data-target="commercial" data-field="location" data-field-label="District, landmark, or area">Malls &amp; Shops</button>
        </div>

        <form action="" method="get" class="hero-search" id="hero-search-form">
            <input type="hidden" name="page" value="rentals" id="hero-page-input">
            <label class="sr-only" for="hero-search-field">Search location</label>
            <input id="hero-search-field" name="city" type="text" placeholder="City, district, estate, or landmark">

            <label class="sr-only" for="beds">Bedrooms or type</label>
            <select id="beds" name="beds">
                <option value="">Any beds</option>
                <option value="1">1+ bed</option>
                <option value="2">2+ beds</option>
                <option value="3">3+ beds</option>
                <option value="4">4+ beds</option>
            </select>

            <button type="submit" id="hero-search-submit">Search rentals</button>
        </form>

        <?php if ($marketSpotlights !== array()): ?>
            <div class="hero-market-strip">
                <?php foreach (array_slice($marketSpotlights, 0, 5) as $market): ?>
                    <?php
                        $heroMarketLink = (int) $market['rentCount'] > 0
                            ? app_url('city-rentals', array('market' => $market['name']))
                            : ((int) $market['saleCount'] > 0
                                ? app_url('homes', array('location' => $market['name']))
                                : app_url('commercial', array('location' => $market['name'])));
                    ?>
                    <a href="<?= htmlspecialchars($heroMarketLink, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) $market['name'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php if ($marketSpotlights !== array()): ?>
    <section class="section-block market-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Browse by Featured Homes</span>
                <h2>Open a neighborhood and see live rentals, homes, and commercial spaces.</h2>
            </div>
            <a class="text-link" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>">Open all rentals</a>
        </div>

        <div class="market-grid">
            <?php foreach ($marketSpotlights as $market): ?>
                <?php
                    $marketParts = array();
                    $marketLink = (int) $market['rentCount'] > 0
                        ? app_url('city-rentals', array('market' => $market['name']))
                        : ((int) $market['saleCount'] > 0
                            ? app_url('homes', array('location' => $market['name']))
                            : app_url('commercial', array('location' => $market['name'])));

                    if ((int) $market['rentCount'] > 0) {
                        $marketParts[] = (int) $market['rentCount'] . ' rentals';
                    }

                    if ((int) $market['saleCount'] > 0) {
                        $marketParts[] = (int) $market['saleCount'] . ' homes';
                    }

                    if ((int) $market['commercialCount'] > 0) {
                        $marketParts[] = (int) $market['commercialCount'] . ' commercial';
                    }
                ?>
                <a class="market-card" href="<?= htmlspecialchars($marketLink, ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars((string) $market['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $market['name'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="market-card-body">
                        <span class="eyebrow">Featured Properties</span>
                        <h3><?= htmlspecialchars((string) $market['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars(implode(' | ', $marketParts), ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="market-card-meta">
                            <strong><?= htmlspecialchars((string) $market['startingPrice'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span>Open Rentals</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($freshListings !== array()): ?>
    <section class="section-block fresh-listings-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Fresh listings</span>
                <h2>Every property card opens its own full detail page.</h2>
            </div>
        </div>

        <div class="market-listing-grid">
            <?php foreach (array_slice($freshListings, 0, 6) as $listing): ?>
                <?php
                    $purposeLabel = $listing['purpose'] === 'sale'
                        ? 'For sale'
                        : ($listing['purpose'] === 'commercial' ? 'Commercial lease' : 'For rent');
                    $imageCount = isset($listing['images']) && is_array($listing['images']) ? count($listing['images']) : 1;
                    $listingUrl = app_property_url($listing);
                ?>
                <article class="market-listing-card">
                    <a class="market-listing-media" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <img src="<?= htmlspecialchars((string) $listing['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="market-listing-badges">
                            <span class="type-pill"><?= htmlspecialchars($purposeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="type-pill"><?= (int) $imageCount ?> photos</span>
                        </div>
                    </a>
                    <div class="market-listing-body">
                        <p class="listing-price"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?></p>
                        <h3><a class="listing-card-title-link" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                        <p class="muted-text"><?= htmlspecialchars((string) $listing['location'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="listing-meta">
                            <?= htmlspecialchars((string) $listing['beds'], ENT_QUOTES, 'UTF-8') ?> bd |
                            <?= htmlspecialchars((string) $listing['baths'], ENT_QUOTES, 'UTF-8') ?> ba |
                            <?= htmlspecialchars((string) $listing['area'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p><?= htmlspecialchars((string) $listing['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="listing-card-actions">
                            <?= app_share_button_markup($listing) ?>
                            <a class="market-listing-link" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>">Open property details</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($featuredHomes !== array()): ?>
    <section class="section-block">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><?= htmlspecialchars((string) $homesEyebrow, ENT_QUOTES, 'UTF-8') ?></span>
                <h2><?= htmlspecialchars((string) $homesTitle, ENT_QUOTES, 'UTF-8') ?></h2>
            </div>
            <a class="text-link" href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $homesLinkLabel, ENT_QUOTES, 'UTF-8') ?></a>
        </div>

        <div class="listing-grid">
            <?php foreach ($featuredHomes as $listing): ?>
                <?php
                    $listingUrl = app_property_url($listing);
                ?>
                <article class="listing-card">
                    <a class="listing-card-media" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <img src="<?= htmlspecialchars((string) $listing['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
                    </a>
                    <div class="listing-body">
                        <span class="type-pill">For sale</span>
                        <p class="listing-price"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?></p>
                        <h3><a class="listing-card-title-link" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                        <p class="listing-meta">
                            <?= htmlspecialchars((string) $listing['beds'], ENT_QUOTES, 'UTF-8') ?> bd |
                            <?= htmlspecialchars((string) $listing['baths'], ENT_QUOTES, 'UTF-8') ?> ba |
                            <?= htmlspecialchars((string) $listing['area'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p><?= htmlspecialchars((string) $listing['location'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="listing-card-actions">
                            <?= app_share_button_markup($listing) ?>
                            <a class="market-listing-link" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>">View details</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($featuredRentals !== array()): ?>
    <section class="section-block">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><?= htmlspecialchars((string) $rentalsEyebrow, ENT_QUOTES, 'UTF-8') ?></span>
                <h2><?= htmlspecialchars((string) $rentalsTitle, ENT_QUOTES, 'UTF-8') ?></h2>
            </div>
            <a class="text-link" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $rentalsLinkLabel, ENT_QUOTES, 'UTF-8') ?></a>
        </div>

        <div class="listing-grid">
            <?php foreach ($featuredRentals as $listing): ?>
                <?php
                    $listingUrl = app_property_url($listing);
                ?>
                <article class="listing-card">
                    <a class="listing-card-media" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <img src="<?= htmlspecialchars((string) $listing['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
                    </a>
                    <div class="listing-body">
                        <div class="badge-row">
                            <?php foreach ($listing['badges'] as $badge): ?>
                                <span><?= htmlspecialchars((string) $badge, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>
                        <p class="listing-price"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?></p>
                        <h3><a class="listing-card-title-link" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                        <p class="listing-meta">
                            <?= htmlspecialchars((string) $listing['beds'], ENT_QUOTES, 'UTF-8') ?> bd |
                            <?= htmlspecialchars((string) $listing['baths'], ENT_QUOTES, 'UTF-8') ?> ba |
                            <?= htmlspecialchars((string) $listing['area'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p><?= htmlspecialchars((string) $listing['location'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="listing-card-actions">
                            <?= app_share_button_markup($listing) ?>
                            <a class="market-listing-link" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>">View rental details</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($featuredCommercial !== array()): ?>
    <section class="section-block">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Commercial</span>
                <h2>Malls, shops, and retail spaces</h2>
            </div>
            <a class="text-link" href="<?= htmlspecialchars(app_url('commercial'), ENT_QUOTES, 'UTF-8') ?>">See all commercial</a>
        </div>

        <div class="listing-grid">
            <?php foreach (array_slice($featuredCommercial, 0, 3) as $listing): ?>
                <?php
                    $listingUrl = app_property_url($listing);
                ?>
                <article class="listing-card">
                    <a class="listing-card-media" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <img src="<?= htmlspecialchars((string) $listing['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
                    </a>
                    <div class="listing-body">
                        <span class="type-pill"><?= htmlspecialchars((string) $listing['commercialType'], ENT_QUOTES, 'UTF-8') ?></span>
                        <p class="listing-price"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?></p>
                        <h3><a class="listing-card-title-link" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                        <p><?= htmlspecialchars((string) $listing['location'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="listing-card-actions">
                            <?= app_share_button_markup($listing) ?>
                            <a class="market-listing-link" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>">View listing</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

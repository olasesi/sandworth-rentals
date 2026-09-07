<?php
/** @var array $listings */
/** @var array $filters */
/** @var array $searchTips */
/** @var array $content */

$hero = isset($content['hero']) ? $content['hero'] : array();
$locationFilter = isset($filters['location']) ? $filters['location'] : '';
$commercialTypeFilter = isset($filters['commercialType']) ? $filters['commercialType'] : '';
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow"><?= htmlspecialchars((string) $hero['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
        <h1><?= htmlspecialchars((string) $hero['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars((string) $hero['description'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</section>

<section class="tab-search" aria-label="Search by listing type">
    <a class="tab-search-item" href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>">Buy</a>
    <a class="tab-search-item" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>">Rent</a>
    <a class="tab-search-item is-active" href="<?= htmlspecialchars(app_url('commercial'), ENT_QUOTES, 'UTF-8') ?>">Malls &amp; Shops</a>
</section>

<section class="search-layout-map">
    <div class="results-panel" id="results-list-commercial">
        <form method="get" class="inline-filter-bar">
            <input type="hidden" name="page" value="commercial">

            <input name="location" type="text" value="<?= htmlspecialchars((string) $locationFilter, ENT_QUOTES, 'UTF-8') ?>" placeholder="District, area, or landmark">

            <select name="commercial_type" aria-label="Commercial type">
                <option value="">Any type</option>
                <option value="Mall" <?= $commercialTypeFilter === 'Mall' ? 'selected' : '' ?>>Shopping mall</option>
                <option value="Shop" <?= $commercialTypeFilter === 'Shop' ? 'selected' : '' ?>>Shop / retail unit</option>
                <option value="Office" <?= $commercialTypeFilter === 'Office' ? 'selected' : '' ?>>Office space</option>
                <option value="Plaza" <?= $commercialTypeFilter === 'Plaza' ? 'selected' : '' ?>>Commercial plaza</option>
            </select>

            <button type="submit" class="solid-button">Apply filters</button>
        </form>

        <div class="results-header">
            <div>
                <strong><?= count($listings) ?></strong>
                <span><?= count($listings) === 1 ? 'commercial space' : 'commercial spaces' ?> available</span>
            </div>
            <div class="topbar-actions">
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>">View rentals</a>
            </div>
        </div>

        <?php if ($listings === array()): ?>
            <article class="empty-state">
                <h2>No commercial spaces matched those filters.</h2>
                <p>Try a wider location search or remove the commercial type filter.</p>
            </article>
        <?php endif; ?>

        <div class="results-list">
            <?php foreach ($listings as $listing): ?>
                <article class="result-card" data-listing-id="<?= (int) $listing['id'] ?>">
                    <img src="<?= htmlspecialchars((string) $listing['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="result-card-body">
                        <div class="result-card-top">
                            <div>
                                <p class="listing-price"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?></p>
                                <h2><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="muted-text"><?= htmlspecialchars((string) $listing['location'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="type-pill"><?= htmlspecialchars((string) $listing['commercialType'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <p class="listing-meta">
                            <?= htmlspecialchars((string) $listing['area'], ENT_QUOTES, 'UTF-8') ?> |
                            <?= htmlspecialchars((string) $listing['units'], ENT_QUOTES, 'UTF-8') ?> units |
                            <?= htmlspecialchars((string) $listing['floors'], ENT_QUOTES, 'UTF-8') ?> floors
                        </p>

                        <p><?= htmlspecialchars((string) $listing['summary'], ENT_QUOTES, 'UTF-8') ?></p>

                        <div class="badge-row">
                            <?php foreach ($listing['badges'] as $badge): ?>
                                <span><?= htmlspecialchars((string) $badge, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="result-actions">
                            <span class="muted-text"><?= htmlspecialchars((string) $listing['leaseTerm'], ENT_QUOTES, 'UTF-8') ?></span>
                            <a class="text-link" href="<?= htmlspecialchars(app_url('property', array('id' => $listing['id'])), ENT_QUOTES, 'UTF-8') ?>">See listing</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($searchTips !== array()): ?>
            <article class="detail-card">
                <span class="eyebrow">Search tips</span>
                <ul class="feature-list">
                    <?php foreach ($searchTips as $tip): ?>
                        <li><?= htmlspecialchars((string) $tip, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </article>
        <?php endif; ?>
    </div>

    <div class="map-pane">
        <div class="map-canvas" data-mode="search" data-zoom="11" data-list-panel="#results-list-commercial" data-pins="<?= app_map_payload($listings) ?>"></div>
    </div>
</section>

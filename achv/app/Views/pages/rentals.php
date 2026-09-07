<?php
/** @var array<int, array<string, mixed>> $listings */
/** @var array<string, mixed> $filters */
/** @var array<string, mixed> $content */

$hero = isset($content['hero']) ? $content['hero'] : array();
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
    <a class="tab-search-item is-active" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>">Rent</a>
    <a class="tab-search-item" href="<?= htmlspecialchars(app_url('commercial'), ENT_QUOTES, 'UTF-8') ?>">Malls &amp; Shops</a>
</section>

<section class="search-layout-map">
    <div class="results-panel" id="results-list-rentals">

        <!-- Filter bar with price range + sort -->
        <form method="get" class="inline-filter-bar" id="rental-filter-form">
            <input type="hidden" name="page" value="rentals">

            <input name="city" type="text"
                value="<?= htmlspecialchars((string) $filters['city'], ENT_QUOTES, 'UTF-8') ?>"
                placeholder="City, district, or estate">

            <select name="beds" aria-label="Minimum bedrooms">
                <option value="">Any beds</option>
                <option value="1" <?= $filters['beds'] === '1' ? 'selected' : '' ?>>1+ bed</option>
                <option value="2" <?= $filters['beds'] === '2' ? 'selected' : '' ?>>2+ beds</option>
                <option value="3" <?= $filters['beds'] === '3' ? 'selected' : '' ?>>3+ beds</option>
                <option value="4" <?= $filters['beds'] === '4' ? 'selected' : '' ?>>4+ beds</option>
            </select>

            <!-- <label class="check-row">
                <input type="checkbox" name="pet_friendly" value="1" <?= $filters['petFriendly'] ? 'checked' : '' ?>>
                <span>Pet-friendly</span>
            </label> -->

            <button type="submit" class="solid-button">Search</button>

            <!-- Price range row -->
            <div class="price-range-row">
                <label>Price range (₦/yr):</label>
                <input type="number" name="price_min"
                    value="<?= isset($filters['priceMin']) ? (int)$filters['priceMin'] : '' ?>"
                    placeholder="Min price" min="0" step="100000">
                <span class="range-sep">—</span>
                <input type="number" name="price_max"
                    value="<?= isset($filters['priceMax']) ? (int)$filters['priceMax'] : '' ?>"
                    placeholder="Max price" min="0" step="100000">

                <!-- Sort -->
                <label style="margin-left:12px">Sort:</label>
                <select name="sort" aria-label="Sort by">
                    <option value="">Recommended</option>
                    <option value="price_asc"  <?= (isset($filters['sort']) && $filters['sort'] === 'price_asc')  ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= (isset($filters['sort']) && $filters['sort'] === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="newest"     <?= (isset($filters['sort']) && $filters['sort'] === 'newest')     ? 'selected' : '' ?>>Newest First</option>
                    <option value="beds_desc"  <?= (isset($filters['sort']) && $filters['sort'] === 'beds_desc')  ? 'selected' : '' ?>>Most Bedrooms</option>
                </select>
            </div>
        </form>

        <div class="results-header">
            <div>
                <strong><?= count($listings) ?></strong>
                <span><?= count($listings) === 1 ? 'rental' : 'rentals' ?> found</span>
            </div>
            <div class="topbar-actions">
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('city-rentals', array('market' => 'Lekki')), ENT_QUOTES, 'UTF-8') ?>">City market</a>
                <?php if (isset($currentUser['role']) && $currentUser['role'] === 'admin'): ?>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin'), ENT_QUOTES, 'UTF-8') ?>">Admin console</a>
                <?php elseif (isset($currentTenancy['id']) && (int) $currentTenancy['id'] > 0): ?>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('tenancy'), ENT_QUOTES, 'UTF-8') ?>">Manage tenancy</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($listings === []): ?>
            <article class="empty-state">
                <h2>No rentals matched those filters.</h2>
                <p>Try removing a filter or searching a broader location to see more properties.</p>
            </article>
        <?php endif; ?>

        <div class="results-list">
            <?php foreach ($listings as $listing): ?>
                <?php
                ?>
                <article class="result-card" data-listing-id="<?= (int) $listing['id'] ?>">
                    <img src="<?= htmlspecialchars((string) $listing['image'], ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
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
                            <?= htmlspecialchars((string) $listing['beds'], ENT_QUOTES, 'UTF-8') ?> bd &nbsp;·&nbsp;
                            <?= htmlspecialchars((string) $listing['baths'], ENT_QUOTES, 'UTF-8') ?> ba &nbsp;·&nbsp;
                            <?= htmlspecialchars((string) $listing['area'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <p style="font-size:.85rem;color:var(--ink-mid);margin:0 0 6px"><?= htmlspecialchars((string) $listing['summary'], ENT_QUOTES, 'UTF-8') ?></p>

                        <div class="badge-row">
                            <?php foreach ($listing['badges'] as $badge): ?>
                                <span><?= htmlspecialchars((string) $badge, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="result-actions">
                            <span class="muted-text"><?= htmlspecialchars((string) $listing['availableDate'], ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="result-action-links">
                                <?= app_share_button_markup($listing) ?>
                                <a class="text-link" href="<?= htmlspecialchars(app_property_url($listing), ENT_QUOTES, 'UTF-8') ?>">See listing &rarr;</a>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="map-pane">
        <div class="map-canvas" data-mode="search" data-zoom="11" data-list-panel="#results-list-rentals" data-pins="<?= app_map_payload($listings) ?>"></div>
    </div>
</section>

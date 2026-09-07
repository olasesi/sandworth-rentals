<?php

/** @var array<string, mixed>|null $listing */
/** @var array<int, array<string, mixed>> $similarListings */
$similarListings = isset($similarListings) ? $similarListings : array();
?>
<?php if ($listing === null): ?>
    <section class="page-hero compact">
        <div>
            <span class="eyebrow">Listing unavailable</span>
            <h1>We couldn't find that property.</h1>
            <p>The link may be outdated or the listing may have been removed.</p>
            <a class="solid-button" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>">Back to rentals</a>
        </div>
    </section>
<?php else: ?>
    <?php
        $purpose     = isset($listing['purpose']) ? $listing['purpose'] : 'rent';
        $isSale      = $purpose === 'sale';
        $isCommercial = $purpose === 'commercial';
        $isRent      = ! $isSale && ! $isCommercial;
        $isFavorite  = isset($isFavorite) ? (bool) $isFavorite : false;
        $viewerCanAct = ! empty($currentUser) && ! App\Core\Auth::isAdmin($currentUser);

        $gallery = isset($listing['images']) && is_array($listing['images']) && count($listing['images']) > 0
            ? $listing['images']
            : array($listing['image']);

        $breadcrumbPage  = $isSale ? 'homes'      : ($isCommercial ? 'commercial' : 'rentals');
        $breadcrumbLabel = $isSale ? 'Homes for sale' : ($isCommercial ? 'Malls & Shops' : 'Rentals');
        $pillLabel       = $isSale ? 'For sale' : ($isCommercial ? 'Commercial' : 'For rent');
        $pillClass       = $isSale ? 'for-sale' : ($isCommercial ? 'commercial' : '');
    ?>

    <nav class="breadcrumb-row" aria-label="Breadcrumb">
        <a href="<?= htmlspecialchars(app_url('home'), ENT_QUOTES, 'UTF-8') ?>">Home</a>
        <span>/</span>
        <a href="<?= htmlspecialchars(app_url($breadcrumbPage), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($breadcrumbLabel, ENT_QUOTES, 'UTF-8') ?></a>
        <span>/</span>
        <span class="muted-text"><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></span>
    </nav>

    <!-- ===== INTRO ===== -->
    <section class="detail-intro">
        <div class="detail-intro-copy">

            <div class="detail-intro-top">
                <span class="type-pill <?= $pillClass ?>"><?= htmlspecialchars($pillLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (count($gallery) > 1): ?>
                    <span class="type-pill" style="background:var(--line-light);color:var(--muted);border-color:var(--line)"><?= count($gallery) ?> photos</span>
                <?php endif; ?>
                <?php if (! empty($listing['petFriendly']) && $isRent): ?>
                    <span class="type-pill" style="background:#E3F5EE;color:#00875A;border-color:#00875A">Pet-friendly</span>
                <?php endif; ?>
            </div>

            <p class="listing-price" style="font-size:2rem;margin:10px 0 6px"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?>
                <?php if ($isRent && ! empty($listing['monthlyRent'])): ?>
                    <span style="font-size:1rem; color:var(--muted); font-weight:500">
                        · <?= htmlspecialchars(app_currency($listing['monthlyRent'] * 12), ENT_QUOTES, 'UTF-8') ?>/yr
                    </span>
                <?php endif; ?>
            </p>
            <h1><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="muted-text" style="font-size:.95rem;margin:0 0 12px">
                📍 <?= htmlspecialchars((string) $listing['location'], ENT_QUOTES, 'UTF-8') ?>
            </p>

            <!-- Fact strip -->
            <div class="fact-strip">
                <?php if ($isCommercial): ?>
                    <div><span>Total area</span><strong><?= htmlspecialchars((string) $listing['area'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div><span>Units</span><strong><?= htmlspecialchars((string) $listing['units'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div><span>Floors</span><strong><?= htmlspecialchars((string) $listing['floors'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div><span>Lease term</span><strong><?= htmlspecialchars((string) $listing['leaseTerm'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                <?php else: ?>
                    <div><span>Bedrooms</span><strong><?= htmlspecialchars((string) $listing['beds'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div><span>Bathrooms</span><strong><?= htmlspecialchars((string) $listing['baths'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div><span>Area</span><strong><?= htmlspecialchars((string) $listing['area'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div><span>Available</span><strong><?= htmlspecialchars((string) $listing['availableDate'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                <?php endif; ?>
            </div>

            <!-- Gallery -->
            <div class="gallery-main">
                <img id="gallery-hero-image"
                     src="<?= htmlspecialchars((string) $gallery[0], ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <?php if (count($gallery) > 1): ?>
            <div class="gallery-thumbs">
                <?php foreach (array_slice($gallery, 0, 4) as $i => $img): ?>
                    <div class="gallery-thumb <?= $i === 0 ? 'is-active' : '' ?>" data-gallery-src="<?= htmlspecialchars((string) $img, ENT_QUOTES, 'UTF-8') ?>">
                        <img src="<?= htmlspecialchars((string) $img, ENT_QUOTES, 'UTF-8') ?>"
                             alt="Photo <?= $i + 1 ?> of <?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action + Agent sidebar -->
        <div class="detail-intro-actions">
            <article class="action-card">
                <div class="action-card-price"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="action-card-buttons">
                    <?php if ($viewerCanAct): ?>
                        <form action="<?= htmlspecialchars(app_url('favorite-toggle'), ENT_QUOTES, 'UTF-8') ?>" method="post" style="margin:0 0 10px">
                            <input type="hidden" name="property_id" value="<?= (int) $listing['id'] ?>">
                            <button type="submit" class="ghost-button wide"
                                    style="border-color:var(--line); color:var(--ink-mid); background:var(--bg); justify-content:center">
                                <?= $isFavorite ? '★ Saved — tap to remove' : '☆ Save this property' ?>
                            </button>
                        </form>

                        <?php if ($viewerCanAct): ?>
                            <a class="ghost-button wide" href="#tour-form"
                               style="border-color:var(--line); color:var(--ink-mid); background:var(--bg); justify-content:center; margin-bottom:10px">
                                🔑 Book a site tour
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($isSale): ?>
                        <a class="solid-button wide" href="<?= htmlspecialchars(app_url('plan'), ENT_QUOTES, 'UTF-8') ?>">Start buyer plan</a>
                        <?php if ($viewerCanAct): ?>
                            <a class="ghost-button wide" href="#offer-form">Make an offer</a>
                        <?php else: ?>
                            <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('login'), ENT_QUOTES, 'UTF-8') ?>">Sign in to make an offer</a>
                        <?php endif; ?>
                        <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>">Browse more homes</a>

                    <?php elseif ($isCommercial): ?>
                        <?php if ($currentUser): ?>
                            <a class="solid-button wide" href="<?= htmlspecialchars(app_url('apply', array('id' => $listing['id'])), ENT_QUOTES, 'UTF-8') ?>">Request lease</a>
                        <?php else: ?>
                            <a class="solid-button wide" href="<?= htmlspecialchars(app_url('login'), ENT_QUOTES, 'UTF-8') ?>">Sign in to enquire</a>
                        <?php endif; ?>
                        <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('commercial'), ENT_QUOTES, 'UTF-8') ?>">Browse more spaces</a>
                    <?php else: ?>
                        <?php if ($currentUser): ?>
                            <a class="solid-button wide" href="<?= htmlspecialchars(app_url('apply', array('id' => $listing['id'])), ENT_QUOTES, 'UTF-8') ?>">Apply for this rental</a>
                            <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('dashboard'), ENT_QUOTES, 'UTF-8') ?>">Open renter dashboard</a>
                        <?php else: ?>
                            <a class="solid-button wide" href="<?= htmlspecialchars(app_url('login'), ENT_QUOTES, 'UTF-8') ?>">Sign in to apply</a>
                            <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('register'), ENT_QUOTES, 'UTF-8') ?>">Create renter account</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if ($isSale && $viewerCanAct): ?>
                <div id="offer-form" style="margin-top:18px; padding-top:18px; border-top:1px solid var(--line); text-align:left">
                    <span style="font-weight:700; font-size:.95rem">Submit an offer</span>
                    <p class="muted-text" style="font-size:.8rem; margin:4px 0 12px">Asking <?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?>. Propose your amount and terms — the sales desk responds in your offer tracker.</p>
                    <form action="<?= htmlspecialchars(app_url('offer-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post" style="display:grid; gap:10px">
                        <input type="hidden" name="property_id" value="<?= (int) $listing['id'] ?>">
                        <input type="number" name="offer_amount" min="1" placeholder="Your offer amount (<?= htmlspecialchars((string) $listing['askingPrice'], ENT_QUOTES, 'UTF-8') ?>)" required style="padding:10px 12px; border-radius:10px; border:1px solid var(--line)">
                        <input type="text" name="timeline" placeholder="Desired timeline (e.g. 3 months)" style="padding:10px 12px; border-radius:10px; border:1px solid var(--line)">
                        <textarea name="terms" rows="3" placeholder="Terms / conditions (e.g. subject to mortgage approval)" style="padding:10px 12px; border-radius:10px; border:1px solid var(--line)"></textarea>
                        <button type="submit" class="solid-button" style="justify-content:center">Submit offer</button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($viewerCanAct): ?>
                <div id="tour-form" style="margin-top:18px; padding-top:18px; border-top:1px solid var(--line); text-align:left">
                    <span style="font-weight:700; font-size:.95rem">🔑 Book a site tour</span>
                    <p class="muted-text" style="font-size:.8rem; margin:4px 0 12px">Pick a preferred daytime slot and we'll confirm it with you in your tour tracker.</p>
                    <form action="<?= htmlspecialchars(app_url('tour-request'), ENT_QUOTES, 'UTF-8') ?>" method="post" style="display:grid; gap:10px">
                        <input type="hidden" name="property_id" value="<?= (int) $listing['id'] ?>">
                        <input type="date" name="tour_date" required style="padding:10px 12px; border-radius:10px; border:1px solid var(--line)">
                        <input type="time" name="tour_time" required style="padding:10px 12px; border-radius:10px; border:1px solid var(--line)">
                        <textarea name="notes" rows="2" placeholder="Anything we should know? (optional)" style="padding:10px 12px; border-radius:10px; border:1px solid var(--line)"></textarea>
                        <button type="submit" class="solid-button" style="justify-content:center">Request tour</button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($isRent && !empty($listing['monthlyRent'])): ?>
                <div style="margin-top:18px; padding-top:18px; border-top:1px solid var(--line); display:grid; gap:8px; font-size:.85rem;">
                    <div style="display:flex; justify-content:space-between; color:var(--muted)">
                        <span>Monthly rent</span>
                        <strong style="color:var(--ink)"><?= htmlspecialchars(app_currency($listing['monthlyRent']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <?php if (!empty($listing['serviceCharge'])): ?>
                    <div style="display:flex; justify-content:space-between; color:var(--muted)">
                        <span>Service charge</span>
                        <strong style="color:var(--ink)"><?= htmlspecialchars(app_currency($listing['serviceCharge']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($listing['securityDeposit'])): ?>
                    <div style="display:flex; justify-content:space-between; color:var(--muted)">
                        <span>Security deposit</span>
                        <strong style="color:var(--ink)"><?= htmlspecialchars(app_currency($listing['securityDeposit']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding-top:8px; border-top:1px solid var(--line-light);">
                        <span style="font-weight:700; color:var(--ink)">Total due at move-in</span>
                        <strong style="color:var(--primary)">
                            <?= htmlspecialchars(app_currency($listing['monthlyRent'] + $listing['serviceCharge'] + $listing['securityDeposit']), ENT_QUOTES, 'UTF-8') ?>
                        </strong>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </article>

            <div class="detail-card agent-card">
                <span class="panel-kicker">Listed by</span>
                <h3>Sandworth Homes Team</h3>
                <p class="muted-text">Reach out for photos, availability, or a scheduled walkthrough of this listing.</p>
                <div class="cta-stack">
                    <?php if ($viewerCanAct): ?>
                        <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('messages'), ENT_QUOTES, 'UTF-8') ?>"
                           style="border-color:var(--line); color:var(--ink-mid); background:var(--bg); font-size:.875rem; justify-content:center">
                            💬 Message leasing desk about this listing
                        </a>
                    <?php endif; ?>
                    <a class="ghost-button wide" href="mailto:hello@sandworthliving.ng"
                       style="border-color:var(--line); color:var(--ink-mid); background:var(--bg); font-size:.875rem; justify-content:center">
                        ✉ Email leasing desk
                    </a>
                    <a class="ghost-button wide" href="tel:+2348000000000"
                       style="border-color:var(--line); color:var(--ink-mid); background:var(--bg); font-size:.875rem; justify-content:center">
                        📞 Call +234 800 000 0000
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== DESCRIPTION & FEATURES ===== -->
    <section class="search-layout" style="gap:28px; padding:0 0 32px">
        <div>
            <div class="detail-card">
                <h2 style="font-size:1.1rem; margin-bottom:12px">About this property</h2>
                <p style="line-height:1.8; color:var(--ink-mid); font-size:.9375rem">
                    <?= htmlspecialchars((string) $listing['summary'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <?php if (!empty($listing['description'])): ?>
                    <p style="line-height:1.8; color:var(--ink-mid); font-size:.9375rem; margin-top:12px">
                        <?= htmlspecialchars((string) $listing['description'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if (!empty($listing['features'])): ?>
            <div class="detail-card" style="margin-top:16px">
                <h2 style="font-size:1.1rem; margin-bottom:12px">Features &amp; amenities</h2>
                <ul class="feature-list" style="columns:2; column-gap:20px">
                    <?php foreach ($listing['features'] as $feature): ?>
                        <li style="break-inside:avoid; list-style:none; display:flex; align-items:center; gap:8px; padding:5px 0; border-bottom:1px solid var(--line-light); font-size:.875rem">
                            <span style="color:var(--green); font-size:1rem">✓</span>
                            <?= htmlspecialchars((string) $feature, ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <aside>
            <?php if (!empty($listing['badges'])): ?>
            <div class="detail-card">
                <span class="eyebrow">Property highlights</span>
                <div class="badge-row" style="margin-top:12px">
                    <?php foreach ($listing['badges'] as $badge): ?>
                        <span><?= htmlspecialchars((string) $badge, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($listing['lat']) && !empty($listing['lng'])): ?>
            <div class="detail-card" style="margin-top:16px; padding:0; overflow:hidden">
                <div style="padding:14px 18px 10px">
                    <span class="eyebrow">Location map</span>
                </div>
                <div class="map-canvas"
                     data-mode="pin"
                     data-lat="<?= htmlspecialchars((string) $listing['lat'], ENT_QUOTES, 'UTF-8') ?>"
                     data-lng="<?= htmlspecialchars((string) $listing['lng'], ENT_QUOTES, 'UTF-8') ?>"
                     data-zoom="14"
                     style="height:220px; border-radius:0 0 var(--r-lg) var(--r-lg)"></div>
            </div>
            <?php endif; ?>
        </aside>
    </section>

    <!-- ===== SIMILAR LISTINGS ===== -->
    <?php if ($similarListings !== array()): ?>
        <section class="section-block">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">More like this</span>
                    <h2>Similar listings nearby</h2>
                </div>
                <a class="text-link" href="<?= htmlspecialchars(app_url($breadcrumbPage), ENT_QUOTES, 'UTF-8') ?>">Browse all &rarr;</a>
            </div>

            <div class="listing-grid">
                <?php foreach ($similarListings as $similar): ?>
                    <a class="listing-card listing-card-link" href="<?= htmlspecialchars(app_url('property', array('id' => $similar['id'])), ENT_QUOTES, 'UTF-8') ?>">
                        <div style="overflow:hidden">
                            <img src="<?= htmlspecialchars((string) $similar['image'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars((string) $similar['title'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="listing-body">
                            <p class="listing-price"><?= htmlspecialchars((string) $similar['price'], ENT_QUOTES, 'UTF-8') ?></p>
                            <h3><?= htmlspecialchars((string) $similar['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="muted-text"><?= htmlspecialchars((string) $similar['location'], ENT_QUOTES, 'UTF-8') ?></p>
                            <span class="market-listing-link">View details &rarr;</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

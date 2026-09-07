<?php

/** @var array<string, mixed>|null $listing */
/** @var array<int, array<string, mixed>> $similarListings */
/** @var array<int, array<string, mixed>> $availableTourSlots */
/** @var array<string, mixed>|null $activeTourRequest */
/** @var array $siteSettings */
$similarListings = isset($similarListings) ? $similarListings : array();
$availableTourSlots = isset($availableTourSlots) ? $availableTourSlots : array();
$activeTourRequest = isset($activeTourRequest) ? $activeTourRequest : null;
$siteSettings = isset($siteSettings) && is_array($siteSettings) ? $siteSettings : array();
?>
<?php if ($listing === null): ?>
    <section class="page-hero compact">
        <div>
            <span class="eyebrow">Listing unavailable</span>
            <h1>We could not find that property.</h1>
            <p>The link may be outdated or the listing may have been removed.</p>
            <a class="solid-button" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>">Back to rentals</a>
        </div>
    </section>
<?php else: ?>
    <?php
        $purpose = isset($listing['purpose']) ? $listing['purpose'] : 'rent';
        $isSale = $purpose === 'sale';
        $isCommercial = $purpose === 'commercial';
        $isRent = ! $isSale && ! $isCommercial;
        $contactEmail = isset($siteSettings['contactEmail']) && trim((string) $siteSettings['contactEmail']) !== '' ? (string) $siteSettings['contactEmail'] : 'info@sandworthliving.ng';
        $contactPhone = isset($siteSettings['contactPhone']) && trim((string) $siteSettings['contactPhone']) !== '' ? (string) $siteSettings['contactPhone'] : '+234 803 437 1916';
        $operationalOffice = isset($siteSettings['operationalOffice']) ? (string) $siteSettings['operationalOffice'] : 'The Facility Management Office, The Nigeria Army Shopping Complex (The Arena), Bolade-Oshodi, 101233, Lagos State, Nigeria.';
        $registeredOffice = isset($siteSettings['registeredOffice']) ? (string) $siteSettings['registeredOffice'] : '1, Tafawa Balewa Crescent, off Adeniran Ogunsanya, Surulere, Lagos State, Nigeria.';

        $gallery = isset($listing['images']) && is_array($listing['images']) && count($listing['images']) > 0
            ? $listing['images']
            : array($listing['image']);

        $breadcrumbPage = $isSale ? 'homes' : ($isCommercial ? 'commercial' : 'rentals');
        $breadcrumbLabel = $isSale ? 'Homes for sale' : ($isCommercial ? 'Malls & Shops' : 'Rentals');
        $pillLabel = $isSale ? 'For sale' : ($isCommercial ? 'Commercial' : 'For rent');
        $pillClass = $isSale ? 'for-sale' : ($isCommercial ? 'commercial' : '');
        $propertyMapPins = array();
        $legalFee = $isRent ? app_legal_fee_amount($listing['monthlyRent']) : 0;
        $cautionDeposit = $isRent ? app_caution_deposit_amount($listing['securityDeposit']) : 0;
        $moveInTotal = $isRent ? app_move_in_total($listing['monthlyRent'], $listing['serviceCharge'], $cautionDeposit) : 0;
        $tourCount = count($availableTourSlots);
        $isAdminViewer = $currentUser && App\Core\Auth::isAdmin($currentUser);

        if (! empty($listing['lat']) && ! empty($listing['lng'])) {
            $propertyMapPins[] = array(
                'id' => isset($listing['id']) ? (string) $listing['id'] : 'property',
                'lat' => (float) $listing['lat'],
                'lng' => (float) $listing['lng'],
                'title' => isset($listing['title']) ? (string) $listing['title'] : 'Property location',
                'price' => isset($listing['price']) ? (string) $listing['price'] : '',
                'location' => isset($listing['location']) ? (string) $listing['location'] : '',
                'image' => isset($gallery[0]) ? (string) $gallery[0] : '',
                'url' => app_property_url($listing),
            );
        }
    ?>

    <nav class="breadcrumb-row" aria-label="Breadcrumb">
        <a href="<?= htmlspecialchars(app_url('home'), ENT_QUOTES, 'UTF-8') ?>">Home</a>
        <span>/</span>
        <a href="<?= htmlspecialchars(app_url($breadcrumbPage), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($breadcrumbLabel, ENT_QUOTES, 'UTF-8') ?></a>
        <span>/</span>
        <span class="muted-text"><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></span>
    </nav>

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

            <p class="listing-price" style="font-size:2rem;margin:10px 0 6px"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?></p>
            <h1><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="muted-text" style="font-size:.95rem;margin:0 0 12px">
                <?= htmlspecialchars((string) $listing['location'], ENT_QUOTES, 'UTF-8') ?>
            </p>

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

        <div class="detail-intro-actions">
            <article class="action-card">
                <div class="action-card-price"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="action-card-buttons">
                    <?php if ($isAdminViewer): ?>
                        <a class="solid-button wide" href="<?= htmlspecialchars(app_url('admin-tours'), ENT_QUOTES, 'UTF-8') ?>">Manage tour slots</a>
                        <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('admin-properties', array('edit_property' => $listing['id'])), ENT_QUOTES, 'UTF-8') ?>">Edit this property</a>
                    <?php elseif ($currentUser): ?>
                        <a class="solid-button wide" href="#tour-booking">Book a tour</a>
                        <?php if ($isSale): ?>
                            <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('plan'), ENT_QUOTES, 'UTF-8') ?>">Start buyer plan</a>
                        <?php elseif ($isCommercial): ?>
                            <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('apply', array('id' => $listing['id'])), ENT_QUOTES, 'UTF-8') ?>">Request lease</a>
                        <?php else: ?>
                            <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('apply', array('id' => $listing['id'])), ENT_QUOTES, 'UTF-8') ?>">Apply for this rental</a>
                            <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('dashboard'), ENT_QUOTES, 'UTF-8') ?>">Open renter dashboard</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a class="solid-button wide" href="<?= htmlspecialchars(app_url('login'), ENT_QUOTES, 'UTF-8') ?>">Sign in to book a tour</a>
                        <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('register'), ENT_QUOTES, 'UTF-8') ?>">Create renter account</a>
                    <?php endif; ?>

                    <?php if ($isSale): ?>
                        <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>">Browse more homes</a>
                    <?php elseif ($isCommercial): ?>
                        <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('commercial'), ENT_QUOTES, 'UTF-8') ?>">Browse more spaces</a>
                    <?php endif; ?>
                </div>

                <div class="tour-availability-note">
                    <?php if ($tourCount > 0): ?>
                        <strong><?= (int) $tourCount ?> upcoming tour slot<?= $tourCount === 1 ? '' : 's' ?> available.</strong>
                        <span>Choose a time below and we will confirm the walkthrough.</span>
                    <?php else: ?>
                        <strong>No open tour slots right now.</strong>
                        <span>Use the contact buttons below and the leasing desk can arrange the next release window.</span>
                    <?php endif; ?>
                </div>

                <?php if ($isRent && ! empty($listing['monthlyRent'])): ?>
                <div style="margin-top:18px; padding-top:18px; border-top:1px solid var(--line); display:grid; gap:8px; font-size:.85rem;">
                    <div style="display:flex; justify-content:space-between; color:var(--muted)">
                        <span>Annual rent</span>
                        <strong style="color:var(--ink)"><?= htmlspecialchars(app_currency($listing['monthlyRent']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <?php if (! empty($listing['serviceCharge'])): ?>
                    <div style="display:flex; justify-content:space-between; color:var(--muted)">
                        <span>Service charge</span>
                        <strong style="color:var(--ink)"><?= htmlspecialchars(app_currency($listing['serviceCharge']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <?php endif; ?>
                    <div style="display:flex; justify-content:space-between; color:var(--muted)">
                        <span>Legal fee (10%)</span>
                        <strong style="color:var(--ink)"><?= htmlspecialchars(app_currency($legalFee), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; color:var(--muted)">
                        <span>Caution deposit</span>
                        <strong style="color:var(--ink)"><?= htmlspecialchars(app_currency($cautionDeposit), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding-top:8px; border-top:1px solid var(--line-light);">
                        <span style="font-weight:700; color:var(--ink)">Total due at move-in</span>
                        <strong style="color:var(--primary)"><?= htmlspecialchars(app_currency($moveInTotal), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
                <?php endif; ?>
            </article>

            <div class="detail-card agent-card">
                <span class="panel-kicker">Listed by</span>
                <h3>Sandworth Homes Team</h3>
                <p class="muted-text">Reach out for photos, availability, or a scheduled walkthrough of this listing.</p>
                <div class="cta-stack">
                    <a class="ghost-button wide" href="mailto:<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?>"
                       style="border-color:var(--line); color:var(--ink-mid); background:var(--bg); font-size:.875rem; justify-content:center">
                        Email leasing desk
                    </a>
                    <a class="ghost-button wide" href="tel:<?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?>"
                       style="border-color:var(--line); color:var(--ink-mid); background:var(--bg); font-size:.875rem; justify-content:center">
                        Call <?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>
                <div style="margin-top:16px; display:grid; gap:10px;">
                    <div>
                        <strong style="display:block; color:var(--ink); font-size:.85rem;">Operational Office</strong>
                        <p class="muted-text" style="margin:4px 0 0; font-size:.82rem; line-height:1.6;"><?= htmlspecialchars($operationalOffice, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <strong style="display:block; color:var(--ink); font-size:.85rem;">Registered Office</strong>
                        <p class="muted-text" style="margin:4px 0 0; font-size:.82rem; line-height:1.6;"><?= htmlspecialchars($registeredOffice, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="search-layout" style="gap:28px; padding:0 0 32px">
        <div>
            <div class="detail-card">
                <h2 style="font-size:1.1rem; margin-bottom:12px">About this property</h2>
                <p style="line-height:1.8; color:var(--ink-mid); font-size:.9375rem">
                    <?= htmlspecialchars((string) $listing['summary'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <?php if (! empty($listing['description'])): ?>
                    <p style="line-height:1.8; color:var(--ink-mid); font-size:.9375rem; margin-top:12px">
                        <?= htmlspecialchars((string) $listing['description'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if (! empty($listing['features'])): ?>
            <div class="detail-card" style="margin-top:16px">
                <h2 style="font-size:1.1rem; margin-bottom:12px">Features &amp; amenities</h2>
                <ul class="feature-list" style="columns:2; column-gap:20px">
                    <?php foreach ($listing['features'] as $feature): ?>
                        <li style="break-inside:avoid; list-style:none; display:flex; align-items:center; gap:8px; padding:5px 0; border-bottom:1px solid var(--line-light); font-size:.875rem">
                            <span style="color:var(--green); font-size:1rem">Yes</span>
                            <?= htmlspecialchars((string) $feature, ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <aside>
            <?php if (! empty($listing['badges'])): ?>
            <div class="detail-card">
                <span class="eyebrow">Property highlights</span>
                <div class="badge-row" style="margin-top:12px">
                    <?php foreach ($listing['badges'] as $badge): ?>
                        <span><?= htmlspecialchars((string) $badge, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (! empty($propertyMapPins)): ?>
            <div class="detail-card" style="margin-top:16px; padding:0; overflow:hidden">
                <div style="padding:14px 18px 10px">
                    <span class="eyebrow">Location map</span>
                </div>
                <div class="map-canvas"
                     data-mode="single"
                     data-pins="<?= htmlspecialchars((string) json_encode($propertyMapPins), ENT_QUOTES, 'UTF-8') ?>"
                     data-zoom="14"
                     style="height:220px; border-radius:0 0 var(--r-lg) var(--r-lg)"></div>
            </div>
            <?php endif; ?>
        </aside>
    </section>

    <section class="section-block" id="tour-booking">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Schedule a visit</span>
                <h2>Book a tour for this property</h2>
            </div>
        </div>

        <div class="tour-booking-grid">
            <article class="detail-card">
                <?php if ($isAdminViewer): ?>
                    <span class="type-pill">Admin tools</span>
                    <h3 style="margin-top:12px;">Manage viewing availability</h3>
                    <p class="muted-text">This listing uses admin-managed slots. Add or adjust openings from the tour desk.</p>
                    <a class="solid-button" href="<?= htmlspecialchars(app_url('admin-tours'), ENT_QUOTES, 'UTF-8') ?>">Open admin tour desk</a>
                <?php elseif ($activeTourRequest): ?>
                    <span class="admin-status-pill <?= htmlspecialchars((string) $activeTourRequest['status'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) ucfirst($activeTourRequest['status']), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <h3 style="margin-top:12px;">Your tour request is on file</h3>
                    <p class="muted-text">Scheduled for <?= htmlspecialchars((string) $activeTourRequest['slotLabel'], ENT_QUOTES, 'UTF-8') ?>.</p>
                    <?php if ($activeTourRequest['message'] !== ''): ?>
                        <p><?= htmlspecialchars((string) $activeTourRequest['message'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if ($activeTourRequest['status'] === 'confirmed'): ?>
                        <p class="muted-text">The leasing desk confirmed this visit. You can track it from your dashboard.</p>
                    <?php else: ?>
                        <p class="muted-text">We are reviewing the slot and will confirm shortly in your dashboard.</p>
                    <?php endif; ?>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('dashboard'), ENT_QUOTES, 'UTF-8') ?>">Open renter dashboard</a>
                <?php elseif (! $currentUser): ?>
                    <span class="type-pill">Renter sign-in required</span>
                    <h3 style="margin-top:12px;">Sign in before choosing a viewing time</h3>
                    <p class="muted-text">We use your account details to confirm appointments and show tour updates in your dashboard.</p>
                    <div class="tour-inline-actions">
                        <a class="solid-button" href="<?= htmlspecialchars(app_url('login'), ENT_QUOTES, 'UTF-8') ?>">Sign in</a>
                        <a class="ghost-button" href="<?= htmlspecialchars(app_url('register'), ENT_QUOTES, 'UTF-8') ?>">Create account</a>
                    </div>
                <?php elseif ($availableTourSlots === array()): ?>
                    <span class="type-pill">No open slots</span>
                    <h3 style="margin-top:12px;">The next viewing window has not been published yet</h3>
                    <p class="muted-text">Contact the leasing desk and we can alert you when the next availability block is released.</p>
                    <div class="tour-inline-actions">
                        <a class="ghost-button" href="mailto:<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?>">Email leasing desk</a>
                        <a class="ghost-button" href="tel:<?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?>">Call <?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                <?php else: ?>
                    <form action="<?= htmlspecialchars(app_url('tour-request-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="tour-booking-form">
                        <input type="hidden" name="property_id" value="<?= (int) $listing['id'] ?>">

                        <div class="admin-field">
                            <label for="tour-slot-id">Choose a tour slot</label>
                            <select id="tour-slot-id" name="slot_id" required>
                                <option value="">Select an available time</option>
                                <?php foreach ($availableTourSlots as $tourSlot): ?>
                                    <option value="<?= (int) $tourSlot['id'] ?>"><?= htmlspecialchars((string) $tourSlot['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="admin-field">
                            <label for="tour-message">Notes for the leasing desk</label>
                            <textarea id="tour-message" name="message" rows="4" placeholder="Gate access, who is attending, preferred contact method, or any other helpful note."></textarea>
                        </div>

                        <button type="submit" class="solid-button">Submit tour request</button>
                    </form>
                <?php endif; ?>
            </article>

            <article class="detail-card">
                <span class="eyebrow">What happens next</span>
                <div class="tour-steps">
                    <div>
                        <strong>1. Choose a slot</strong>
                        <p>Select one of the listed viewing windows for this property.</p>
                    </div>
                    <div>
                        <strong>2. We confirm it</strong>
                        <p>The Leasing manager confirms the appointment and keeps the slot reserved for you.</p>
                    </div>
                    <div>
                        <strong>3. Track it in your dashboard</strong>
                        <p>Your renter dashboard shows the latest status for every requested or confirmed visit.</p>
                    </div>
                </div>
            </article>
        </div>
    </section>

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
                    <?php $listingUrl = app_property_url($similar); ?>
                    <article class="listing-card">
                        <a class="listing-card-media" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>">
                            <div style="overflow:hidden">
                                <img src="<?= htmlspecialchars((string) $similar['image'], ENT_QUOTES, 'UTF-8') ?>"
                                     alt="<?= htmlspecialchars((string) $similar['title'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </a>
                        <div class="listing-body">
                            <p class="listing-price"><?= htmlspecialchars((string) $similar['price'], ENT_QUOTES, 'UTF-8') ?></p>
                            <h3><a class="listing-card-title-link" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $similar['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                            <p class="muted-text"><?= htmlspecialchars((string) $similar['location'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="listing-card-actions">
                                <?= app_share_button_markup($similar) ?>
                                <a class="market-listing-link" href="<?= htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8') ?>">View details &rarr;</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

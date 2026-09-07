<?php
/** @var array<string, mixed>|null $listing */
/** @var array<int, array<string, mixed>> $similarListings */
/** @var array<int, array<string, mixed>> $availableTourSlots */
/** @var array<string, mixed>|null $activeTourRequest */
/** @var array<string, mixed> $siteSettings */
/** @var array<int, array<string, mixed>> $propertyMessages */
$similarListings = isset($similarListings) ? $similarListings : array();
$availableTourSlots = isset($availableTourSlots) ? $availableTourSlots : array();
$activeTourRequest = isset($activeTourRequest) ? $activeTourRequest : null;
$propertyMessages = isset($propertyMessages) ? $propertyMessages : array();
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
        $gallery = isset($listing['images']) && is_array($listing['images']) && count($listing['images']) > 0 ? $listing['images'] : array($listing['image']);
        $activePageLink = $isSale ? app_url('homes') : ($isCommercial ? app_url('commercial') : app_url('rentals'));
    ?>

    <nav class="breadcrumb-row" aria-label="Breadcrumb">
        <a href="<?= htmlspecialchars(app_url('home'), ENT_QUOTES, 'UTF-8') ?>">Home</a>
        <span>/</span>
        <a href="<?= htmlspecialchars($activePageLink, ENT_QUOTES, 'UTF-8') ?>"><?= $isSale ? 'Homes for sale' : ($isCommercial ? 'Commercial spaces' : 'Rentals') ?></a>
        <span>/</span>
        <span class="muted-text"><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></span>
    </nav>

    <section class="detail-intro">
        <div class="detail-intro-copy">
            <div class="detail-intro-top">
                <span class="type-pill <?= $isSale ? 'for-sale' : ($isCommercial ? 'commercial' : '') ?>"><?= $isSale ? 'For sale' : ($isCommercial ? 'Commercial' : 'For rent') ?></span>
                <?php if (count($gallery) > 1): ?>
                    <span class="type-pill" style="background:var(--line-light);color:var(--muted);border-color:var(--line)"><?= count($gallery) ?> photos</span>
                <?php endif; ?>
            </div>

            <p class="listing-price" style="font-size:2rem;margin:10px 0 6px"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?></p>
            <h1><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="muted-text" style="font-size:.95rem;margin:0 0 12px"><?= htmlspecialchars((string) $listing['location'], ENT_QUOTES, 'UTF-8') ?></p>

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
                <img id="gallery-hero-image" src="<?= htmlspecialchars((string) $gallery[0], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <?php if (count($gallery) > 1): ?>
                <div class="gallery-thumbs">
                    <?php foreach (array_slice($gallery, 0, 4) as $i => $img): ?>
                        <div class="gallery-thumb <?= $i === 0 ? 'is-active' : '' ?>" data-gallery-src="<?= htmlspecialchars((string) $img, ENT_QUOTES, 'UTF-8') ?>">
                            <img src="<?= htmlspecialchars((string) $img, ENT_QUOTES, 'UTF-8') ?>" alt="Photo <?= $i + 1 ?> of <?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="detail-intro-actions">
            <article class="action-card">
                <div class="action-card-price"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="action-card-buttons">
                    <?php if (! $currentUser): ?>
                        <a class="solid-button wide" href="<?= htmlspecialchars(app_url('login'), ENT_QUOTES, 'UTF-8') ?>">Sign in to continue</a>
                        <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('register'), ENT_QUOTES, 'UTF-8') ?>">Create account</a>
                    <?php elseif ($isSale): ?>
                        <a class="solid-button wide" href="#offer-form">Make an offer</a>
                        <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('plan'), ENT_QUOTES, 'UTF-8') ?>">Start buyer plan</a>
                    <?php elseif ($isCommercial): ?>
                        <a class="solid-button wide" href="<?= htmlspecialchars(app_url('apply', array('id' => $listing['id'])), ENT_QUOTES, 'UTF-8') ?>">Request lease</a>
                        <a class="ghost-button wide" href="#message-desk">Message the leasing desk</a>
                    <?php else: ?>
                        <a class="solid-button wide" href="#tour-booking">Book a tour</a>
                        <a class="ghost-button wide" href="<?= htmlspecialchars(app_url('apply', array('id' => $listing['id'])), ENT_QUOTES, 'UTF-8') ?>">Apply for this rental</a>
                    <?php endif; ?>
                </div>

                <div class="tour-availability-note">
                    <?php if ($availableTourSlots !== array()): ?>
                        <strong><?= count($availableTourSlots) ?> open viewing slot<?= count($availableTourSlots) === 1 ? '' : 's' ?>.</strong>
                        <span>Choose a time and keep the appointment on your dashboard.</span>
                    <?php else: ?>
                        <strong>No open tour slots right now.</strong>
                        <span>Contact the team or check back when the next viewing window is released.</span>
                    <?php endif; ?>
                </div>
            </article>

            <article class="detail-card agent-card">
                <span class="panel-kicker">Listed by</span>
                <h3>Sandworth Homes Team</h3>
                <p class="muted-text">Reach out for scheduling, pricing, or document support.</p>
                <div class="cta-stack">
                    <a class="ghost-button wide" href="mailto:<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?>">Email leasing desk</a>
                    <a class="ghost-button wide" href="tel:<?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?>">Call <?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </article>
        </div>
    </section>

    <section class="search-layout" style="gap:24px; padding:0 0 32px">
        <div>
            <div class="detail-card">
                <h2 style="font-size:1.1rem; margin-bottom:12px">About this property</h2>
                <p style="line-height:1.8; color:var(--ink-mid); font-size:.9375rem"><?= htmlspecialchars((string) $listing['summary'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <?php if (! empty($listing['features'])): ?>
                <div class="detail-card" style="margin-top:16px">
                    <h2 style="font-size:1.1rem; margin-bottom:12px">Features and amenities</h2>
                    <ul class="feature-list">
                        <?php foreach ($listing['features'] as $feature): ?>
                            <li><?= htmlspecialchars((string) $feature, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <aside>
            <div class="detail-card" id="message-desk">
                <span class="eyebrow">Property chat</span>
                <h2 style="font-size:1.1rem; margin:6px 0 12px">Message the team</h2>
                <?php if (! $currentUser): ?>
                    <p class="muted-text">Sign in to send questions and keep the conversation in your dashboard.</p>
                <?php else: ?>
                    <form action="<?= htmlspecialchars(app_url('message-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="filter-form">
                        <input type="hidden" name="property_id" value="<?= (int) $listing['id'] ?>">
                        <input type="hidden" name="subject" value="<?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars(app_property_url($listing) . '#message-desk', ENT_QUOTES, 'UTF-8') ?>">
                        <div>
                            <label for="property-message-body">Your message</label>
                            <textarea id="property-message-body" name="body" rows="4" placeholder="Ask about viewing access, move-in charges, documentation, or negotiation details." required></textarea>
                        </div>
                        <button type="submit" class="solid-button">Send message</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($propertyMessages !== array()): ?>
                <div class="detail-card" style="margin-top:16px">
                    <span class="eyebrow">Recent conversation</span>
                    <div style="display:grid; gap:12px; margin-top:12px">
                        <?php foreach (array_slice($propertyMessages, 0, 4) as $message): ?>
                            <article style="padding-bottom:12px; border-bottom:1px solid var(--line-light)">
                                <strong style="display:block; font-size:.85rem"><?= htmlspecialchars((string) ucfirst($message['sender']), ENT_QUOTES, 'UTF-8') ?></strong>
                                <p style="margin:6px 0 0; font-size:.875rem"><?= nl2br(htmlspecialchars((string) $message['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
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
                <?php if ($activeTourRequest): ?>
                    <span class="admin-status-pill <?= htmlspecialchars((string) $activeTourRequest['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ucfirst($activeTourRequest['status']), ENT_QUOTES, 'UTF-8') ?></span>
                    <h3 style="margin-top:12px">Your tour request is active</h3>
                    <p class="muted-text">Scheduled for <?= htmlspecialchars((string) $activeTourRequest['slotLabel'], ENT_QUOTES, 'UTF-8') ?>.</p>
                    <form action="<?= htmlspecialchars(app_url('tour-request-cancel'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                        <input type="hidden" name="tour_request_id" value="<?= (int) $activeTourRequest['id'] ?>">
                        <button type="submit" class="ghost-button">Cancel and rebook</button>
                    </form>
                <?php elseif (! $currentUser): ?>
                    <span class="type-pill">Renter sign-in required</span>
                    <h3 style="margin-top:12px">Sign in before choosing a viewing time</h3>
                    <p class="muted-text">We use your account details to confirm appointments and keep the visit on your dashboard.</p>
                <?php elseif ($availableTourSlots === array()): ?>
                    <span class="type-pill">No open slots</span>
                    <h3 style="margin-top:12px">The next viewing window has not been published yet</h3>
                    <p class="muted-text">Use the message desk to request the next release window.</p>
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
                            <textarea id="tour-message" name="message" rows="4" placeholder="Gate access, who is attending, or preferred contact method."></textarea>
                        </div>
                        <button type="submit" class="solid-button">Submit tour request</button>
                    </form>
                <?php endif; ?>
            </article>

            <?php if ($isSale && $currentUser): ?>
                <article class="detail-card" id="offer-form">
                    <span class="eyebrow">Buyer action</span>
                    <h3 style="margin-top:8px">Submit your offer</h3>
                    <form action="<?= htmlspecialchars(app_url('offer-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="filter-form">
                        <input type="hidden" name="property_id" value="<?= (int) $listing['id'] ?>">
                        <div>
                            <label for="offer-amount">Offer amount</label>
                            <input id="offer-amount" name="offer_amount" type="number" min="1" required>
                        </div>
                        <div>
                            <label for="offer-timeline">Timeline</label>
                            <input id="offer-timeline" name="timeline" type="text" placeholder="Cash in 30 days, mortgage in 60 days">
                        </div>
                        <div>
                            <label for="offer-terms">Terms</label>
                            <textarea id="offer-terms" name="terms" rows="4" placeholder="Inspection, financing, or document conditions."></textarea>
                        </div>
                        <button type="submit" class="solid-button">Send offer</button>
                    </form>
                </article>
            <?php else: ?>
                <article class="detail-card">
                    <span class="eyebrow">What happens next</span>
                    <div class="tour-steps">
                        <div>
                            <strong>1. Choose the best next action</strong>
                            <p><?= $isSale ? 'Send an offer and a timeline that matches your buying readiness.' : 'Choose a slot or submit your application when you are ready.' ?></p>
                        </div>
                        <div>
                            <strong>2. Keep everything in one account</strong>
                            <p>Your dashboard tracks housing progress, messages, applications, and visits in one place.</p>
                        </div>
                    </div>
                </article>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($similarListings !== array()): ?>
        <section class="section-block">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">More like this</span>
                    <h2>Similar listings nearby</h2>
                </div>
            </div>
            <div class="listing-grid">
                <?php foreach ($similarListings as $similar): ?>
                    <article class="listing-card">
                        <a class="listing-card-media" href="<?= htmlspecialchars(app_property_url($similar), ENT_QUOTES, 'UTF-8') ?>">
                            <div style="overflow:hidden">
                                <img src="<?= htmlspecialchars((string) $similar['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $similar['title'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </a>
                        <div class="listing-body">
                            <p class="listing-price"><?= htmlspecialchars((string) $similar['price'], ENT_QUOTES, 'UTF-8') ?></p>
                            <h3><a class="listing-card-title-link" href="<?= htmlspecialchars(app_property_url($similar), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $similar['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                            <p class="muted-text"><?= htmlspecialchars((string) $similar['location'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

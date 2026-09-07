<?php
/** @var array $applications */
/** @var array $tourRequests */
/** @var array|null $tenancy */
/** @var array $offers */
/** @var array $messages */
/** @var array $savedSearches */
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">My dashboard</span>
        <h1>Your housing control center</h1>
        <p>Track tours, applications, offers, saved searches, and conversations from one renter-ready workspace.</p>
    </div>
</section>

<?php if ($tenancy): ?>
    <div class="detail-card" style="margin:24px 0; border-left:4px solid var(--green); border-radius:0 var(--r-lg) var(--r-lg) 0; display:flex; align-items:center; justify-content:space-between; gap:20px; flex-wrap:wrap">
        <div>
            <span class="eyebrow">Active tenancy</span>
            <h2 style="margin:4px 0 6px; font-size:1.1rem"><?= htmlspecialchars((string) $tenancy['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p style="font-size:.875rem; color:var(--muted); margin:0">
                Move-in: <?= htmlspecialchars((string) $tenancy['startDate'], ENT_QUOTES, 'UTF-8') ?> | Status: <?= htmlspecialchars((string) ucfirst($tenancy['status']), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <a class="solid-button" href="<?= htmlspecialchars(app_url('tenancy'), ENT_QUOTES, 'UTF-8') ?>">Open tenancy app</a>
    </div>
<?php endif; ?>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Saved searches</span>
            <h2>Stay ahead of new availability</h2>
        </div>
    </div>

    <div class="search-layout" style="gap:20px">
        <article class="detail-card">
            <h3 style="margin-top:0">Save a search</h3>
            <form action="<?= htmlspecialchars(app_url('saved-search-create'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="filter-form">
                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars(app_url('dashboard'), ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label for="search-name">Search name</label>
                    <input id="search-name" name="name" type="text" placeholder="Lekki family rentals">
                </div>
                <div>
                    <label for="search-purpose">Category</label>
                    <select id="search-purpose" name="purpose">
                        <option value="rent">Rent</option>
                        <option value="sale">Buy</option>
                        <option value="commercial">Commercial</option>
                    </select>
                </div>
                <div>
                    <label for="search-location">Location</label>
                    <input id="search-location" name="location" type="text" placeholder="Lekki, Ikoyi, Ikeja">
                </div>
                <div>
                    <label for="search-beds">Minimum beds</label>
                    <input id="search-beds" name="beds" type="number" min="0" value="0">
                </div>
                <div>
                    <label for="search-property-type">Property type</label>
                    <input id="search-property-type" name="property_type" type="text" placeholder="Apartment, Duplex, Villa">
                </div>
                <div>
                    <label for="search-commercial-type">Commercial type</label>
                    <input id="search-commercial-type" name="commercial_type" type="text" placeholder="Mall, Shop, Plaza">
                </div>
                <label class="check-row">
                    <input type="checkbox" name="pet_friendly" value="1">
                    <span>Pet-friendly only</span>
                </label>
                <button type="submit" class="solid-button">Save search</button>
            </form>
        </article>

        <div class="pipeline-grid">
            <?php foreach ($savedSearches as $savedSearch): ?>
                <article class="pipeline-card">
                    <div class="pipeline-head">
                        <h3><?= htmlspecialchars((string) $savedSearch['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="type-pill"><?= (int) $savedSearch['matchCount'] ?> match<?= (int) $savedSearch['matchCount'] === 1 ? '' : 'es' ?></span>
                    </div>
                    <p class="muted-text" style="margin:0 0 12px"><?= htmlspecialchars((string) $savedSearch['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                    <form action="<?= htmlspecialchars(app_url('saved-search-delete'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                        <input type="hidden" name="saved_search_id" value="<?= (int) $savedSearch['id'] ?>">
                        <button type="submit" class="ghost-button">Remove saved search</button>
                    </form>
                </article>
            <?php endforeach; ?>

            <?php if ($savedSearches === array()): ?>
                <article class="empty-state">
                    <h2>No saved searches yet.</h2>
                    <p>Save your target neighborhoods and filters so this dashboard always shows how many live matches are available.</p>
                </article>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Tour requests</span>
            <h2>Your upcoming property visits</h2>
        </div>
        <a class="ghost-button" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>">Search more rentals</a>
    </div>

    <?php if ($tourRequests === array()): ?>
        <article class="empty-state">
            <h2>No tours booked yet.</h2>
            <p>Use the Book a tour action on any property page to reserve a viewing slot.</p>
        </article>
    <?php else: ?>
        <div class="pipeline-grid">
            <?php foreach ($tourRequests as $tourRequest): ?>
                <article class="pipeline-card">
                    <div class="pipeline-head">
                        <h3><?= htmlspecialchars((string) $tourRequest['property']['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="admin-status-pill <?= htmlspecialchars((string) $tourRequest['status'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string) ucfirst($tourRequest['status']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <p class="muted-text"><?= htmlspecialchars((string) $tourRequest['property']['location'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="muted-text">Slot: <?= htmlspecialchars((string) $tourRequest['slotLabel'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($tourRequest['message'] !== ''): ?>
                        <p><?= htmlspecialchars((string) $tourRequest['message'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if (in_array($tourRequest['status'], array('requested', 'confirmed'), true)): ?>
                        <form action="<?= htmlspecialchars(app_url('tour-request-cancel'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                            <input type="hidden" name="tour_request_id" value="<?= (int) $tourRequest['id'] ?>">
                            <button type="submit" class="ghost-button">Cancel this tour</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Applications</span>
            <h2>Your rental journey</h2>
        </div>
    </div>

    <?php if ($applications === array()): ?>
        <article class="empty-state">
            <h2>No applications yet.</h2>
            <p>Start with a rental search, choose a property, and submit your application.</p>
        </article>
    <?php else: ?>
        <div class="pipeline-grid">
            <?php foreach ($applications as $application): ?>
                <article class="pipeline-card">
                    <div class="pipeline-head">
                        <h3><?= htmlspecialchars((string) $application['property']['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="admin-status-pill <?= htmlspecialchars((string) $application['status'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string) ucfirst($application['status']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <p class="muted-text"><?= htmlspecialchars((string) $application['property']['location'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="muted-text">Submitted: <?= htmlspecialchars((string) $application['submittedAt'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($application['approvedBy'] !== ''): ?>
                        <p style="font-size:.82rem">Reviewed by <?= htmlspecialchars((string) $application['approvedBy'], ENT_QUOTES, 'UTF-8') ?>.</p>
                    <?php endif; ?>
                    <?php if ($application['status'] === 'approved'): ?>
                        <a class="solid-button" href="<?= htmlspecialchars(app_url('payment', array('application_id' => $application['id'])), ENT_QUOTES, 'UTF-8') ?>">Pay move-in charges</a>
                    <?php elseif ($application['status'] === 'active'): ?>
                        <a class="ghost-button" href="<?= htmlspecialchars(app_url('tenancy'), ENT_QUOTES, 'UTF-8') ?>">Open tenancy</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Buyer offers</span>
            <h2>Your sales negotiations</h2>
        </div>
        <a class="ghost-button" href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>">Browse homes</a>
    </div>

    <?php if ($offers === array()): ?>
        <article class="empty-state">
            <h2>No buyer offers yet.</h2>
            <p>Open any home for sale and use the offer form to send pricing and timeline to the sales desk.</p>
        </article>
    <?php else: ?>
        <div class="pipeline-grid">
            <?php foreach ($offers as $offer): ?>
                <article class="pipeline-card">
                    <div class="pipeline-head">
                        <h3><?= htmlspecialchars((string) $offer['property']['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="admin-status-pill <?= htmlspecialchars((string) $offer['status'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string) ucfirst($offer['status']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <p style="font-weight:700; margin:0 0 6px"><?= htmlspecialchars(app_currency($offer['offerAmount']), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="muted-text"><?= htmlspecialchars((string) $offer['timeline'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($offer['terms'] !== ''): ?>
                        <p><?= htmlspecialchars((string) $offer['terms'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if ($offer['adminNotes'] !== ''): ?>
                        <p class="muted-text">Update: <?= htmlspecialchars((string) $offer['adminNotes'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Inbox</span>
            <h2>Messages from the leasing and sales desk</h2>
        </div>
    </div>

    <?php if ($messages === array()): ?>
        <article class="empty-state">
            <h2>No messages yet.</h2>
            <p>Your property conversations will appear here after you contact the team from a property or tenancy page.</p>
        </article>
    <?php else: ?>
        <div class="results-list">
            <?php foreach ($messages as $message): ?>
                <article class="result-card">
                    <div class="result-card-body result-card-body-wide">
                        <div class="result-card-top">
                            <div>
                                <h2><?= htmlspecialchars($message['subject'] !== '' ? (string) $message['subject'] : 'Property conversation', ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="muted-text">
                                    <?= $message['property'] ? htmlspecialchars((string) $message['property']['title'], ENT_QUOTES, 'UTF-8') : 'General support' ?>
                                    | <?= htmlspecialchars((string) ucfirst($message['sender']), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                            <span class="type-pill"><?= htmlspecialchars((string) date('M j, Y g:i a', strtotime($message['createdAt'])), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p><?= nl2br(htmlspecialchars((string) $message['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

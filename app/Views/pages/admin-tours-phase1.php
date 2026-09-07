<?php
/** @var array $portfolio */
/** @var array $properties */
/** @var array $tourRequests */
/** @var array $tourSlots */
$activeAdminPage = 'tours';
$adminTitle = 'Manage property tours and availability';
$adminDescription = 'Create one-off or auto-generated viewing slots, then confirm renter bookings from one tour desk.';
require __DIR__ . '/_admin_phase1_header.php';
?>

<section class="admin-workspace">
    <div class="tour-admin-grid">
        <section class="admin-section">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Single slot</span>
                    <h2>Create one upcoming tour window</h2>
                </div>
            </div>
            <form action="<?= htmlspecialchars(app_url('admin-tour-slot-create'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-form">
                <div class="admin-form-grid">
                    <div class="admin-field admin-span-3">
                        <label for="tour-property-id">Property</label>
                        <select id="tour-property-id" name="property_id" required>
                            <option value="">Select a property</option>
                            <?php foreach ($properties as $property): ?>
                                <option value="<?= (int) $property['id'] ?>"><?= htmlspecialchars((string) $property['title'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $property['location'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="tour-starts-at">Start time</label>
                        <input id="tour-starts-at" name="starts_at" type="datetime-local" required>
                    </div>
                    <div class="admin-field">
                        <label for="tour-ends-at">End time</label>
                        <input id="tour-ends-at" name="ends_at" type="datetime-local" required>
                    </div>
                    <div class="admin-field admin-span-3">
                        <label for="tour-slot-notes">Internal notes</label>
                        <textarea id="tour-slot-notes" name="notes" rows="3" placeholder="Parking guidance, access notes, or assigned rep."></textarea>
                    </div>
                </div>
                <div class="admin-form-actions">
                    <button type="submit" class="solid-button">Add tour slot</button>
                </div>
            </form>
        </section>

        <section class="admin-section">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Auto-generate</span>
                    <h2>Create a full viewing block</h2>
                </div>
            </div>
            <form action="<?= htmlspecialchars(app_url('admin-tour-slot-bulk-create'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-form">
                <div class="admin-form-grid">
                    <div class="admin-field admin-span-3">
                        <label for="bulk-property-id">Property</label>
                        <select id="bulk-property-id" name="property_id" required>
                            <option value="">Select a property</option>
                            <?php foreach ($properties as $property): ?>
                                <option value="<?= (int) $property['id'] ?>"><?= htmlspecialchars((string) $property['title'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $property['location'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="slot-date">Date</label>
                        <input id="slot-date" name="slot_date" type="date" required>
                    </div>
                    <div class="admin-field">
                        <label for="window-start-time">Window start</label>
                        <input id="window-start-time" name="window_start_time" type="time" required>
                    </div>
                    <div class="admin-field">
                        <label for="window-end-time">Window end</label>
                        <input id="window-end-time" name="window_end_time" type="time" required>
                    </div>
                    <div class="admin-field">
                        <label for="slot-minutes">Slot length (minutes)</label>
                        <input id="slot-minutes" name="slot_minutes" type="number" min="15" value="30" required>
                    </div>
                    <div class="admin-field">
                        <label for="gap-minutes">Gap between slots</label>
                        <input id="gap-minutes" name="gap_minutes" type="number" min="0" value="0" required>
                    </div>
                    <div class="admin-field admin-span-3">
                        <label for="bulk-notes">Internal notes</label>
                        <textarea id="bulk-notes" name="notes" rows="3" placeholder="Shared instructions for the generated block."></textarea>
                    </div>
                </div>
                <div class="admin-form-actions">
                    <button type="submit" class="solid-button">Generate slot series</button>
                </div>
            </form>
        </section>
    </div>

    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Open schedule</span>
                <h2>Upcoming slots still available</h2>
            </div>
        </div>

        <?php if ($tourSlots === array()): ?>
            <article class="empty-state">
                <h2>No open slots yet.</h2>
                <p>Create one-off or generated availability above and new openings will appear here.</p>
            </article>
        <?php else: ?>
            <div class="tour-slot-list">
                <?php foreach ($tourSlots as $tourSlot): ?>
                    <article class="tour-slot-card">
                        <div>
                            <h3><?= htmlspecialchars((string) $tourSlot['property']['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="muted-text"><?= htmlspecialchars((string) $tourSlot['label'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="muted-text"><?= htmlspecialchars((string) $tourSlot['property']['location'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if ($tourSlot['notes'] !== ''): ?>
                                <p><?= htmlspecialchars((string) $tourSlot['notes'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                        <form action="<?= htmlspecialchars(app_url('admin-tour-slot-delete'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                            <input type="hidden" name="slot_id" value="<?= (int) $tourSlot['id'] ?>">
                            <button type="submit" class="ghost-button">Remove slot</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Upcoming requests</span>
                <h2>Confirm and manage renter tour bookings</h2>
            </div>
        </div>

        <?php if ($tourRequests === array()): ?>
            <article class="empty-state">
                <h2>No upcoming tour requests yet.</h2>
                <p>Once renters choose a slot from a property page, the request will appear here for confirmation.</p>
            </article>
        <?php else: ?>
            <div class="results-list">
                <?php foreach ($tourRequests as $tourRequest): ?>
                    <article class="result-card">
                        <div class="result-card-body result-card-body-wide">
                            <div class="result-card-top">
                                <div>
                                    <h2><?= htmlspecialchars((string) $tourRequest['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p class="muted-text"><?= htmlspecialchars((string) $tourRequest['fullName'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $tourRequest['email'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <span class="admin-status-pill <?= htmlspecialchars((string) $tourRequest['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ucfirst($tourRequest['status']), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p>Slot: <?= htmlspecialchars((string) $tourRequest['slotLabel'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if ($tourRequest['message'] !== ''): ?>
                                <p><?= htmlspecialchars((string) $tourRequest['message'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <form action="<?= htmlspecialchars(app_url('admin-tour-request-update'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="tour-request-actions">
                                <input type="hidden" name="tour_request_id" value="<?= (int) $tourRequest['id'] ?>">
                                <div class="admin-field">
                                    <label for="tour-notes-<?= (int) $tourRequest['id'] ?>">Admin notes</label>
                                    <textarea id="tour-notes-<?= (int) $tourRequest['id'] ?>" name="admin_notes" rows="3"><?= htmlspecialchars((string) $tourRequest['adminNotes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div class="tour-inline-actions">
                                    <?php if ($tourRequest['status'] === 'requested'): ?>
                                        <button type="submit" class="solid-button" name="status" value="confirmed">Confirm tour</button>
                                        <button type="submit" class="ghost-button" name="status" value="cancelled">Cancel request</button>
                                    <?php elseif ($tourRequest['status'] === 'confirmed'): ?>
                                        <button type="submit" class="solid-button" name="status" value="completed">Mark completed</button>
                                        <button type="submit" class="ghost-button" name="status" value="cancelled">Cancel request</button>
                                    <?php else: ?>
                                        <span class="muted-text">This request is already <?= htmlspecialchars((string) $tourRequest['status'], ENT_QUOTES, 'UTF-8') ?>.</span>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

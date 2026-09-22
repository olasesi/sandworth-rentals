<?php
/** @var array $portfolio */
/** @var array $property */
/** @var array $units */
/** @var string $unitTable */
/** @var array|null $editUnit */
/** @var array $tenantUsers */
/** @var int $page */
/** @var int $perPage */
/** @var int $totalUnits */
/** @var int $totalPages */

$editUnit = isset($editUnit) ? $editUnit : null;
$page = isset($page) ? max(1, (int) $page) : 1;
$perPage = isset($perPage) ? max(1, (int) $perPage) : 25;
$totalUnits = isset($totalUnits) ? (int) $totalUnits : count($units);
$totalPages = isset($totalPages) ? max(1, (int) $totalPages) : 1;
$isMall = $unitTable === 'mall_shops';
$isApartment = $unitTable === 'apartment_flats';
$isResidential = $unitTable === 'residential_units';

$unitLabel = $isMall ? 'shop' : ($isApartment ? 'flat' : 'unit');
$unitLabelPlural = $isMall ? 'shops' : ($isApartment ? 'flats' : 'units');

$formTitle = $editUnit ? 'Edit ' . $unitLabel . ' record' : 'Add a ' . $unitLabel;
$formButton = $editUnit ? 'Save changes' : 'Add ' . $unitLabel;

$activeAdminPage = 'inventory';
$adminTitle = ($isMall ? 'Shops' : ucfirst($unitLabelPlural)) . ' inside ' . $property['title'] . '.';
$adminDescription = 'Manage the individual ' . $unitLabelPlural . ', stores, or flats inside this property, with each tenant, tenure, and rent details.';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><?= $isMall ? 'Mall shops' : ($isApartment ? 'Apartment flats' : 'Residential units') ?></span>
                <h2><?= htmlspecialchars((string) $property['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="muted-text"><?= htmlspecialchars((string) $property['location'], ENT_QUOTES, 'UTF-8') ?> &mdash; <?= htmlspecialchars((string) $property['type'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="admin-page-actions">
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-properties', array('edit_property' => $property['id'])), ENT_QUOTES, 'UTF-8') ?>">Edit property</a>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-inventory'), ENT_QUOTES, 'UTF-8') ?>">Back to inventory</a>
            </div>
        </div>

        <?php if ($editUnit): ?>
            <div class="admin-editor-state">
                <span class="type-pill">Editing <?= $unitLabel ?> #<?= (int) $editUnit['id'] ?></span>
                <p class="muted-text">Update the owner, tenure, and rent details for this <?= $unitLabel ?>.</p>
            </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars(app_url('admin-unit-save'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-form">
            <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">
            <?php if ($editUnit): ?>
                <input type="hidden" name="unit_id" value="<?= (int) $editUnit['id'] ?>">
            <?php endif; ?>

            <div class="admin-form-grid">
                <?php if ($isMall): ?>
                    <div class="admin-field">
                        <label for="unit-shop-number">Shop number</label>
                        <input id="unit-shop-number" name="shop_number" type="text" placeholder="Shop 12" value="<?= $editUnit ? htmlspecialchars((string) $editUnit['shopNumber'], ENT_QUOTES, 'UTF-8') : '' ?>">
                    </div>
                    <div class="admin-field">
                        <label for="unit-shop-name">Shop name</label>
                        <input id="unit-shop-name" name="shop_name" type="text" placeholder="Soleil Fashion" value="<?= $editUnit ? htmlspecialchars((string) $editUnit['shopName'], ENT_QUOTES, 'UTF-8') : '' ?>">
                    </div>
                <?php elseif ($isApartment): ?>
                    <div class="admin-field">
                        <label for="unit-block">Block</label>
                        <input id="unit-block" name="block" type="text" placeholder="Block A" value="<?= $editUnit ? htmlspecialchars((string) $editUnit['block'], ENT_QUOTES, 'UTF-8') : '' ?>">
                    </div>
                    <div class="admin-field">
                        <label for="unit-floor">Floor</label>
                        <input id="unit-floor" name="floor" type="text" placeholder="2" value="<?= $editUnit ? htmlspecialchars((string) $editUnit['floor'], ENT_QUOTES, 'UTF-8') : '' ?>">
                    </div>
                    <div class="admin-field">
                        <label for="unit-flat-number">Flat number</label>
                        <input id="unit-flat-number" name="flat_number" type="text" placeholder="Flat 3" value="<?= $editUnit ? htmlspecialchars((string) $editUnit['flatNumber'], ENT_QUOTES, 'UTF-8') : '' ?>">
                    </div>
                <?php else: ?>
                    <div class="admin-field">
                        <label for="unit-block">Block</label>
                        <input id="unit-block" name="block" type="text" placeholder="Block B" value="<?= $editUnit ? htmlspecialchars((string) $editUnit['block'], ENT_QUOTES, 'UTF-8') : '' ?>">
                    </div>
                    <div class="admin-field">
                        <label for="unit-unit-number">Unit number</label>
                        <input id="unit-unit-number" name="unit_number" type="text" placeholder="Unit 7" value="<?= $editUnit ? htmlspecialchars((string) $editUnit['unitNumber'], ENT_QUOTES, 'UTF-8') : '' ?>">
                    </div>
                <?php endif; ?>

                <div class="admin-field admin-span-<?= $isMall || $isApartment ? 3 : 2 ?>">
                    <label for="unit-user-id"><?= $isMall ? 'Shop / store owner (tenant)' : 'Occupant (tenant)' ?></label>
                    <select id="unit-user-id" name="user_id" required>
                        <option value="">Select the tenant occupying this <?= $unitLabel ?></option>
                        <?php foreach ($tenantUsers as $tenantUser): ?>
                            <option value="<?= (int) $tenantUser['id'] ?>" <?= $editUnit && (int) $editUnit['userId'] === (int) $tenantUser['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $tenantUser['name'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $tenantUser['email'], ENT_QUOTES, 'UTF-8') ?><?= $tenantUser['phone'] !== '' ? ' | ' . htmlspecialchars((string) $tenantUser['phone'], ENT_QUOTES, 'UTF-8') : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="muted-text" style="font-size:.8rem; margin-top:.25rem">The tenant's name, email, and phone come from their account. Register or update tenants on the <a href="<?= htmlspecialchars(app_url('admin-tenants'), ENT_QUOTES, 'UTF-8') ?>">Tenants</a> page.</p>
                </div>

                <div class="admin-field">
                    <label for="unit-tenure">Tenure / term</label>
                    <input id="unit-tenure" name="tenure" type="text" placeholder="Twelve months, 3 years..." value="<?= $editUnit ? htmlspecialchars((string) $editUnit['tenure'], ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>

                <div class="admin-field">
                    <label for="unit-monthly-rent">Rent amount</label>
                    <input id="unit-monthly-rent" name="monthly_rent" type="number" min="0" placeholder="0" value="<?= $editUnit ? (int) $editUnit['monthlyRent'] : '' ?>">
                </div>

                <div class="admin-field">
                    <label for="unit-service-charge">Service charge</label>
                    <input id="unit-service-charge" name="service_charge" type="number" min="0" placeholder="0" value="<?= $editUnit ? (int) $editUnit['serviceCharge'] : 0 ?>">
                </div>

                <div class="admin-field">
                    <label for="unit-status">Status</label>
                    <select id="unit-status" name="status">
                        <option value="active" <?= $editUnit && $editUnit['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $editUnit && $editUnit['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="vacant" <?= $editUnit && $editUnit['status'] === 'vacant' ? 'selected' : '' ?>>Vacant</option>
                        <option value="ended" <?= $editUnit && $editUnit['status'] === 'ended' ? 'selected' : '' ?>>Ended</option>
                    </select>
                </div>

                <div class="admin-field admin-span-3">
                    <label for="unit-notes">Notes</label>
                    <textarea id="unit-notes" name="notes" rows="3" placeholder="Additional details about this <?= $unitLabel ?> such as use, fittings, or agreements."><?= $editUnit ? htmlspecialchars((string) $editUnit['notes'], ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="solid-button"><?= htmlspecialchars($formButton, ENT_QUOTES, 'UTF-8') ?></button>
                <?php if ($editUnit): ?>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-units', array('property_id' => $property['id'])), ENT_QUOTES, 'UTF-8') ?>">Cancel editing</a>
                    <form action="<?= htmlspecialchars(app_url('admin-unit-delete'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-inline-form" onsubmit="return confirm('Delete this <?= $unitLabel ?> record? This cannot be undone.');">
                        <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">
                        <input type="hidden" name="unit_id" value="<?= (int) $editUnit['id'] ?>">
                        <button type="submit" class="admin-icon-button admin-icon-button-danger" aria-label="Delete <?= $unitLabel ?>" title="Delete <?= $unitLabel ?>">
                            <!-- Trash / delete icon -->
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                <path d="M10 11v6M14 11v6"/>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                            </svg>
                            Delete
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><?= ucfirst($unitLabelPlural) ?></span>
                <h2>Registered <?= $unitLabelPlural ?> in this property</h2>
            </div>
        </div>

        <?php if ($units === array()): ?>
            <article class="empty-state">
                <h2>No <?= $unitLabelPlural ?> registered yet.</h2>
                <p>Use the form above to add the first <?= $unitLabel ?> with its tenant, tenure, and rent details.</p>
            </article>
        <?php else: ?>
            <div class="record-table">
                <div class="record-table-row record-table-head">
                    <span><?= $isMall ? 'Shop' : ($isApartment ? 'Flat' : 'Unit') ?></span>
                    <span>Owner / occupant</span>
                    <span>Tenure</span>
                    <span>Rent</span>
                    <span>Status</span>
                    <span>Actions</span>
                </div>
                <?php foreach ($units as $unit): ?>
                    <?php
                        $detailHref = app_url('admin-unit-detail', array('property_id' => $property['id'], 'unit_id' => $unit['id']));
                    ?>
                    <div class="record-table-row">
                        <span>
                            <a href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8') ?>" class="admin-unit-link">
                                <?php if ($isMall): ?>
                                    <strong><?= htmlspecialchars((string) $unit['shopName'] !== '' ? $unit['shopName'] : ($unit['shopNumber'] !== '' ? 'Shop ' . $unit['shopNumber'] : 'Unnamed shop'), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="muted-text" style="display:block; font-size:.8rem"><?= $unit['shopNumber'] !== '' ? 'Shop ' . htmlspecialchars((string) $unit['shopNumber'], ENT_QUOTES, 'UTF-8') : '' ?></span>
                                <?php elseif ($isApartment): ?>
                                    <strong><?= htmlspecialchars((string) $unit['flatNumber'] !== '' ? $unit['flatNumber'] : 'Flat #' . $unit['id'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="muted-text" style="display:block; font-size:.8rem"><?= trim((string) $unit['block'] . ($unit['floor'] !== '' ? ' | Floor ' . $unit['floor'] : '')) ?></span>
                                <?php else: ?>
                                    <strong><?= htmlspecialchars((string) $unit['unitNumber'] !== '' ? $unit['unitNumber'] : 'Unit #' . $unit['id'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars((string) $unit['block'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </a>
                        </span>
                        <span>
                            <strong><?= htmlspecialchars((string) ($unit['ownerName'] !== '' ? $unit['ownerName'] : '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars(trim((string) $unit['ownerPhone'] . ($unit['ownerEmail'] !== '' ? ' | ' . $unit['ownerEmail'] : '')), ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                        <span>
                            <strong><?= htmlspecialchars((string) ($unit['tenure'] !== '' ? $unit['tenure'] : '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if ($unit['serviceCharge'] > 0): ?>
                                <span class="muted-text" style="display:block; font-size:.8rem">Service charge <?= htmlspecialchars(app_currency($unit['serviceCharge']), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </span>
                        <span style="font-weight:700"><?= htmlspecialchars(app_currency($unit['monthlyRent']), ENT_QUOTES, 'UTF-8') ?></span>
                        <span>
                            <span class="admin-status-pill <?= htmlspecialchars((string) $unit['status'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) ucfirst($unit['status']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </span>
                        <span class="admin-table-actions">
                            <a class="admin-icon-button admin-icon-button-edit" href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8') ?>" aria-label="View <?= $unitLabel ?> details" title="View <?= $unitLabel ?> details">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                View
                            </a>
                            <a class="admin-icon-button admin-icon-button-edit" href="<?= htmlspecialchars(app_url('admin-units', array('property_id' => $property['id'], 'edit_unit' => $unit['id'])), ENT_QUOTES, 'UTF-8') ?>" aria-label="Edit <?= $unitLabel ?>" title="Edit <?= $unitLabel ?>">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Edit
                            </a>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="admin-pagination" aria-label="Unit pages">
                    <?php if ($page > 1): ?>
                        <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-units', array('property_id' => $property['id'], 'pgn' => $page - 1)), ENT_QUOTES, 'UTF-8') ?>">Previous</a>
                    <?php else: ?>
                        <span class="ghost-button ghost-button-disabled">Previous</span>
                    <?php endif; ?>

                    <span class="admin-pagination-info">
                        Page <?= (int) $page ?> of <?= (int) $totalPages ?> &middot; <?= (int) $totalUnits ?> total
                    </span>

                    <?php if ($page < $totalPages): ?>
                        <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-units', array('property_id' => $property['id'], 'pgn' => $page + 1)), ENT_QUOTES, 'UTF-8') ?>">Next</a>
                    <?php else: ?>
                        <span class="ghost-button ghost-button-disabled">Next</span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</section>
<?php
/** @var array $properties */
/** @var array $portfolio */

$activeAdminPage = 'inventory';
$adminTitle = 'See every loaded property on its own inventory page.';
$adminDescription = 'Browse the live property table without the property editor and editorial tools making the page unnecessarily long.';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Loaded properties</span>
                <h2>Live property inventory</h2>
            </div>
            <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-properties'), ENT_QUOTES, 'UTF-8') ?>">Load or edit property</a>
        </div>

        <div class="admin-inventory-table">
            <div class="admin-inventory-row admin-inventory-head">
                <div>Listing</div>
                <div>Purpose</div>
                <div>Price</div>
                <div>Media</div>
                <div>Status</div>
                <div>Actions</div>
            </div>

            <?php foreach ($properties as $property): ?>
                <div class="admin-inventory-row">
                    <div class="admin-property-cell">
                        <img src="<?= htmlspecialchars((string) $property['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $property['title'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="admin-property-summary">
                            <strong><?= htmlspecialchars((string) $property['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= htmlspecialchars((string) $property['location'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= htmlspecialchars((string) $property['type'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= (int) $property['beds'] ?> bed | <?= htmlspecialchars((string) $property['baths'], ENT_QUOTES, 'UTF-8') ?> bath</span>
                        </div>
                    </div>
                    <div class="admin-inventory-value">
                        <span class="type-pill"><?= htmlspecialchars((string) ucfirst($property['purpose']), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="admin-inventory-value admin-price-cell">
                        <strong><?= htmlspecialchars((string) $property['price'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="admin-inventory-value">
                        <strong><?= is_array($property['images']) ? count($property['images']) : 0 ?> images</strong>
                        <span>Main + gallery</span>
                    </div>
                    <div class="admin-inventory-value">
                        <span class="type-pill"><?= htmlspecialchars((string) ucfirst($property['status']), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="admin-table-actions">
                        <a class="admin-icon-button admin-icon-button-edit" href="<?= htmlspecialchars(app_url('admin-units', array('property_id' => $property['id'])), ENT_QUOTES, 'UTF-8') ?>" aria-label="Manage units" title="Manage shops, flats or units">
                            <?php
                                $typeKey = strtolower((string) $property['type']);

                                if (strpos($typeKey, 'mall') !== false) {
                                    $unitLinkLabel = 'Shops';
                                } elseif (strpos($typeKey, 'apartment') !== false) {
                                    $unitLinkLabel = 'Flats';
                                } else {
                                    $unitLinkLabel = 'Units';
                                }
                            ?>
                            <?= htmlspecialchars($unitLinkLabel, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <a class="admin-icon-button admin-icon-button-edit" href="<?= htmlspecialchars(app_url('admin-properties', array('edit_property' => $property['id'])), ENT_QUOTES, 'UTF-8') ?>" aria-label="Edit listing" title="Edit listing">
                            <!-- Pencil / edit icon -->
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            Edit
                        </a>
                        <form action="<?= htmlspecialchars(app_url('admin-property-delete'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-inline-form" onsubmit="return confirm('Delete this property from the inventory? This cannot be undone.');">
                            <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">
                            <button type="submit" class="admin-icon-button admin-icon-button-danger" aria-label="Delete property" title="Delete property">
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
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($properties === array()): ?>
                <div class="admin-inventory-row">
                    <div class="admin-empty-inline">No properties have been loaded yet.</div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</section>

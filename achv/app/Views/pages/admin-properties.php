<?php
/** @var array|null $editProperty */
/** @var array $portfolio */

$editProperty = isset($editProperty) ? $editProperty : null;
$propertyFormAction = $editProperty ? app_url('admin-property-update') : app_url('admin-property-create');
$propertyFormTitle = $editProperty ? 'Edit property' : 'Load property';
$propertyFormButton = $editProperty ? 'Save changes' : 'Load property';
$galleryPreviewImages = array();
$galleryValue = '';

if ($editProperty && isset($editProperty['images']) && is_array($editProperty['images'])) {
    $galleryPreviewImages = array_values(array_filter($editProperty['images'], function ($image) use ($editProperty) {
        return trim((string) $image) !== '' && trim((string) $image) !== trim((string) $editProperty['image']);
    }));
    $galleryValue = implode(PHP_EOL, $galleryPreviewImages);
}

$activeAdminPage = 'properties';
$adminTitle = $editProperty ? 'Edit property details.' : 'Add new properties for rent, sale, or commercial lease.';
$adminDescription = 'Use this screen for listing creation and updates only, with room for pricing, media, service charges, and gallery management.';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><?= htmlspecialchars($propertyFormTitle, ENT_QUOTES, 'UTF-8') ?></span>
                <h2><?= $editProperty ? 'Update listing details, price points, and all attached images.' : 'Create a new property listing for rent, sale, or commercial lease.' ?></h2>
            </div>
            <div class="admin-page-actions">
                <?php if ($editProperty): ?>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-properties'), ENT_QUOTES, 'UTF-8') ?>">Load new property</a>
                <?php endif; ?>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-inventory'), ENT_QUOTES, 'UTF-8') ?>">View loaded properties</a>
            </div>
        </div>

        <?php if ($editProperty): ?>
            <div class="admin-editor-state">
                <span class="type-pill">Editing property #<?= (int) $editProperty['id'] ?></span>
                <p class="muted-text">Remove any image path from the gallery field below if you want that image removed from the property gallery on save.</p>
            </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($propertyFormAction, ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data" class="admin-form">
            <?php if ($editProperty): ?>
                <input type="hidden" name="property_id" value="<?= (int) $editProperty['id'] ?>">
            <?php endif; ?>

            <div class="admin-form-grid">
                <div class="admin-field">
                    <label for="property-purpose">Purpose</label>
                    <select id="property-purpose" name="purpose" required>
                        <option value="rent" <?= $editProperty && $editProperty['purpose'] === 'rent' ? 'selected' : '' ?>>Rent</option>
                        <option value="sale" <?= $editProperty && $editProperty['purpose'] === 'sale' ? 'selected' : '' ?>>Sale</option>
                        <option value="commercial" <?= $editProperty && $editProperty['purpose'] === 'commercial' ? 'selected' : '' ?>>Commercial</option>
                    </select>
                </div>

                <div class="admin-field">
                    <label for="property-type">Type</label>
                    <input id="property-type" name="type" type="text" placeholder="Apartment" value="<?= $editProperty ? htmlspecialchars((string) $editProperty['type'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
                </div>

                <div class="admin-field">
                    <label for="property-location">Location</label>
                    <input id="property-location" name="location" type="text" value="<?= $editProperty ? htmlspecialchars((string) $editProperty['location'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
                </div>

                <div class="admin-field admin-span-3">
                    <label for="property-title">Title</label>
                    <input id="property-title" name="title" type="text" value="<?= $editProperty ? htmlspecialchars((string) $editProperty['title'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
                </div>

                <div class="admin-field">
                    <label for="property-beds">Beds</label>
                    <input id="property-beds" name="beds" type="number" min="0" value="<?= $editProperty ? (int) $editProperty['beds'] : 0 ?>">
                </div>

                <div class="admin-field">
                    <label for="property-baths">Baths</label>
                    <input id="property-baths" name="baths" type="text" value="<?= $editProperty ? htmlspecialchars((string) $editProperty['baths'], ENT_QUOTES, 'UTF-8') : '0' ?>">
                </div>

                <div class="admin-field">
                    <label for="property-area">Area</label>
                    <input id="property-area" name="area" type="text" placeholder="1,450 sqft" value="<?= $editProperty ? htmlspecialchars((string) $editProperty['area'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
                </div>

                <div class="admin-field">
                    <label for="property-rent">Annual rent</label>
                    <input id="property-rent" name="monthly_rent" type="number" min="0" placeholder="For rentals or lease billing" value="<?= $editProperty ? (int) $editProperty['monthlyRent'] : '' ?>">
                </div>

                <div class="admin-field">
                    <label for="property-asking-price">Asking price</label>
                    <input id="property-asking-price" name="asking_price" type="number" min="0" placeholder="For sale or commercial" value="<?= $editProperty ? (int) $editProperty['askingPrice'] : '' ?>">
                </div>

                <div class="admin-field">
                    <label for="property-service-charge">Service charge</label>
                    <input id="property-service-charge" name="service_charge" type="number" min="0" value="<?= $editProperty ? (int) $editProperty['serviceCharge'] : 0 ?>" required>
                </div>

                <div class="admin-field">
                    <label for="property-security-deposit">Caution deposit</label>
                    <input id="property-security-deposit" name="security_deposit" type="number" min="0" value="<?= $editProperty ? (int) $editProperty['securityDeposit'] : app_caution_deposit_default() ?>" required>
                    <p class="muted-text">Defaults to <?= htmlspecialchars(app_currency(app_caution_deposit_default()), ENT_QUOTES, 'UTF-8') ?> for rentals and lease spaces.</p>
                </div>

                <div class="admin-field">
                    <label for="property-available-date">Available date</label>
                    <input id="property-available-date" name="available_date" type="text" placeholder="Available Aug 20, 2026" value="<?= $editProperty ? htmlspecialchars((string) $editProperty['availableDate'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
                </div>

                <div class="admin-field">
                    <label for="property-commercial-type">Commercial label</label>
                    <input id="property-commercial-type" name="commercial_type" type="text" placeholder="Shopping Mall, Retail Plaza" value="<?= $editProperty ? htmlspecialchars((string) $editProperty['commercialType'], ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>

                <div class="admin-field">
                    <label for="property-lease-term">Lease term</label>
                    <input id="property-lease-term" name="lease_term" type="text" placeholder="5-10 year lease" value="<?= $editProperty ? htmlspecialchars((string) $editProperty['leaseTerm'], ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>

                <div class="admin-field">
                    <label for="property-units">Units</label>
                    <input id="property-units" name="units" type="number" min="0" value="<?= $editProperty ? (int) $editProperty['units'] : 0 ?>">
                </div>

                <div class="admin-field">
                    <label for="property-floors">Floors</label>
                    <input id="property-floors" name="floors" type="number" min="0" value="<?= $editProperty ? (int) $editProperty['floors'] : 0 ?>">
                </div>

                <div class="admin-field">
                    <label for="property-lat">Latitude</label>
                    <input id="property-lat" name="lat" type="text" value="<?= $editProperty ? htmlspecialchars((string) $editProperty['lat'], ENT_QUOTES, 'UTF-8') : '6.4474' ?>">
                </div>

                <div class="admin-field">
                    <label for="property-lng">Longitude</label>
                    <input id="property-lng" name="lng" type="text" value="<?= $editProperty ? htmlspecialchars((string) $editProperty['lng'], ENT_QUOTES, 'UTF-8') : '3.4724' ?>">
                </div>

                <div class="admin-field admin-span-3">
                    <label for="property-summary">Summary</label>
                    <textarea id="property-summary" name="summary" rows="4" required><?= $editProperty ? htmlspecialchars((string) $editProperty['summary'], ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                </div>

                <div class="admin-field admin-span-3">
                    <label for="property-features">Features</label>
                    <input id="property-features" name="features" type="text" placeholder="Gym, Backup power, CCTV" value="<?= $editProperty ? htmlspecialchars(implode(', ', $editProperty['features']), ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>

                <div class="admin-field admin-span-3">
                    <label for="property-badges">Badges</label>
                    <input id="property-badges" name="badges" type="text" placeholder="Verified listing, Apply online" value="<?= $editProperty ? htmlspecialchars(implode(', ', $editProperty['badges']), ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>

                <div class="admin-field admin-span-2">
                    <label for="property-image-file"><?= $editProperty ? 'Replace main image' : 'Main image' ?></label>
                    <input id="property-image-file" name="image_file" type="file" accept="image/*" <?= $editProperty ? '' : 'required' ?>>
                </div>

                <div class="admin-field">
                    <label for="property-gallery-files"><?= $editProperty ? 'Add more gallery images' : 'Gallery images' ?></label>
                    <input id="property-gallery-files" name="gallery_files[]" type="file" accept="image/*" multiple>
                </div>

                <div class="admin-field admin-span-3">
                    <label for="property-images">Gallery image paths or URLs</label>
                    <textarea id="property-images" name="images" rows="7" placeholder="/public/assets/uploads/rentals/example-image.jpg&#10;https://example.com/property-02.jpg"><?= htmlspecialchars($galleryValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <p class="muted-text">Use one image per line or comma-separated values. Uploaded files are saved automatically into the route folder for homes, rentals, or commercial listings.</p>
                </div>

                <div class="admin-field admin-span-3">
                    <label class="check-row">
                        <input type="checkbox" name="pet_friendly" value="1" <?= $editProperty && ! empty($editProperty['petFriendly']) ? 'checked' : '' ?>>
                        <span>Pet-friendly</span>
                    </label>
                </div>
            </div>

            <div class="admin-media-grid">
                <article class="detail-card admin-preview-card">
                    <span class="eyebrow">Main image</span>
                    <?php if ($editProperty): ?>
                        <img src="<?= htmlspecialchars((string) $editProperty['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $editProperty['title'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php else: ?>
                        <div class="admin-preview-empty">
                            <strong>Main cover image</strong>
                            <p>Upload a bold hero photo for the listing card and property detail page.</p>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="detail-card admin-preview-card">
                    <span class="eyebrow">Gallery preview</span>
                    <?php if ($galleryPreviewImages !== array()): ?>
                        <div class="admin-gallery-grid">
                            <?php foreach ($galleryPreviewImages as $galleryImage): ?>
                                <figure>
                                    <img src="<?= htmlspecialchars((string) $galleryImage, ENT_QUOTES, 'UTF-8') ?>" alt="Gallery image for <?= htmlspecialchars((string) $editProperty['title'], ENT_QUOTES, 'UTF-8') ?>">
                                    <figcaption><?= htmlspecialchars((string) basename((string) $galleryImage), ENT_QUOTES, 'UTF-8') ?></figcaption>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-preview-empty">
                            <strong>Large galleries supported</strong>
                            <p>Add as many property photos as needed. Upload several files at once or paste saved image paths into the gallery field.</p>
                        </div>
                    <?php endif; ?>
                </article>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="solid-button"><?= htmlspecialchars($propertyFormButton, ENT_QUOTES, 'UTF-8') ?></button>
                <?php if ($editProperty): ?>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-properties'), ENT_QUOTES, 'UTF-8') ?>">Cancel editing</a>
                    <form action="<?= htmlspecialchars(app_url('admin-property-delete'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-inline-form" onsubmit="return confirm('Delete this property from the inventory? This cannot be undone.');">
                        <input type="hidden" name="property_id" value="<?= (int) $editProperty['id'] ?>">
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
                <?php endif; ?>
            </div>
        </form>
    </section>
</section>

<?php
/** @var array $properties */
/** @var array $applications */
/** @var array $portfolio */
/** @var string $currentCurrencyCode */
/** @var array|null $editProperty */

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
?>
<section class="admin-header" id="admin-overview">
    <div class="admin-header-content">
        <span class="eyebrow">Admin console</span>
        <h1>Manage listings, media, pricing, renters, and tenancy operations.</h1>
        <p>Admins Operations desk | Loading properties, Update pricing and images, Review rental applications.</p>
    </div>
    <div class="admin-header-tabs">
        <a href="#admin-overview">Overview</a>
        <a href="#admin-listing-editor">Listing editor</a>
        <a href="#admin-applications">Applications</a>
        <a href="#admin-inventory">Inventory</a>
    </div>
</section>

<section class="manager-stats">
    <article>
        <span>Active properties</span>
        <strong><?= (int) $portfolio['activeProperties'] ?></strong>
    </article>
    <article>
        <span>Submitted applications</span>
        <strong><?= (int) $portfolio['submittedApplications'] ?></strong>
    </article>
    <article>
        <span>Approved or active</span>
        <strong><?= (int) $portfolio['approvedApplications'] ?></strong>
    </article>
</section>

<section class="admin-workspace">
    <div class="admin-toolbar">
        <div>
            <span class="eyebrow">Workspace settings</span>
            <h2>Operations controls</h2>
            <p>Switch the public display currency instantly and keep the listing desk ready for both rental and sales inventory.</p>
        </div>
        <form action="<?= htmlspecialchars(app_url('admin-currency-update'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-toolbar-form">
            <div class="admin-field">
                <label for="currency-code">Display currency</label>
                <select id="currency-code" name="currency_code" required>
                    <option value="USD" <?= $currentCurrencyCode === 'USD' ? 'selected' : '' ?>>Dollar ($)</option>
                    <option value="NGN" <?= $currentCurrencyCode === 'NGN' ? 'selected' : '' ?>>Naira (&#8358;)</option>
                </select>
            </div>
            <button type="submit" class="ghost-button">Update currency</button>
        </form>
    </div>

    <section class="admin-section" id="admin-listing-editor">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><?= htmlspecialchars($propertyFormTitle, ENT_QUOTES, 'UTF-8') ?></span>
                <h2><?= $editProperty ? 'Update listing details, price points, and all attached images.' : 'Create a new property listing for rent, sale, or commercial lease.' ?></h2>
            </div>
            <?php if ($editProperty): ?>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin'), ENT_QUOTES, 'UTF-8') ?>">Load new property</a>
            <?php endif; ?>
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
                    <label for="property-rent">Monthly rent</label>
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
                    <label for="property-security-deposit">Security deposit</label>
                    <input id="property-security-deposit" name="security_deposit" type="number" min="0" value="<?= $editProperty ? (int) $editProperty['securityDeposit'] : 0 ?>" required>
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
                    <textarea id="property-images" name="images" rows="7" placeholder="public/assets/uploads/rentals/example-image.jpg&#10;https://example.com/property-02.jpg"><?= htmlspecialchars($galleryValue, ENT_QUOTES, 'UTF-8') ?></textarea>
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
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin'), ENT_QUOTES, 'UTF-8') ?>">Cancel editing</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="admin-section" id="admin-applications">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Applications</span>
                <h2>Review renter pipeline</h2>
            </div>
        </div>

        <div class="results-list">
            <?php foreach ($applications as $application): ?>
                <article class="result-card">
                    <div class="result-card-body result-card-body-wide">
                        <div class="result-card-top">
                            <div>
                                <h2><?= htmlspecialchars((string) $application['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="muted-text"><?= htmlspecialchars((string) $application['user']['name'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $application['user']['email'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="type-pill"><?= htmlspecialchars((string) ucfirst($application['status']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p>Income: <?= htmlspecialchars((string) $application['annualIncome'], ENT_QUOTES, 'UTF-8') ?> | Employer: <?= htmlspecialchars((string) $application['employer'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p>Move-in: <?= htmlspecialchars((string) $application['moveInDate'], ENT_QUOTES, 'UTF-8') ?> | Occupants: <?= (int) $application['occupants'] ?></p>
                        <p><?= htmlspecialchars((string) $application['notes'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if ($application['status'] === 'submitted'): ?>
                            <form action="<?= htmlspecialchars(app_url('application-approve'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                                <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                                <button type="submit" class="solid-button">Approve application</button>
                            </form>
                        <?php else: ?>
                            <span class="muted-text">Approved tenants can now pay online and move into the tenancy app.</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if ($applications === array()): ?>
                <article class="empty-state">
                    <h2>No applications yet.</h2>
                    <p>As users sign in and apply for rent, their records will appear here for approval.</p>
                </article>
            <?php endif; ?>
        </div>
    </section>

    <section class="admin-section" id="admin-inventory">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Loaded properties</span>
                <h2>Live property inventory</h2>
            </div>
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
                        <div>
                            <strong><?= htmlspecialchars((string) $property['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= htmlspecialchars((string) $property['location'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= htmlspecialchars((string) $property['type'], ENT_QUOTES, 'UTF-8') ?> | <?= (int) $property['beds'] ?> bed | <?= htmlspecialchars((string) $property['baths'], ENT_QUOTES, 'UTF-8') ?> bath</span>
                        </div>
                    </div>
                    <div><?= htmlspecialchars((string) ucfirst($property['purpose']), ENT_QUOTES, 'UTF-8') ?></div>
                    <div><?= htmlspecialchars((string) $property['price'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div><?= is_array($property['images']) ? count($property['images']) : 0 ?> images</div>
                    <div><span class="type-pill"><?= htmlspecialchars((string) ucfirst($property['status']), ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div class="admin-table-actions">
                        <a class="text-link" href="<?= htmlspecialchars(app_url('admin', array('edit_property' => $property['id'])), ENT_QUOTES, 'UTF-8') ?>">Edit listing</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</section>

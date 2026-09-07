<?php
/** @var array $portfolio */
/** @var array $siteSettings */

$activeAdminPage = 'seo';
$adminTitle = 'Manage Sandworth Homes | Marketing & SEO Admin page.';
$adminDescription = 'Update the public base URL, default meta content, social sharing image, robots policy, sitemap-ready details, and brand contact information anytime.';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Marketing &amp; SEO</span>
                <h2>Public metadata and search visibility controls</h2>
                <p class="muted-text">Page-specific SEO can still be refined inside the Editorial Pages screen by editing each page's <code>meta</code> content block. These settings control the global brand, sharing, contact, and crawl behavior.</p>
            </div>
        </div>

        <form action="<?= htmlspecialchars(app_url('admin-seo-save'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-form">
            <div class="admin-form-grid">
                <div class="admin-field admin-span-2">
                    <label for="seo-site-name">Site name</label>
                    <input id="seo-site-name" name="site_name" type="text" value="<?= htmlspecialchars((string) $siteSettings['siteName'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="admin-field">
                    <label for="seo-site-base-url">Site base URL</label>
                    <input id="seo-site-base-url" name="site_base_url" type="url" placeholder="https://rentals.sandworthproperties.ng" value="<?= htmlspecialchars((string) $siteSettings['siteBaseUrl'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="admin-field admin-span-3">
                    <label for="seo-default-meta-title">Default meta title</label>
                    <input id="seo-default-meta-title" name="default_meta_title" type="text" value="<?= htmlspecialchars((string) $siteSettings['defaultMetaTitle'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="admin-field admin-span-3">
                    <label for="seo-default-meta-description">Default meta description</label>
                    <textarea id="seo-default-meta-description" name="default_meta_description" rows="4" required><?= htmlspecialchars((string) $siteSettings['defaultMetaDescription'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="admin-field admin-span-2">
                    <label for="seo-default-share-image">Default share image path or URL</label>
                    <input id="seo-default-share-image" name="default_share_image" type="text" value="<?= htmlspecialchars((string) $siteSettings['defaultShareImage'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="admin-field">
                    <label for="seo-twitter-handle">Twitter/X handle</label>
                    <input id="seo-twitter-handle" name="twitter_handle" type="text" placeholder="@sandworthhomes" value="<?= htmlspecialchars((string) $siteSettings['twitterHandle'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="admin-field admin-span-3">
                    <label for="seo-robots-policy">Robots meta policy</label>
                    <input id="seo-robots-policy" name="robots_policy" type="text" value="<?= htmlspecialchars((string) $siteSettings['robotsPolicy'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="admin-field">
                    <label for="seo-facebook-url">Facebook URL</label>
                    <input id="seo-facebook-url" name="facebook_url" type="url" value="<?= htmlspecialchars((string) $siteSettings['facebookUrl'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="admin-field">
                    <label for="seo-instagram-url">Instagram URL</label>
                    <input id="seo-instagram-url" name="instagram_url" type="url" value="<?= htmlspecialchars((string) $siteSettings['instagramUrl'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="admin-field">
                    <label for="seo-x-url">Twitter/X URL</label>
                    <input id="seo-x-url" name="x_url" type="url" value="<?= htmlspecialchars((string) $siteSettings['xUrl'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="admin-field admin-span-3">
                    <label for="seo-linkedin-url">LinkedIn URL</label>
                    <input id="seo-linkedin-url" name="linkedin_url" type="url" value="<?= htmlspecialchars((string) $siteSettings['linkedinUrl'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="admin-field">
                    <label for="seo-contact-email">Public email</label>
                    <input id="seo-contact-email" name="contact_email" type="email" value="<?= htmlspecialchars((string) $siteSettings['contactEmail'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="admin-field">
                    <label for="seo-contact-phone">Public phone</label>
                    <input id="seo-contact-phone" name="contact_phone" type="text" value="<?= htmlspecialchars((string) $siteSettings['contactPhone'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="admin-field admin-span-3">
                    <label for="seo-operational-office">Operational office</label>
                    <textarea id="seo-operational-office" name="operational_office" rows="3" required><?= htmlspecialchars((string) $siteSettings['operationalOffice'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="admin-field admin-span-3">
                    <label for="seo-registered-office">Registered office</label>
                    <textarea id="seo-registered-office" name="registered_office" rows="3" required><?= htmlspecialchars((string) $siteSettings['registeredOffice'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="solid-button">Save marketing settings</button>
                <a class="ghost-button" href="<?= htmlspecialchars(app_public_base_path() . '/robots.txt', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open robots.txt</a>
                <a class="ghost-button" href="<?= htmlspecialchars(app_public_base_path() . '/sitemap.xml', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open sitemap.xml</a>
            </div>
        </form>
    </section>
</section>

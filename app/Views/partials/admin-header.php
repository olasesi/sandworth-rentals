<?php
$activeAdminPage = isset($activeAdminPage) ? $activeAdminPage : 'overview';
$adminTitle = isset($adminTitle) ? $adminTitle : 'Admin workspace';
$adminDescription = isset($adminDescription) ? $adminDescription : 'Manage your platform from one clear operations desk.';
$portfolio = isset($portfolio) ? $portfolio : array();
?>
<section class="admin-header">
    <div class="admin-header-content">
        <span class="eyebrow">Admin console</span>
        <h1><?= htmlspecialchars((string) $adminTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars((string) $adminDescription, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="admin-header-tabs">
        <a class="<?= $activeAdminPage === 'overview' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin'), ENT_QUOTES, 'UTF-8') ?>">Overview</a>
        <a class="<?= $activeAdminPage === 'editorial' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-editorial'), ENT_QUOTES, 'UTF-8') ?>">Editorial pages</a>
        <a class="<?= $activeAdminPage === 'seo' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-seo'), ENT_QUOTES, 'UTF-8') ?>">Marketing &amp; SEO</a>
        <a class="<?= $activeAdminPage === 'properties' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-properties'), ENT_QUOTES, 'UTF-8') ?>">Edit property</a>
        <a class="<?= $activeAdminPage === 'applications' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-applications'), ENT_QUOTES, 'UTF-8') ?>">Applications</a>
        <a class="<?= $activeAdminPage === 'tenants' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-tenants'), ENT_QUOTES, 'UTF-8') ?>">Tenants</a>
        <a class="<?= $activeAdminPage === 'charges' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-charges'), ENT_QUOTES, 'UTF-8') ?>">Charges</a>
        <a class="<?= $activeAdminPage === 'rent-due' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-rent-due'), ENT_QUOTES, 'UTF-8') ?>">Rent due<?= isset($dueUnits) ? ' (' . count($dueUnits) . ')' : '' ?></a>
        <a class="<?= $activeAdminPage === 'tours' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-tours'), ENT_QUOTES, 'UTF-8') ?>">Tours</a>
        <a class="<?= $activeAdminPage === 'inventory' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-inventory'), ENT_QUOTES, 'UTF-8') ?>">Loaded properties</a>
    </div>
</section>

<section class="manager-stats">
    <article>
        <span>Active properties</span>
        <strong><?= isset($portfolio['activeProperties']) ? (int) $portfolio['activeProperties'] : 0 ?></strong>
    </article>
    <article>
        <span>Submitted applications</span>
        <strong><?= isset($portfolio['submittedApplications']) ? (int) $portfolio['submittedApplications'] : 0 ?></strong>
    </article>
    <article>
        <span>Approved or active</span>
        <strong><?= isset($portfolio['approvedApplications']) ? (int) $portfolio['approvedApplications'] : 0 ?></strong>
    </article>
    <article>
        <span>Editorial pages</span>
        <strong><?= isset($portfolio['contentPages']) ? (int) $portfolio['contentPages'] : 0 ?></strong>
    </article>
    <article>
        <span>Content blocks</span>
        <strong><?= isset($portfolio['contentBlocks']) ? (int) $portfolio['contentBlocks'] : 0 ?></strong>
    </article>
</section>

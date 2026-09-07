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
        <a class="<?= $activeAdminPage === 'applications' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-applications'), ENT_QUOTES, 'UTF-8') ?>">Applications</a>
        <a class="<?= $activeAdminPage === 'tours' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-tours'), ENT_QUOTES, 'UTF-8') ?>">Tours</a>
        <a class="<?= $activeAdminPage === 'offers' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-offers'), ENT_QUOTES, 'UTF-8') ?>">Offers</a>
        <a class="<?= $activeAdminPage === 'messages' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-messages'), ENT_QUOTES, 'UTF-8') ?>">Inbox</a>
        <a class="<?= $activeAdminPage === 'maintenance' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-maintenance'), ENT_QUOTES, 'UTF-8') ?>">Maintenance</a>
        <a class="<?= $activeAdminPage === 'properties' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-properties'), ENT_QUOTES, 'UTF-8') ?>">Properties</a>
        <a class="<?= $activeAdminPage === 'inventory' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-inventory'), ENT_QUOTES, 'UTF-8') ?>">Inventory</a>
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
        <span>Live offers</span>
        <strong><?= isset($portfolio['activeOffers']) ? (int) $portfolio['activeOffers'] : 0 ?></strong>
    </article>
    <article>
        <span>Open maintenance</span>
        <strong><?= isset($portfolio['openMaintenance']) ? (int) $portfolio['openMaintenance'] : 0 ?></strong>
    </article>
    <article>
        <span>Unread messages</span>
        <strong><?= isset($portfolio['unreadMessages']) ? (int) $portfolio['unreadMessages'] : 0 ?></strong>
    </article>
</section>

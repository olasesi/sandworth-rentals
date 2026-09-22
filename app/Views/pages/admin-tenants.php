<?php
/** @var array $portfolio */
/** @var array $properties */
/** @var array $tenantCounts */
/** @var int $activePropertyId */
/** @var array $tenants */
/** @var int $totalTenants */
/** @var int $page */
/** @var int $perPage */
/** @var int $totalPages */
/** @var array|null $editTenant */
/** @var array|null $addedTenant */
/** @var string $pageMode */
/** @var array $oldInput */
/** @var string $activeStatusFilter */
/** @var string $searchQuery */

$editTenant = isset($editTenant) ? $editTenant : null;
$addedTenant = isset($addedTenant) ? $addedTenant : null;
$pageMode = isset($pageMode) ? $pageMode : 'register';
$oldInput = isset($oldInput) ? $oldInput : array();
$activeStatusFilter = isset($activeStatusFilter) ? $activeStatusFilter : '';
$searchQuery = isset($searchQuery) ? $searchQuery : '';
$tenantCounts = isset($tenantCounts) ? $tenantCounts : array();
$activePropertyId = isset($activePropertyId) ? (int) $activePropertyId : 0;
$tenants = isset($tenants) ? $tenants : array();
$totalTenants = isset($totalTenants) ? (int) $totalTenants : count($tenants);
$page = isset($page) ? max(1, (int) $page) : 1;
$perPage = isset($perPage) ? max(1, (int) $perPage) : 50;
$totalPages = isset($totalPages) ? max(1, (int) $totalPages) : 1;
$hasOldInput = $oldInput !== array();
$propertyCharges = isset($propertyCharges) ? $propertyCharges : array();

$toTenureYears = function ($raw) {
    $raw = strtolower(trim((string) $raw));

    if ($raw === '') {
        return 0;
    }

    if (preg_match('/^\d{1,3}$/', $raw)) {
        return (int) min(10, max(1, (int) $raw));
    }

    if (preg_match('/(\d+)\s*months?/i', $raw, $m)) {
        return (int) min(10, max(1, round((int) $m[1] / 12)));
    }

    if (preg_match('/(\d+)\s*years?/i', $raw, $m)) {
        return (int) min(10, max(1, (int) $m[1]));
    }

    return 0;
};

$unitTableForType = function ($type) {
    $type = strtolower(trim((string) $type));

    if (strpos($type, 'mall') !== false) {
        return 'mall_shops';
    }

    if (strpos($type, 'apartment') !== false) {
        return 'apartment_flats';
    }

    return 'residential_units';
};

$tenantKeyMap = array(
    'property_id' => 'propertyId',
    'tenure' => 'tenure',
    'start_date' => 'startDate',
    'end_date' => 'endDate',
    'monthly_rent' => 'monthlyRent',
    'service_charge' => 'serviceCharge',
    'security_deposit' => 'securityDeposit',
    'status' => 'status',
    'notes' => 'notes',
    'block' => 'block',
    'floor' => 'floor',
    'shop_number' => 'shopNumber',
    'shop_name' => 'shopName',
    'unit_number' => 'unitNumber',
    'flat_number' => 'flatNumber',
);

$timelineTenant = $viewTenant ? $viewTenant : ($editTenant ? $editTenant : ($addedTenant ? $addedTenant : null));
$timelineBalances = ($timelineTenant && isset($timelineTenant['balances'])) ? $timelineTenant['balances'] : array('tenureOwed' => 0, 'priorArrears' => 0, 'totalOwed' => 0);
$timelineTenureOwed = (int) $timelineBalances['tenureOwed'];
$timelinePriorArrears = (int) $timelineBalances['priorArrears'];
$timelineTotalOwed = (int) $timelineBalances['totalOwed'];
$activeTenant = $pageMode === 'summary' ? ($viewTenant ? $viewTenant : $addedTenant) : ($pageMode === 'edit' ? $editTenant : null);
$isSummary = $pageMode === 'summary';
$isEdit = $pageMode === 'edit';
$isRegister = $pageMode === 'register';
$isViewMode = $viewTenant !== null;

$formTitle = $isViewMode ? 'Tenant details' : ($isSummary ? 'Tenant registered' : ($isEdit ? 'Edit tenant registration' : 'Register a tenant'));
$formHeading = $isViewMode
    ? 'Tenant registration details.'
    : ($isSummary
        ? 'The registration has been saved. Record a payment below or register another tenant.'
        : ($isEdit ? 'Update tenant account' : 'Add the tenant account'));
$formButton = $isEdit ? 'Save changes' : 'Register tenant';
$adminTitle = $isViewMode ? 'Tenant details.' : ($isSummary ? 'Tenant registration complete.' : ($isEdit ? 'Edit tenant registration details.' : 'Registered tenants'));
$adminDescription = $isViewMode
    ? 'Tenant account, unit, tenure, rent and payments.'
    : ($isEdit
        ? 'Update the tenant account, the exact unit they rent, tenure dates, rent, service charge, and amounts paid.'
        : 'Browse tenants by property, search the register, and add new tenant registrations.');

$formValue = function ($field, $default = '') use ($hasOldInput, $oldInput, $activeTenant, $tenantKeyMap) {
    if ($hasOldInput) {
        return isset($oldInput[$field]) ? $oldInput[$field] : $default;
    }

    if ($activeTenant) {
        if (strpos($field, 'tenant_') === 0) {
            $userKey = substr($field, 7);
            return isset($activeTenant['user'][$userKey]) ? $activeTenant['user'][$userKey] : $default;
        }

        if (isset($tenantKeyMap[$field])) {
            $key = $tenantKeyMap[$field];
            return isset($activeTenant[$key]) ? $activeTenant[$key] : $default;
        }
    }

    return $default;
};

$selectedPropertyType = '';
if ($hasOldInput && isset($oldInput['property_id']) && $oldInput['property_id'] !== '') {
    foreach ($properties as $prop) {
        if ((int) $prop['id'] === (int) $oldInput['property_id']) {
            $selectedPropertyType = (string) $unitTableForType($prop['type']);
            break;
        }
    }
} elseif ($isEdit && $editTenant) {
    $selectedPropertyType = (string) $editTenant['unitTable'];
} elseif ($isSummary && $addedTenant) {
    $selectedPropertyType = (string) $addedTenant['unitTable'];
}

$tenureOptions = array('Twelve months', 'Two years', 'Three years', 'Five years');
$tenureYearsValue = $toTenureYears($formValue('tenure'));

$rentValueYearly = '';
if ($hasOldInput && isset($oldInput['monthly_rent']) && (string) $oldInput['monthly_rent'] !== '') {
    $rentValueYearly = (string) $oldInput['monthly_rent'];
} elseif (isset($activeTenant['monthlyRent']) && (int) $activeTenant['monthlyRent'] > 0) {
    $rentValueYearly = (string) ((int) round((int) $activeTenant['monthlyRent'] * 12));
}

$activeAdminPage = 'tenants';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <?php if ($isSummary && $timelineTenant): ?>
        <section class="admin-section">
            <div class="tenant-detail-bar">
                <div class="tenant-detail-bar__group">
                    <?php if ($isViewMode): ?>
                        <button type="button" class="solid-button solid-button-good-soft" id="renew-tenant-btn">Renew tenure</button>
                    <?php else: ?>
                        <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants'), ENT_QUOTES, 'UTF-8') ?>">+ Register another tenant</a>
                    <?php endif; ?>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants', array('view_tenant' => $timelineTenant['unitId'], 'unit_table' => $timelineTenant['unitTable'])), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to tenant</a>
                </div>
                <a class="solid-button solid-button-slate" href="<?= htmlspecialchars(app_url('admin-tenants', array('edit_tenant' => $timelineTenant['unitId'], 'unit_table' => $timelineTenant['unitTable'])), ENT_QUOTES, 'UTF-8') ?>">Edit this registration</a>
            </div>
            <div class="section-heading" style="margin-bottom:18px">
                <div>
                    <span class="eyebrow"><?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?></span>
                    <h2><?= htmlspecialchars($formHeading, ENT_QUOTES, 'UTF-8') ?></h2>
                </div>
                <div class="admin-page-actions">
                    <a class="solid-button" href="<?= htmlspecialchars(app_url('admin-tenant-payment-new', array('unit_table' => $timelineTenant['unitTable'], 'unit_id' => $timelineTenant['unitId'], 'type' => 'rent')), ENT_QUOTES, 'UTF-8') ?>">Record rent payment</a>
                    <a class="solid-button solid-button-navy" href="<?= htmlspecialchars(app_url('admin-tenant-payment-new', array('unit_table' => $timelineTenant['unitTable'], 'unit_id' => $timelineTenant['unitId'], 'type' => 'service_charge')), ENT_QUOTES, 'UTF-8') ?>">Record SC payment</a>
                </div>
            </div>
            <div class="admin-editor-state admin-tenant-summary">
                <span class="type-pill"><?= $isViewMode ? 'Details' : 'Summary' ?> — <?= htmlspecialchars((string) $timelineTenant['unitTable'], ENT_QUOTES, 'UTF-8') ?> #<?= (int) $timelineTenant['unitId'] ?></span>
                <div class="admin-tenant-summary-grid">
                    <div>
                        <span>Tenant</span>
                        <strong><?= htmlspecialchars((string) $timelineTenant['user']['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="muted-text"><?= htmlspecialchars((string) $timelineTenant['user']['email'] . ' | ' . $timelineTenant['user']['phone'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div>
                        <span>Property</span>
                        <strong><?= htmlspecialchars((string) ($timelineTenant['property'] ? $timelineTenant['property']['title'] : '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php
                        $detailUnitLabel = $timelineTenant['unitTable'];
                        $detailUnitSub = '';
                        if ($timelineTenant['unitTable'] === 'mall_shops') {
                            $detailUnitLabel = $timelineTenant['shopName'] !== '' ? $timelineTenant['shopName'] : ($timelineTenant['shopNumber'] !== '' ? 'Shop ' . $timelineTenant['shopNumber'] : 'Shop');
                            if ($timelineTenant['shopNumber'] !== '') { $detailUnitSub = 'Shop ' . $timelineTenant['shopNumber']; }
                        } elseif ($timelineTenant['unitTable'] === 'apartment_flats') {
                            $detailUnitLabel = $timelineTenant['flatNumber'] !== '' ? $timelineTenant['flatNumber'] : 'Flat #' . $timelineTenant['unitId'];
                            if ($timelineTenant['block'] !== '' || $timelineTenant['floor'] !== '') { $detailUnitSub = trim((string) $timelineTenant['block'] . ($timelineTenant['floor'] !== '' ? ' | Floor ' . $timelineTenant['floor'] : '')); }
                        } else {
                            $detailUnitLabel = $timelineTenant['unitNumber'] !== '' ? $timelineTenant['unitNumber'] : 'Unit #' . $timelineTenant['unitId'];
                            if ($timelineTenant['block'] !== '') { $detailUnitSub = (string) $timelineTenant['block']; }
                        }
                        ?>
                        <span class="muted-text"><?= htmlspecialchars((string) $detailUnitLabel . ($detailUnitSub !== '' ? ' — ' . $detailUnitSub : ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div>
                        <span>Tenure / term</span>
                        <strong><?= htmlspecialchars((string) $timelineTenant['tenure'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="muted-text"><?= htmlspecialchars(trim((string) $timelineTenant['startDate'] . ($timelineTenant['endDate'] !== '' ? ' → ' . $timelineTenant['endDate'] : '')), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div>
                        <span>Monthly rent</span>
                        <strong><?= htmlspecialchars(app_currency($timelineTenant['monthlyRent']), ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if ($timelineTenant['serviceCharge'] > 0): ?>
                            <span class="muted-text">Service charge <?= htmlspecialchars(app_currency($timelineTenant['serviceCharge']), ENT_QUOTES, 'UTF-8') ?>/month</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span>Owed (tenure)</span>
                        <strong class="<?= $timelineTenureOwed > 0 ? 'admin-amount-due' : 'admin-amount-ok' ?>"><?= htmlspecialchars(app_currency($timelineTenureOwed), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="muted-text">Total owed <?= htmlspecialchars(app_currency($timelineTotalOwed), ENT_QUOTES, 'UTF-8') ?><?= $timelinePriorArrears > 0 ? ' (incl. ' . htmlspecialchars(app_currency($timelinePriorArrears), ENT_QUOTES, 'UTF-8') . ' arrears)' : '' ?></span>
                    </div>
                </div>
            </div>

            <?php if ($isViewMode): ?>
                <?php
                $renewStartDate = date('Y-m-d');
                if ($timelineTenant['endDate'] !== '') {
                    $renewEndTs = @strtotime($timelineTenant['endDate']);
                    if ($renewEndTs !== false) {
                        $renewStartDate = date('Y-m-d', strtotime('+1 day', $renewEndTs));
                    }
                }
                $renewStartTs = @strtotime($renewStartDate);
                $renewEndDate = date('Y-m-d', strtotime('+1 year'));
                $oldStartTs = $timelineTenant['startDate'] !== '' ? @strtotime($timelineTenant['startDate']) : false;
                $oldEndTs = $timelineTenant['endDate'] !== '' ? @strtotime($timelineTenant['endDate']) : false;
                if ($renewStartTs !== false && $oldStartTs !== false && $oldEndTs !== false && $oldEndTs > $oldStartTs) {
                    $renewEndDate = date('Y-m-d', $renewStartTs + ($oldEndTs - $oldStartTs));
                } elseif ($renewStartTs !== false) {
                    $renewEndDate = date('Y-m-d', strtotime('+1 year', $renewStartTs));
                }
                $renewCarryOver = $timelineTotalOwed;
                $renewPanelOpen = $hasOldInput || (isset($_GET['renew']) && (string) $_GET['renew'] !== '');
                $renewFieldValue = function ($field, $default) use ($hasOldInput, $oldInput) {
                    if ($hasOldInput && isset($oldInput[$field]) && (string) $oldInput[$field] !== '') {
                        return (string) $oldInput[$field];
                    }
                    return (string) $default;
                };
                $renewTenureYears = $toTenureYears($renewFieldValue('tenure', $timelineTenant['tenure']));
                $renewRentValue = '';
                if ($hasOldInput && isset($oldInput['monthly_rent']) && (string) $oldInput['monthly_rent'] !== '') {
                    $renewRentValue = (string) $oldInput['monthly_rent'];
                } elseif ((int) $timelineTenant['monthlyRent'] > 0) {
                    $renewRentValue = (string) ((int) round((int) $timelineTenant['monthlyRent'] * 12));
                }
                ?> 
                <div id="renew-tenant-modal" class="admin-modal-overlay admin-modal-overlay--scroll" style="display:<?= $renewPanelOpen ? 'flex' : 'none' ?>" aria-hidden="<?= $renewPanelOpen ? 'false' : 'true' ?>">
                    <div class="admin-modal-dialog admin-modal-dialog--wide">
                        <h3>Renew this tenure?</h3>
                        <p style="margin:0 0 16px">
                            Renew the rent for
                            <strong><?= htmlspecialchars((string) $timelineTenant['user']['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            on <?= htmlspecialchars($detailUnitLabel, ENT_QUOTES, 'UTF-8') ?><?= $detailUnitSub !== '' ? ' (' . htmlspecialchars($detailUnitSub, ENT_QUOTES, 'UTF-8') . ')' : '' ?>?
                            This closes the current tenure (<strong><?= htmlspecialchars((string) $timelineTenant['tenure'], ENT_QUOTES, 'UTF-8') ?></strong>) and opens a new one on the same unit. The outstanding balance of
                            <strong class="<?= $renewCarryOver > 0 ? 'admin-amount-danger' : 'admin-amount-ok' ?>"><?= htmlspecialchars(app_currency($renewCarryOver), ENT_QUOTES, 'UTF-8') ?></strong>
                            will be carried into the new tenure.
                        </p>
                        <form action="<?= htmlspecialchars(app_url('admin-tenant-renew'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="admin-form">
                            <input type="hidden" name="unit_table" value="<?= htmlspecialchars((string) $timelineTenant['unitTable'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="unit_id" value="<?= (int) $timelineTenant['unitId'] ?>">
                            <div class="admin-form-grid">
                                <div class="admin-field">
                                    <label for="renew-tenure">New tenure / term (years)</label>
                                    <input id="renew-tenure" name="tenure" type="number" min="1" max="10" step="1" placeholder="e.g. 1, 2, 5" required value="<?= $renewTenureYears > 0 ? (int) $renewTenureYears : '' ?>">
                                </div>
                            <div class="admin-field">
                                <label for="renew-monthly-rent">Yearly rent</label>
                                <input id="renew-monthly-rent" name="monthly_rent" type="number" min="1" value="<?= htmlspecialchars($renewRentValue, ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="admin-field">
                                <label for="renew-start-date">New start date</label>
                                <input id="renew-start-date" name="start_date" type="date" value="<?= htmlspecialchars($renewFieldValue('start_date', $renewStartDate), ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="admin-field">
                                <label for="renew-end-date">New end date</label>
                                <input id="renew-end-date" name="end_date" type="date" value="<?= htmlspecialchars($renewFieldValue('end_date', $renewEndDate), ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="admin-field">
                                <label for="renew-service-charge">Monthly Service charge</label>
                                <input id="renew-service-charge" name="service_charge" type="number" min="0" value="<?= htmlspecialchars($renewFieldValue('service_charge', $timelineTenant['serviceCharge']), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="admin-field">
                                <label for="renew-security-deposit">Caution deposit</label>
                                <input id="renew-security-deposit" name="security_deposit" type="number" min="0" value="<?= htmlspecialchars($renewFieldValue('security_deposit', $timelineTenant['securityDeposit']), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="admin-field">
                            <label for="renew-notes">Notes</label>
                            <input id="renew-notes" name="notes" type="text" placeholder="Renewal reference, rent review, special terms">
                        </div>
                        <div class="admin-modal-actions">
                            <button type="button" class="ghost-button" id="renew-tenant-cancel">Cancel</button>
                            <button type="submit" class="solid-button solid-button-good">Confirm renewal</button>
                        </div>
                    </form>
                    </div>
                </div>
                <?php if (! empty($timelineTenant['documents'])): ?>
                    <div class="tenant-documents">
                        <span class="eyebrow">Tenancy agreement</span>
                        <ul>
                            <?php foreach ($timelineTenant['documents'] as $document): ?>
                                <li>
                                    <a href="<?= htmlspecialchars((string) $document['filePath'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($document['originalName'] !== '' ? $document['originalName'] : 'Tenancy agreement', ENT_QUOTES, 'UTF-8') ?></a>
                                    <span class="muted-text"><?= htmlspecialchars(number_format($document['fileSize'] / 1024, 0) . ' KB', ENT_QUOTES, 'UTF-8') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php endif; ?>
        </section>

    <?php elseif ($isRegister || ($isEdit && $editTenant)): ?>
        <?php $showFormByDefault = $isEdit || $hasOldInput; ?>
        <section class="admin-section" id="add-tenant-form" style="<?= $showFormByDefault ? 'display:block' : 'display:none' ?>">
            <div class="section-heading">
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px">
                    <?php if ($isEdit && $editTenant): ?>
                        <a id="edit-back-btn" class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants', array('view_tenant' => $editTenant['unitId'], 'unit_table' => $editTenant['unitTable'])), ENT_QUOTES, 'UTF-8') ?>">←Back</a>
                    <?php endif; ?>
                    <div>
                        <span class="eyebrow"><?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?></span>
                        <h2><?= htmlspecialchars($formHeading, ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>
                </div>
                <div class="admin-page-actions">
                    <?php if ($isRegister): ?>
                        <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants'), ENT_QUOTES, 'UTF-8') ?>">← Back to tenants</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isEdit && $editTenant): ?>
                <div class="admin-editor-state">
                    <span class="type-pill">Editing <?= htmlspecialchars((string) $editTenant['unitTable'], ENT_QUOTES, 'UTF-8') ?> #<?= (int) $editTenant['unitId'] ?></span>
                    <p class="muted-text">Update tenant account</p>
                </div>
            <?php endif; ?>

            <form id="tenant-form" action="<?= htmlspecialchars(app_url('admin-tenant-save'), ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data" class="admin-form" <?= $isEdit && $editTenant ? 'data-confirm-save="1"' : '' ?>>
                <input type="hidden" name="unit_table" id="tenant-unit-table" data-locked="<?= $isEdit && $editTenant ? '1' : '0' ?>" value="<?= $isEdit && $editTenant ? htmlspecialchars((string) $editTenant['unitTable'], ENT_QUOTES, 'UTF-8') : '' ?>">
                <?php if ($isEdit && $editTenant): ?>
                    <input type="hidden" name="unit_id" value="<?= (int) $editTenant['unitId'] ?>">
                <?php endif; ?>

                <span class="eyebrow" style="display:block; margin:18px 0 14px">Tenant account</span>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="tenant-name">Full name</label>
                        <input id="tenant-name" name="tenant_name" type="text" placeholder="Full name" required value="<?= htmlspecialchars((string) $formValue('tenant_name'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="admin-field">
                        <label for="tenant-email">Email</label>
                        <input id="tenant-email" name="tenant_email" type="email" placeholder="name@example.com" required value="<?= htmlspecialchars((string) $formValue('tenant_email'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="admin-field">
                        <label for="tenant-phone">Phone</label>
                        <input id="tenant-phone" name="tenant_phone" type="tel" placeholder="e.g 08023434534" autocomplete="off" required value="<?= htmlspecialchars((string) $formValue('tenant_phone'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <span class="eyebrow" style="display:block; margin:26px 0 14px">Unit and tenure</span>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="tenant-property-id">Property</label>
                        <select id="tenant-property-id" name="property_id" required>
                            <option value="">Select the property this tenant rents</option>
                            <?php foreach ($properties as $prop): ?>
                                <?php $isSelectedProperty = (int) $formValue('property_id') === (int) $prop['id']; ?>
                                <?php $propCharges = isset($propertyCharges[(int) $prop['id']]) ? $propertyCharges[(int) $prop['id']] : array('annualRent' => 0, 'monthlyRent' => 0, 'serviceCharge' => 0, 'securityDeposit' => 0); ?>
                                <option value="<?= (int) $prop['id'] ?>" data-unit-table="<?= htmlspecialchars((string) $unitTableForType($prop['type']), ENT_QUOTES, 'UTF-8') ?>" data-rent="<?= (int) $propCharges['annualRent'] ?>" data-sc="<?= (int) $propCharges['serviceCharge'] ?>" data-deposit="<?= (int) $propCharges['securityDeposit'] ?>" <?= $isSelectedProperty ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $prop['title'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="tenant-status">Status</label>
                        <select id="tenant-status" name="status">
                            <option value="active" <?= (string) $formValue('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (string) $formValue('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="ended" <?= (string) $formValue('status') === 'ended' ? 'selected' : '' ?>>Ended</option>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label for="tenant-tenure">Tenure / term (years)</label>
                        <input id="tenant-tenure" name="tenure" type="number" min="1" max="10" step="1" placeholder="e.g. 1, 2, 5" required value="<?= $tenureYearsValue > 0 ? (int) $tenureYearsValue : '' ?>">
                    </div>
                    <div class="admin-field">
                        <label for="tenant-monthly-rent">Yearly rent</label>
                        <input id="tenant-monthly-rent" name="monthly_rent" type="number" min="50000" step="1" placeholder="e.g. 6,000,000" required value="<?= htmlspecialchars($rentValueYearly, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="admin-field">
                        <label for="tenant-start-date">Tenure start</label>
                        <input id="tenant-start-date" name="start_date" type="date" required value="<?= htmlspecialchars((string) $formValue('start_date'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="admin-field">
                        <label for="tenant-end-date">Tenure end</label>
                        <input id="tenant-end-date" name="end_date" type="date" required value="<?= htmlspecialchars((string) $formValue('end_date'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="admin-field">
                        <label for="tenant-service-charge">Monthly Service charge</label>
                        <input id="tenant-service-charge" name="service_charge" type="number" min="0" step="1" placeholder="0" value="<?= htmlspecialchars((string) $formValue('service_charge'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="admin-field">
                        <label for="tenant-security-deposit">Caution deposit</label>
                        <input id="tenant-security-deposit" name="security_deposit" type="number" min="0" step="1" placeholder="500000" value="<?= htmlspecialchars((string) $formValue('security_deposit'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="admin-form-grid" data-unit-group="mall_shops" style="<?= $selectedPropertyType === 'mall_shops' ? '' : 'display:none' ?>">
                    <div class="admin-field">
                        <label for="tenant-shop-number">Shop number</label>
                        <input id="tenant-shop-number" name="shop_number" type="text" placeholder="Shop 12" value="<?= htmlspecialchars((string) $formValue('shop_number'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="admin-field">
                        <label for="tenant-shop-name">Shop name</label>
                        <input id="tenant-shop-name" name="shop_name" type="text" placeholder="Soleil Fashion" value="<?= htmlspecialchars((string) $formValue('shop_name'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div></div>
                </div>

                <div class="admin-form-grid" data-unit-group="apartment_flats" style="<?= $selectedPropertyType === 'apartment_flats' ? '' : 'display:none' ?>">
                    <div class="admin-field">
                        <label for="tenant-block">Block</label>
                        <input id="tenant-block" name="block" type="text" placeholder="Block A" value="<?= htmlspecialchars((string) $formValue('block'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="admin-field">
                        <label for="tenant-floor">Floor</label>
                        <input id="tenant-floor" name="floor" type="text" placeholder="2" value="<?= htmlspecialchars((string) $formValue('floor'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="admin-field">
                        <label for="tenant-flat-number">Flat number</label>
                        <input id="tenant-flat-number" name="flat_number" type="text" placeholder="Flat 3" value="<?= htmlspecialchars((string) $formValue('flat_number'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="admin-form-grid" data-unit-group="residential_units" style="<?= $selectedPropertyType === 'residential_units' ? '' : 'display:none' ?>">
                    <div class="admin-field">
                        <label for="tenant-block">Block</label>
                        <input id="tenant-block" name="block" type="text" placeholder="Block B" value="<?= htmlspecialchars((string) $formValue('block'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="admin-field">
                        <label for="tenant-unit-number">Unit number</label>
                        <input id="tenant-unit-number" name="unit_number" type="text" placeholder="Unit 7" value="<?= htmlspecialchars((string) $formValue('unit_number'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div></div>
                </div>

                <div class="admin-form-grid">
                    <div class="admin-field admin-span-3">
                        <label for="tenant-notes">Notes</label>
                        <textarea id="tenant-notes" name="notes" rows="3" placeholder="Reference point, rent payment pattern, guarantor details, or any other note about this tenancy."><?= htmlspecialchars((string) $formValue('notes'), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div class="admin-form-grid">
                    <div class="admin-field admin-span-3">
                        <label for="tenant-tenancy-agreement">Tenancy agreement form</label>
                        <input id="tenant-tenancy-agreement" name="tenancy_agreement" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <p class="muted-text" style="font-size:.8rem; margin-top:.3rem">Optional: upload the signed tenancy agreement (PDF, Word, or image). It is stored with this registration.</p>
                        <?php if ($isEdit && $editTenant && ! empty($editTenant['documents'])): ?>
                            <div class="tenant-documents" style="margin-top:10px">
                                <?php foreach ($editTenant['documents'] as $document): ?>
                                    <a href="<?= htmlspecialchars((string) $document['filePath'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($document['originalName'] !== '' ? $document['originalName'] : 'Tenancy agreement', ENT_QUOTES, 'UTF-8') ?></a>
                                    <span class="muted-text"><?= htmlspecialchars(number_format($document['fileSize'] / 1024, 0) . ' KB', ENT_QUOTES, 'UTF-8') ?></span><br>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="solid-button"><?= htmlspecialchars($formButton, ENT_QUOTES, 'UTF-8') ?></button>
                    <?php if ($isEdit && $editTenant): ?>
                        <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants'), ENT_QUOTES, 'UTF-8') ?>">Cancel editing</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <?php if ($timelineTenant): ?>
        <section class="admin-section">
            <?php $rentYears = isset($timelineTenant['rentYears']) ? $timelineTenant['rentYears'] : null; ?>
            <?php if ($rentYears !== null && $rentYears['years'] !== array()): ?>
                <div style="margin-top:2rem">
                    <div class="section-heading" style="margin-bottom:12px">
                        <div>
                            <span class="eyebrow">Rent</span>
                            <h3>Rent breakdown by year</h3>
                        </div>
                    </div>

                    <?php if ($rentYears['years'] !== array()): ?>
                        <div class="manager-stats" style="margin-bottom:14px">
                            <article>
                                <span>Total billed</span>
                                <strong><?= htmlspecialchars(app_currency($rentYears['totalRates']), ENT_QUOTES, 'UTF-8') ?></strong>
                            </article>
                            <article>
                                <span>Total paid</span>
                                <strong><?= htmlspecialchars(app_currency($rentYears['totalPaid']), ENT_QUOTES, 'UTF-8') ?></strong>
                            </article>
                            <article>
                                <span>Arrears</span>
                                <strong<?= $rentYears['totalArrears'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($rentYears['totalArrears']), ENT_QUOTES, 'UTF-8') ?></strong>
                            </article>
                            <article>
                                <span>Outstanding</span>
                                <strong<?= $rentYears['totalRemaining'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($rentYears['totalRemaining']), ENT_QUOTES, 'UTF-8') ?></strong>
                            </article>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($rentYears['years'] as $year): ?>
                        <details class="prior-tenure-block"<?= $year['isCurrentYear'] ? ' open' : '' ?> style="border:1px solid var(--line); border-radius:12px; margin-bottom:12px; padding:14px 18px;">
                            <summary style="cursor:pointer; display:flex; flex-wrap:wrap; gap:8px 20px; align-items:center; justify-content:space-between;">
                                <span>
                                    <strong>Year <?= (int) $year['yearNumber'] ?></strong>
                                    <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars((string) $year['yearStart'] . ' → ' . $year['yearEnd'], ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                                <span>
                                    <?php if ($year['isArrears']): ?>
                                        <span class="type-pill pill-ended">Arrears</span>
                                    <?php elseif ($year['remaining'] <= 0): ?>
                                        <span class="type-pill pill-active">Settled</span>
                                    <?php else: ?>
                                        <span class="type-pill pill-active">Ongoing</span>
                                    <?php endif; ?>
                                    <span class="muted-text" style="font-size:.8rem; margin-left:10px"><?= htmlspecialchars(app_currency($year['annualRent']), ENT_QUOTES, 'UTF-8') ?> billed · <?= htmlspecialchars(app_currency($year['paid']), ENT_QUOTES, 'UTF-8') ?> paid · <?= htmlspecialchars(app_currency($year['remaining']), ENT_QUOTES, 'UTF-8') ?> remaining</span>
                                </span>
                            </summary>

                            <div style="margin-top:14px">
                                <div class="record-table">
                                    <div class="record-table-row record-table-head">
                                        <span>Payment</span>
                                        <span>Channel</span>
                                        <span>Reference</span>
                                        <span>Amount</span>
                                    </div>
                                    <?php if ($year['payments'] === array()): ?>
                                        <div class="record-table-row">
                                            <span>No payments recorded in this year.</span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($year['payments'] as $pmt): ?>
                                            <div class="record-table-row">
                                                <span><strong><?= htmlspecialchars((string) $pmt['date'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                                                <span><?= htmlspecialchars(ucwords((string) $pmt['channel']), ENT_QUOTES, 'UTF-8') ?></span>
                                                <span><?= htmlspecialchars((string) $pmt['reference'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <span><?= htmlspecialchars(app_currency($pmt['amount']), ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="manager-stats" style="margin-top:12px">
                                    <article>
                                        <span>Annual rent</span>
                                        <strong><?= htmlspecialchars(app_currency($year['annualRent']), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </article>
                                    <article>
                                        <span>Paid this year</span>
                                        <strong><?= htmlspecialchars(app_currency($year['paid']), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </article>
                                    <article>
                                        <span>Remaining</span>
                                        <strong<?= $year['remaining'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($year['remaining']), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </article>
                                </div>
                            </div>
                        </details>
                    <?php endforeach; ?>

                    <p class="muted-text">Each tenure year is billed in full for the year. Payments are listed under the year they were recorded — some tenants make more than one payment within a year. A year is settled when its billed amount is fully paid.</p>
                </div>
            <?php endif; ?>

            <?php
            $sc = isset($timelineTenant['scSummary']) ? $timelineTenant['scSummary'] : null;
            if ($sc !== null && $sc['months'] !== array()):
            ?>
                <div style="margin-top:2rem">
                    <div class="section-heading" style="margin-bottom:12px">
                        <div>
                            <span class="eyebrow">Service charges</span>
                            <h3>Billing schedule — <?= htmlspecialchars((string) $sc['currentYearStart'] . ' to ' . $sc['currentYearEnd'], ENT_QUOTES, 'UTF-8') ?></h3>
                        </div>
                    </div>
                    <div class="manager-stats" style="margin-bottom:14px">
                        <article>
                            <span>Billed this year</span>
                            <strong><?= htmlspecialchars(app_currency($sc['totalRates']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </article>
                        <article>
                            <span>Paid so far</span>
                            <strong><?= htmlspecialchars(app_currency($sc['totalPaid']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </article>
                        <article>
                            <span>Arrears</span>
                            <strong<?= $sc['arrears'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($sc['arrears']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </article>
                        <article>
                            <span>Outstanding</span>
                            <strong<?= $sc['outstanding'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($sc['outstanding']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </article>
                    </div>
                    <div class="record-table">
                        <div class="record-table-row record-table-head">
                            <span>Month</span>
                            <span>Monthly rate</span>
                            <span>Paid</span>
                            <span>Remaining</span>
                        </div>
                        <?php foreach ($sc['months'] as $m): ?>
                            <div class="record-table-row<?= $m['remaining'] > 0 ? ' record-table-row--highlight' : '' ?>">
                                <span><strong><?= htmlspecialchars((string) $m['serviceMonth'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                                <span><?= htmlspecialchars(app_currency($m['rate']), ENT_QUOTES, 'UTF-8') ?></span>
                                <span><?= htmlspecialchars(app_currency($m['paid']), ENT_QUOTES, 'UTF-8') ?></span>
                                <span>
                                    <strong<?= $m['remaining'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($m['remaining']), ENT_QUOTES, 'UTF-8') ?></strong>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="muted-text">Rates follow the service charge history of the property. Earlier months with no payment show a zero rate because they are assumed to have been settled on time; only the current and future billing months carry the live rate.</p>
                </div>
            <?php endif; ?>

            <?php $priorTenures = isset($timelineTenant['priorTenures']) ? $timelineTenant['priorTenures'] : array(); ?>
            <?php if ($priorTenures !== array()): ?>
                <div style="margin-top:2rem">
                    <div class="section-heading" style="margin-bottom:12px">
                        <div>
                            <span class="eyebrow">Previous tenures</span>
                            <h3>Past rent &amp; service charge breakdowns</h3>
                        </div>
                    </div>

                    <?php foreach ($priorTenures as $priorIndex => $prior): ?>
                        <?php
                        $h = $prior['history'];
                        $bd = $prior['breakdown'];
                        $ptId = 'prior-tenure-' . $priorIndex . '-' . $timelineTenant['unitId'];
                        ?>
                        <details class="prior-tenure-block" style="border:1px solid var(--line); border-radius:12px; margin-bottom:12px; padding:14px 18px;">
                            <summary style="cursor:pointer; display:flex; flex-wrap:wrap; gap:8px 20px; align-items:center; justify-content:space-between;">
                                <span>
                                    <strong><?= htmlspecialchars((string) ($h['tenure'] !== '' ? $h['tenure'] : 'Tenure'), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars(trim((string) $h['startDate'] . ($h['endDate'] !== '' ? ' → ' . $h['endDate'] : '')), ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                                <span>
                                    <span class="type-pill <?= $h['status'] === 'renewed' ? 'pill-active' : 'pill-ended' ?>"><?= htmlspecialchars((string) ucfirst($h['status']), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="muted-text" style="font-size:.8rem; margin-left:10px"><?= htmlspecialchars(app_currency($bd['monthlyRent']), ENT_QUOTES, 'UTF-8') ?>/mo · SC <?= htmlspecialchars(app_currency($bd['serviceChargeRate']), ENT_QUOTES, 'UTF-8') ?>/mo</span>
                                </span>
                            </summary>

                            <div style="margin-top:14px">
                                <div class="manager-stats" style="margin-bottom:14px">
                                    <article>
                                        <span>Rent billed</span>
                                        <strong><?= htmlspecialchars(app_currency($bd['billedAmount']), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </article>
                                    <article>
                                        <span>Rent paid</span>
                                        <strong><?= htmlspecialchars(app_currency($bd['paidAmount']), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </article>
                                    <article>
                                        <span>Rent remaining</span>
                                        <strong<?= $bd['outstanding'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($bd['outstanding']), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </article>
                                    <article>
                                        <span>Carried to next tenure</span>
                                        <strong<?= $bd['balanceCarried'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($bd['balanceCarried']), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </article>
                                </div>

                                <?php if ($bd['months'] !== array()): ?>
                                    <div style="overflow-x:auto">
                                        <div class="record-table">
                                            <div class="record-table-row record-table-head">
                                                <span>Rent month</span>
                                                <span>Monthly rent</span>
                                                <span>Paid</span>
                                                <span>Remaining</span>
                                            </div>
                                            <?php foreach ($bd['months'] as $m): ?>
                                                <div class="record-table-row<?= $m['remaining'] > 0 ? ' record-table-row--highlight' : '' ?>">
                                                    <span><strong><?= htmlspecialchars((string) $m['serviceMonth'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                                                    <span><?= htmlspecialchars(app_currency($m['rate']), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span><?= htmlspecialchars(app_currency($m['paid']), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span>
                                                        <strong<?= $m['remaining'] > 0 ? ' class="text-danger"' : '' ?>><?= htmlspecialchars(app_currency($m['remaining']), ENT_QUOTES, 'UTF-8') ?></strong>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($bd['serviceChargeRate'] > 0): ?>
                                    <p class="muted-text" style="margin-top:12px">
                                        Service charge was <?= htmlspecialchars(app_currency($bd['serviceChargeRate']), ENT_QUOTES, 'UTF-8') ?>/month for this tenure and is assumed settled through <?= htmlspecialchars((string) $h['endDate'], ENT_QUOTES, 'UTF-8') ?>.
                                        <?php if ($bd['balanceCarried'] > 0): ?>Any rent shortfall (<?= htmlspecialchars(app_currency($bd['balanceCarried']), ENT_QUOTES, 'UTF-8') ?>) was carried into the next tenure.<?php endif; ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($isRegister): ?>
    <section class="admin-section" id="tenant-register-section"<?= $hasOldInput ? ' style="display:none"' : '' ?>>
        <div class="section-heading">
            <div>
                <span class="eyebrow">Tenant register</span>
                <h2>Registered tenants</h2>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center">
                <form class="admin-search-form" method="get" action="<?= htmlspecialchars(app_url('admin-tenants'), ENT_QUOTES, 'UTF-8') ?>" data-property-id="<?= (int) $activePropertyId ?>" id="tenant-search-form">
                    <?php if ($activeStatusFilter !== ''): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($activeStatusFilter, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    <?php if ($activeOwingFilter !== ''): ?>
                        <input type="hidden" name="owing" value="1">
                    <?php endif; ?>
                    <input type="search" name="q" id="tenant-search-input" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search name, email, phone, unit, property&hellip;" autocomplete="off">
                    <button type="submit" class="ghost-button">Search</button>
                    <?php if ($searchQuery !== ''): ?>
                        <a class="ghost-button admin-search-clear" href="<?= htmlspecialchars(app_url('admin-tenants', array('property_id' => $activePropertyId, 'status' => $activeStatusFilter)), ENT_QUOTES, 'UTF-8') ?>">Clear</a>
                    <?php endif; ?>
                </form>
                <?php if ($isRegister): ?>
                    <a href="#add-tenant-form" class="admin-add-button" id="add-tenant-btn">
                        <span class="admin-add-button-icon" aria-hidden="true">+</span>
                        Add tenant
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-property-pills">
            <?php foreach ($properties as $property): ?>
                <?php
                $propCount = isset($tenantCounts[(int) $property['id']]) ? (int) $tenantCounts[(int) $property['id']] : 0;
                $isActive = (int) $activePropertyId === (int) $property['id'];
                ?>
                <a class="admin-property-pill<?= $isActive ? ' is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-tenants', array('property_id' => (int) $property['id'], 'status' => $activeStatusFilter, 'owing' => $activeOwingFilter)), ENT_QUOTES, 'UTF-8') ?>">
                    <span class="admin-property-pill-title"><?= htmlspecialchars((string) $property['title'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="admin-property-pill-count"><?= (int) $propCount ?> <?= (int) $propCount === 1 ? 'tenant' : 'tenants' ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php
        $filterCounts = isset($filterCounts) ? $filterCounts : array('all' => 0, 'active' => 0, 'inactive' => 0, 'ended' => 0, 'owing' => 0);
        $statusPills = array(
            array('label' => 'All', 'status' => '', 'owing' => '', 'class' => 'pill-all', 'count' => $filterCounts['all'], 'active' => $activeStatusFilter === '' && $activeOwingFilter === ''),
            array('label' => 'Active', 'status' => 'active', 'owing' => '', 'class' => 'pill-active', 'count' => $filterCounts['active'], 'active' => $activeStatusFilter === 'active'),
            array('label' => 'Inactive', 'status' => 'inactive', 'owing' => '', 'class' => 'pill-inactive', 'count' => $filterCounts['inactive'], 'active' => $activeStatusFilter === 'inactive'),
            array('label' => 'Ended', 'status' => 'ended', 'owing' => '', 'class' => 'pill-ended', 'count' => $filterCounts['ended'], 'active' => $activeStatusFilter === 'ended'),
            array('label' => 'Owing', 'status' => '', 'owing' => '1', 'class' => 'pill-owing', 'count' => $filterCounts['owing'], 'active' => $activeOwingFilter !== ''),
        );
        ?>
        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin:6px 0 18px">
            <span class="muted-text" style="font-size:.8rem; margin-right:4px">Filter:</span>
            <?php foreach ($statusPills as $pill): ?>
                <a class="type-pill <?= $pill['class'] ?><?= $pill['active'] ? ' is-active' : '' ?>" href="<?= htmlspecialchars(app_url('admin-tenants', array('property_id' => $activePropertyId, 'status' => $pill['status'], 'q' => $searchQuery, 'owing' => $pill['owing'])), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($pill['label'], ENT_QUOTES, 'UTF-8') ?><span class="pill-count"><?= (int) $pill['count'] ?></span></a>
            <?php endforeach; ?>
        </div>

        <div id="tenant-results">
        <?php if ($tenants === array()): ?>
            <article class="empty-state">
                <h2>No tenants match this view.</h2>
                <p><?= $searchQuery !== '' ? 'No registered tenant matches your search. Try a different name, phone, unit, or property.' : 'This property has no registered tenants yet. Use the Add tenant button to create the first registration.' ?></p>
            </article>
        <?php else: ?>
            <div class="admin-table-meta">
                <p class="muted-text"><?= $searchQuery !== '' ? 'Searching across all properties for &ldquo;' . htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') . '&rdquo; · ' : '' ?><?= (int) $totalTenants ?> <?= (int) $totalTenants === 1 ? 'tenant' : 'tenants' ?> · Page <?= (int) $page ?> of <?= (int) $totalPages ?></p>
            </div>
            <div class="record-table admin-tenant-table">
                <div class="record-table-row record-table-head">
                    <span>Tenant</span>
                    <span>Unit</span>
                    <span>Tenure</span>
                    <span>Annual Rent/Service Charge</span>
                    <span>Rent Payable</span>
                    <span>SC Payable</span>
                    <span>Status</span>
                    <span>Action</span>
                </div>
                <?php foreach ($tenants as $tenant): ?>
                    <?php
                    if ($tenant['unitTable'] === 'mall_shops') {
                        $unitLabel = $tenant['shopName'] !== '' ? $tenant['shopName'] : ($tenant['shopNumber'] !== '' ? 'Shop ' . $tenant['shopNumber'] : 'Shop');
                        $unitSub = $tenant['shopNumber'] !== '' ? 'Shop ' . $tenant['shopNumber'] : '';
                    } elseif ($tenant['unitTable'] === 'apartment_flats') {
                        $unitLabel = $tenant['flatNumber'] !== '' ? $tenant['flatNumber'] : 'Flat #' . $tenant['unitId'];
                        $unitSub = trim((string) $tenant['block'] . ($tenant['floor'] !== '' ? ' | Floor ' . $tenant['floor'] : ''));
                    } else {
                        $unitLabel = $tenant['unitNumber'] !== '' ? $tenant['unitNumber'] : 'Unit #' . $tenant['unitId'];
                        $unitSub = $tenant['block'] !== '' ? $tenant['block'] : '';
                    }
                    $tenantViewHref = app_url('admin-tenants', array('view_tenant' => $tenant['unitId'], 'unit_table' => $tenant['unitTable']));
                    $tenantEditHref = app_url('admin-tenants', array('edit_tenant' => $tenant['unitId'], 'unit_table' => $tenant['unitTable']));
                    $balances = isset($tenant['balances']) ? $tenant['balances'] : array('priorArrears' => 0, 'totalOwed' => 0);
                    $priorArrears = (int) $balances['priorArrears'];
                    $totalOwed = (int) $balances['totalOwed'];
                    $scSummary = isset($tenant['scSummary']) ? $tenant['scSummary'] : array('outstanding' => 0, 'totalRates' => 0);
                    $scOwed = (int) $scSummary['outstanding'];
                    $scPerYear = (int) (isset($scSummary['totalRates']) ? $scSummary['totalRates'] : 0);
                    ?>
                    <div class="record-table-row admin-tenant-row" data-href="<?= htmlspecialchars($tenantViewHref, ENT_QUOTES, 'UTF-8') ?>" title="View <?= htmlspecialchars((string) $tenant['user']['name'], ENT_QUOTES, 'UTF-8') ?>—<?= htmlspecialchars((string) $unitLabel, ENT_QUOTES, 'UTF-8') ?>">
                        <span>
                            <strong><?= htmlspecialchars((string) $tenant['user']['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars((string) $tenant['user']['email'] . ' | ' . $tenant['user']['phone'], ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                        <span>
                            <strong><?= htmlspecialchars((string) $unitLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if ($unitSub !== ''): ?>
                                <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars((string) $unitSub, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($searchQuery !== '' && $tenant['property']): ?>
                                <span class="muted-text" style="display:block; font-size:.75rem"><?= htmlspecialchars((string) $tenant['property']['title'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </span>
                        <span>
                            <strong><?= htmlspecialchars((string) ($tenant['tenure'] !== '' ? $tenant['tenure'] : '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="muted-text" style="display:block; font-size:.8rem"><?= htmlspecialchars(trim((string) $tenant['startDate'] . ($tenant['endDate'] !== '' ? ' → ' . $tenant['endDate'] : '')), ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                        <span>
                            <strong><?= htmlspecialchars(app_currency($tenant['monthlyRent'] * 12), ENT_QUOTES, 'UTF-8') ?>/yr</strong>
                            <?php if ($scPerYear > 0): ?>
                                <span class="muted-text" style="display:block; font-size:.8rem">SC <?= htmlspecialchars(app_currency($scPerYear), ENT_QUOTES, 'UTF-8') ?>/yr</span>
                            <?php endif; ?>
                        </span>
                        <span class="<?= $totalOwed > 0 ? 'admin-amount-danger' : 'admin-amount-ok' ?>">
                            <?= htmlspecialchars(app_currency($totalOwed), ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($priorArrears > 0): ?>
                                <span class="admin-amount-note">incl. <?= htmlspecialchars(app_currency($priorArrears), ENT_QUOTES, 'UTF-8') ?> arrears</span>
                            <?php endif; ?>
                        </span>
                        <span class="<?= $scOwed > 0 ? 'admin-amount-danger' : 'admin-amount-ok' ?>">
                            <?= htmlspecialchars(app_currency($scOwed), ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($scOwed > 0): ?>
                                <span class="admin-amount-note admin-amount-note--muted">service charge</span>
                            <?php endif; ?>
                        </span>
                        <span>
                            <span class="admin-status-pill <?= htmlspecialchars((string) $tenant['status'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) ucfirst($tenant['status']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </span>
                        <span>
                            <div style="display:flex; gap:6px; align-items:center">
                                <a class="admin-icon-button admin-icon-button-view" href="<?= htmlspecialchars($tenantViewHref, ENT_QUOTES, 'UTF-8') ?>" aria-label="View tenant details" title="View tenant details">View</a>
                                <a class="admin-icon-button admin-icon-button-edit" href="<?= htmlspecialchars($tenantEditHref, ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                            </div>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <nav class="admin-pagination" aria-label="Tenant pages">
                    <?php if ($page > 1): ?>
                        <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants', array('property_id' => $activePropertyId, 'status' => $activeStatusFilter, 'q' => $searchQuery, 'owing' => $activeOwingFilter, 'pgn' => $page - 1)), ENT_QUOTES, 'UTF-8') ?>">&larr; Previous</a>
                    <?php else: ?>
                        <span class="ghost-button disabled" aria-disabled="true">&larr; Previous</span>
                    <?php endif; ?>
                    <span class="admin-pagination-info">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tenants', array('property_id' => $activePropertyId, 'status' => $activeStatusFilter, 'q' => $searchQuery, 'owing' => $activeOwingFilter, 'pgn' => $page + 1)), ENT_QUOTES, 'UTF-8') ?>">Next →</a>
                    <?php else: ?>
                        <span class="ghost-button disabled" aria-disabled="true">Next →</span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    </section>

    <?php if ($isEdit && $editTenant): ?>
        <div id="save-changes-modal" class="admin-modal-overlay" style="display:none">
            <div class="admin-modal-dialog">
                <h3>Save these changes?</h3>
                <p>Are you sure you want to update this tenant account?</p>
                <div class="admin-modal-actions">
                    <button type="button" class="ghost-button" id="save-changes-cancel">Cancel</button>
                    <button type="button" class="solid-button" id="save-changes-confirm">Yes, save changes</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

<script>
(function () {
    var backBtn = document.getElementById('edit-back-btn');

    if (backBtn) {
        backBtn.addEventListener('click', function (e) {
            var referrer = document.referrer || '';
            var sameOrigin = referrer.indexOf(window.location.origin) === 0;
            var isReloadLoop = referrer.indexOf('edit_tenant=') !== -1;
            var isFormSubmit = referrer.indexOf('page=admin-tenant-save') !== -1;

            if (sameOrigin && !isReloadLoop && !isFormSubmit) {
                e.preventDefault();
                window.location.href = referrer;
                return;
            }

            if (window.history.length > 1) {
                e.preventDefault();
                window.history.back();
                return;
            }

            window.location.href = backBtn.getAttribute('href');
        });
    }

    var tenantForm = document.getElementById('tenant-form');
    var saveModal = document.getElementById('save-changes-modal');

    if (tenantForm && saveModal && tenantForm.getAttribute('data-confirm-save') === '1') {
        var saveCancel = document.getElementById('save-changes-cancel');
        var saveConfirm = document.getElementById('save-changes-confirm');

        function closeSaveModal() {
            saveModal.style.display = 'none';
            saveModal.setAttribute('aria-hidden', 'true');
        }

        tenantForm.addEventListener('submit', function (e) {
            e.preventDefault();
            saveModal.style.display = 'flex';
            saveModal.setAttribute('aria-hidden', 'false');
        });

        if (saveCancel) {
            saveCancel.addEventListener('click', closeSaveModal);
        }

        if (saveConfirm) {
            saveConfirm.addEventListener('click', function () {
                closeSaveModal();
                tenantForm.submit();
            });
        }

        saveModal.addEventListener('click', function (e) {
            if (e.target === saveModal) {
                closeSaveModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeSaveModal();
            }
        });
    }

    var propertySelect = document.getElementById('tenant-property-id');
    var unitTableInput = document.getElementById('tenant-unit-table');
    var groups = Array.prototype.slice.call(document.querySelectorAll('[data-unit-group]'));

    function showUnitGroup(table) {
        groups.forEach(function (group) {
            group.style.display = group.getAttribute('data-unit-group') === table ? '' : 'none';
        });
    }

    if (propertySelect && unitTableInput) {
        propertySelect.addEventListener('change', function () {
            var option = propertySelect.options[propertySelect.selectedIndex];
            var table = option && option.getAttribute('data-unit-table') ? option.getAttribute('data-unit-table') : '';
            if (unitTableInput.getAttribute('data-locked') !== '1') {
                unitTableInput.value = table;
            }
            showUnitGroup(table);
        });

        var preset = propertySelect.options[propertySelect.selectedIndex];
        var selectedTable = preset && preset.getAttribute('data-unit-table') ? preset.getAttribute('data-unit-table') : '';
        unitTableInput.value = selectedTable;
        showUnitGroup(selectedTable);
    }

    var rentInput = document.getElementById('tenant-monthly-rent');
    var scInput = document.getElementById('tenant-service-charge');
    var depositInput = document.getElementById('tenant-security-deposit');

    if (propertySelect && rentInput && scInput && depositInput) {
        propertySelect.addEventListener('change', function () {
            var option = propertySelect.options[propertySelect.selectedIndex];
            if (!option) { return; }
            rentInput.value = option.hasAttribute('data-rent') ? option.getAttribute('data-rent') : rentInput.value;
            scInput.value = option.hasAttribute('data-sc') ? option.getAttribute('data-sc') : scInput.value;
            depositInput.value = option.hasAttribute('data-deposit') ? option.getAttribute('data-deposit') : depositInput.value;
        });
    }

    function yearsToDate(startValue, years) {
        if (!startValue || !years) { return ''; }
        var parts = String(startValue).split('-');
        if (parts.length !== 3) { return ''; }
        var d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        d.setFullYear(d.getFullYear() + Number(years));
        d.setDate(d.getDate() - 1);
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    var tenureInput = document.getElementById('tenant-tenure');
    var startInput = document.getElementById('tenant-start-date');
    var endInput = document.getElementById('tenant-end-date');

    function recomputeEnd() {
        if (!endInput) { return; }
        if (endInput.value !== '' && endInput.dataset.touched === '1') { return; }
        var computed = yearsToDate(startInput ? startInput.value : '', tenureInput ? tenureInput.value : '');
        if (computed !== '') { endInput.value = computed; }
    }

    if (startInput) { startInput.addEventListener('change', recomputeEnd); }
    if (tenureInput) { tenureInput.addEventListener('change', recomputeEnd); }
    if (endInput) { endInput.addEventListener('input', function () { endInput.dataset.touched = '1'; }); }

    var renewTenureInput = document.getElementById('renew-tenure');
    var renewStartInput = document.getElementById('renew-start-date');
    var renewEndInput = document.getElementById('renew-end-date');

    function recomputeRenewEnd() {
        if (!renewEndInput) { return; }
        if (renewEndInput.value !== '' && renewEndInput.dataset.touched === '1') { return; }
        var computed = yearsToDate(renewStartInput ? renewStartInput.value : '', renewTenureInput ? renewTenureInput.value : '');
        if (computed !== '') { renewEndInput.value = computed; }
    }

    if (renewStartInput) { renewStartInput.addEventListener('change', recomputeRenewEnd); }
    if (renewTenureInput) { renewTenureInput.addEventListener('change', recomputeRenewEnd); }
    if (renewEndInput) { renewEndInput.addEventListener('input', function () { renewEndInput.dataset.touched = '1'; }); }

    var addBtn = document.getElementById('add-tenant-btn');
    var addForm = document.getElementById('add-tenant-form');
    var tenantRegisterSection = document.getElementById('tenant-register-section');

    if (addBtn && addForm) {
        addBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (tenantRegisterSection) {
                tenantRegisterSection.style.display = 'none';
            }
            addForm.style.display = 'block';
            addForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    var renewBtn = document.getElementById('renew-tenant-btn');
    var renewModal = document.getElementById('renew-tenant-modal');
    var renewCancel = document.getElementById('renew-tenant-cancel');

    function openRenewModal() {
        renewModal.style.display = 'flex';
        renewModal.setAttribute('aria-hidden', 'false');
    }

    function closeRenewModal() {
        renewModal.style.display = 'none';
        renewModal.setAttribute('aria-hidden', 'true');
    }

    if (renewBtn && renewModal) {
        renewBtn.addEventListener('click', openRenewModal);
    }

    if (renewCancel && renewModal) {
        renewCancel.addEventListener('click', closeRenewModal);
    }

    if (renewModal) {
        renewModal.addEventListener('click', function (e) {
            if (e.target === renewModal) {
                closeRenewModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeRenewModal();
            }
        });
    }

    function bindTenantRows() {
        Array.prototype.forEach.call(document.querySelectorAll('.admin-tenant-row'), function (row) {
            if (row.getAttribute('data-bound')) {
                return;
            }
            row.setAttribute('data-bound', '1');
            row.addEventListener('click', function (e) {
                if (e.target.closest('a, button, form, input, select, textarea')) {
                    return;
                }
                var href = row.getAttribute('data-href');
                if (href) {
                    window.location.href = href;
                }
            });
        });
    }

    var searchInput = document.getElementById('tenant-search-input');
    var resultsWrap = document.getElementById('tenant-results');
    var searchForm = document.getElementById('tenant-search-form');
    var searchTimer = null;
    var searchBase = <?= json_encode(app_url('admin-tenants'), JSON_UNESCAPED_SLASHES) ?>;

    function refreshResults(query) {
        if (! resultsWrap || ! searchForm) {
            return;
        }

        var status = searchForm.querySelector('input[name="status"]');
        var owing = searchForm.querySelector('input[name="owing"]');
        var url = searchBase
            + (status && status.value ? '&status=' + encodeURIComponent(status.value) : '')
            + (owing && owing.value ? '&owing=' + encodeURIComponent(owing.value) : '')
            + (query ? '&q=' + encodeURIComponent(query) : '');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (resp) {
                return resp.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var fresh = doc.getElementById('tenant-results');
                if (fresh) {
                    resultsWrap.innerHTML = fresh.innerHTML;
                }
                bindTenantRows();

                var clearLink = searchForm.querySelector('.admin-search-clear');
                if (clearLink) {
                    clearLink.style.display = query ? '' : 'none';
                }
            })
            .catch(function () {});
    }

    if (searchInput && searchForm) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                refreshResults(searchInput.value);
            }, 250);
        });
    }

    bindTenantRows();
})();
</script>

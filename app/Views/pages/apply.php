<?php
/** @var array $listing */
/** @var bool $isCommercial */
$isCommercial = isset($isCommercial) ? $isCommercial : (isset($listing['purpose']) && $listing['purpose'] === 'commercial');
$legalFee = ! $isCommercial ? app_legal_fee_amount($listing['monthlyRent']) : 0;
$cautionDeposit = ! $isCommercial ? app_caution_deposit_amount($listing['securityDeposit']) : 0;
$moveInTotal = ! $isCommercial ? app_move_in_total($listing['monthlyRent'], $listing['serviceCharge'], $cautionDeposit) : 0;
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow"><?= $isCommercial ? 'Lease request' : 'Rental application' ?></span>
        <h1><?= $isCommercial ? 'Request a lease for ' : 'Apply for ' ?><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p>
            <?php if ($isCommercial): ?>
                Tell the leasing team about your business so they can send unit availability, lease terms and pricing.
            <?php else: ?>
                Complete the application so the leasing team can review, approve, and unlock online move-in payment.
            <?php endif; ?>
        </p>
    </div>
</section>

<section class="search-layout" style="padding:28px 0">
    <aside>
        <div class="detail-card">
            <span class="eyebrow">Property summary</span>
            <h2 style="font-size:1.25rem; margin:8px 0 6px"><?= htmlspecialchars((string) $listing['price'], ENT_QUOTES, 'UTF-8') ?></h2>
            <h3 style="font-size:.95rem; font-weight:700; margin:0 0 6px"><?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="muted-text" style="font-size:.85rem; margin:0 0 16px">
                📍 <?= htmlspecialchars((string) $listing['location'], ENT_QUOTES, 'UTF-8') ?>
            </p>

            <div style="display:grid; gap:8px; font-size:.875rem">
                <?php if ($isCommercial): ?>
                    <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--line-light)">
                        <span style="color:var(--muted)">Lease term</span>
                        <strong><?= htmlspecialchars((string) $listing['leaseTerm'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--line-light)">
                        <span style="color:var(--muted)">Leasable area</span>
                        <strong><?= htmlspecialchars((string) $listing['area'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:6px 0">
                        <span style="color:var(--muted)">Units available</span>
                        <strong><?= htmlspecialchars((string) $listing['units'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                <?php else: ?>
                    <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--line-light)">
                        <span style="color:var(--muted)">Annual rent</span>
                        <strong><?= htmlspecialchars(app_currency($listing['monthlyRent']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--line-light)">
                        <span style="color:var(--muted)">Service charge</span>
                        <strong><?= htmlspecialchars(app_currency($listing['serviceCharge']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--line-light)">
                        <span style="color:var(--muted)">Legal fee (10%)</span>
                        <strong><?= htmlspecialchars(app_currency($legalFee), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--line-light)">
                        <span style="color:var(--muted)">Caution deposit</span>
                        <strong><?= htmlspecialchars(app_currency($cautionDeposit), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:6px 0">
                        <span style="font-weight:700">Total due at move-in</span>
                        <strong><?= htmlspecialchars(app_currency($moveInTotal), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-card" style="margin-top:14px; background:var(--primary-light); border-color:rgba(21,87,192,.15)">
            <span class="eyebrow">What happens next?</span>
            <ol style="padding-left:18px; margin:10px 0 0; font-size:.875rem; color:var(--ink-mid); display:grid; gap:8px">
                <li>Submit your application below</li>
                <li>Qualified renters can pass auto-screening instantly</li>
                <li>Manual review still handles exceptions and edge cases</li>
                <li>Complete online move-in payment</li>
                <li>Your tenancy is activated instantly</li>
            </ol>
        </div>
    </aside>

    <div class="filter-panel">
        <h2 style="font-size:1.1rem; margin-bottom:18px">
            <?= $isCommercial ? 'Business &amp; lease details' : 'Application details' ?>
        </h2>
        <form action="<?= htmlspecialchars(app_url('application-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="filter-form">
            <input type="hidden" name="property_id" value="<?= (int) $listing['id'] ?>">

            <div>
                <label for="annual-income"><?= $isCommercial ? 'Annual business revenue' : 'Annual income' ?></label>
                <input id="annual-income" name="annual_income" type="text"
                       placeholder="<?= htmlspecialchars(app_currency(48000), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div>
                <label for="employer"><?= $isCommercial ? 'Business or brand name' : 'Employer or business' ?></label>
                <input id="employer" name="employer" type="text" required>
            </div>

            <div>
                <label for="move-in-date"><?= $isCommercial ? 'Preferred lease start date' : 'Preferred move-in date' ?></label>
                <input id="move-in-date" name="move_in_date" type="date" required>
            </div>

            <div>
                <label for="occupants"><?= $isCommercial ? 'Number of staff on site' : 'Number of occupants' ?></label>
                <input id="occupants" name="occupants" type="number" min="1" value="1" required>
            </div>

            <div>
                <label for="notes">Additional notes</label>
                <textarea id="notes" name="notes" rows="5"
                    placeholder="<?= $isCommercial ? 'Business type, fit-out needs, or unit-size preference' : 'Employment details, guarantor notes, or special requests' ?>"></textarea>
            </div>

            <button type="submit" class="solid-button wide" style="padding:14px; font-size:.95rem">
                <?= $isCommercial ? 'Submit lease request' : 'Submit application' ?>
            </button>
        </form>
    </div>
</section>

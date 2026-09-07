<?php
/** @var array|null $application */
/** @var array|null $tenancy */
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">Secure payment</span>
        <h1>Complete your payment online</h1>
        <p>Move-in charges or ongoing rent and service-charge payments — all processed securely.</p>
    </div>
</section>

<?php if ($application): ?>
    <section class="search-layout" style="padding:28px 0">
        <aside>
            <div class="detail-card">
                <span class="eyebrow">Payment summary</span>
                <h2 style="margin:8px 0 14px"><?= htmlspecialchars((string) $application['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div style="display:grid; gap:10px; font-size:.875rem">
                    <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--line-light)">
                        <span style="color:var(--muted)">First rent</span>
                        <strong><?= htmlspecialchars(app_currency($application['property']['monthlyRent']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--line-light)">
                        <span style="color:var(--muted)">Service charge</span>
                        <strong><?= htmlspecialchars(app_currency($application['property']['serviceCharge']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--line-light)">
                        <span style="color:var(--muted)">Security deposit</span>
                        <strong><?= htmlspecialchars(app_currency($application['property']['securityDeposit']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0; font-size:1rem">
                        <span style="font-weight:700">Total due now</span>
                        <strong style="color:var(--primary); font-size:1.1rem">
                            <?= htmlspecialchars(app_currency($application['property']['monthlyRent'] + $application['property']['serviceCharge'] + $application['property']['securityDeposit']), ENT_QUOTES, 'UTF-8') ?>
                        </strong>
                    </div>
                </div>
                <div style="margin-top:16px; padding:12px; background:var(--green-light); border-radius:var(--r-sm); font-size:.82rem; color:var(--green)">
                    ✓ Payment activates your tenancy immediately
                </div>
            </div>
        </aside>

        <div class="filter-panel">
            <h2 style="font-size:1.1rem; margin-bottom:18px">Card details</h2>
            <form action="<?= htmlspecialchars(app_url('payment-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="filter-form">
                <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                <input type="hidden" name="channel" value="online">

                <div><label for="card-name">Name on card</label>
                <input id="card-name" name="card_name" type="text" placeholder="As it appears on the card" required></div>

                <div><label for="card-number">Card number</label>
                <input id="card-number" name="card_number" type="text" placeholder="4242 4242 4242 4242" required></div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
                    <div><label for="expiry">Expiry</label>
                    <input id="expiry" name="expiry" type="text" placeholder="MM/YY" required></div>
                    <div><label for="cvv">CVV</label>
                    <input id="cvv" name="cvv" type="text" placeholder="123" required></div>
                </div>

                <button type="submit" class="solid-button wide" style="padding:14px; font-size:.95rem; margin-top:8px">
                    Pay &amp; activate tenancy
                </button>
                <p style="font-size:.78rem; color:var(--muted); text-align:center; margin:8px 0 0">
                    🔒 Your payment is secured with 256-bit SSL encryption
                </p>
            </form>
        </div>
    </section>

<?php elseif ($tenancy): ?>
    <section class="search-layout" style="padding:28px 0">
        <aside>
            <div class="detail-card">
                <span class="eyebrow">Your tenancy</span>
                <h2 style="margin:8px 0 14px"><?= htmlspecialchars((string) $tenancy['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div style="font-size:.875rem; display:grid; gap:8px">
                    <div style="display:flex; justify-content:space-between">
                        <span style="color:var(--muted)">Monthly rent</span>
                        <strong><?= htmlspecialchars(app_currency($tenancy['monthlyRent']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between">
                        <span style="color:var(--muted)">Service charge</span>
                        <strong><?= htmlspecialchars(app_currency($tenancy['serviceCharge']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </div>
        </aside>

        <div class="filter-panel">
            <h2 style="font-size:1.1rem; margin-bottom:18px">Post a payment</h2>
            <form action="<?= htmlspecialchars(app_url('payment-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="filter-form">
                <input type="hidden" name="tenancy_id" value="<?= (int) $tenancy['id'] ?>">
                <input type="hidden" name="channel" value="online">

                <div><label for="charge-type">Charge type</label>
                <select id="charge-type" name="charge_type">
                    <option value="rent">Rent</option>
                    <option value="service_charge">Service charge</option>
                    <option value="other">Other</option>
                </select></div>

                <div><label for="label">Payment label</label>
                <input id="label" name="label" type="text" placeholder="e.g. August rent" required></div>

                <div><label for="amount">Amount (₦)</label>
                <input id="amount" name="amount" type="number" min="1" required></div>

                <div><label for="tenant-card-name">Name on card</label>
                <input id="tenant-card-name" name="card_name" type="text" required></div>

                <div><label for="tenant-card-number">Card number</label>
                <input id="tenant-card-number" name="card_number" type="text" placeholder="4242 4242 4242 4242" required></div>

                <button type="submit" class="solid-button wide" style="padding:14px; font-size:.95rem; margin-top:8px">
                    Post payment to tenancy
                </button>
            </form>
        </div>
    </section>

<?php else: ?>
    <section class="empty-state" style="margin:40px 0">
        <h2>No payment target found.</h2>
        <p>Return to your dashboard and choose an approved application or active tenancy to pay.</p>
        <a class="solid-button" href="<?= htmlspecialchars(app_url('dashboard'), ENT_QUOTES, 'UTF-8') ?>" style="margin-top:20px; display:inline-flex">Go to dashboard</a>
    </section>
<?php endif; ?>

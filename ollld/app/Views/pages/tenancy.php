<?php /** @var array|null $tenancy */ ?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">Tenancy app</span>
        <h1>Manage rent records &amp; service charges</h1>
        <p>Once payment is completed, track your ongoing rent records and tenancy status here.</p>
    </div>
</section>

<?php if (! $tenancy): ?>
    <section class="empty-state" style="margin:40px 0">
        <h2>No active tenancy yet.</h2>
        <p>After your application is approved and first payment is completed, your tenancy record will appear here.</p>
        <a class="solid-button" href="<?= htmlspecialchars(app_url('dashboard'), ENT_QUOTES, 'UTF-8') ?>" style="margin-top:20px; display:inline-flex">Go to dashboard</a>
    </section>
<?php else: ?>

    <!-- Tenancy stats -->
    <section class="manager-stats" style="margin:24px 0">
        <article>
            <span>Property</span>
            <strong style="font-size:1rem"><?= htmlspecialchars((string) $tenancy['property']['title'], ENT_QUOTES, 'UTF-8') ?></strong>
        </article>
        <article>
            <span>Monthly rent</span>
            <strong><?= htmlspecialchars(app_currency($tenancy['monthlyRent']), ENT_QUOTES, 'UTF-8') ?></strong>
        </article>
        <article>
            <span>Service charge</span>
            <strong><?= htmlspecialchars(app_currency($tenancy['serviceCharge']), ENT_QUOTES, 'UTF-8') ?></strong>
        </article>
        <article>
            <span>Status</span>
            <strong style="color:var(--green)"><?= htmlspecialchars((string) ucfirst($tenancy['status']), ENT_QUOTES, 'UTF-8') ?></strong>
        </article>
    </section>

    <!-- Payment ledger -->
    <section class="section-block">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Ledger</span>
                <h2>Rent &amp; service-charge record</h2>
            </div>
            <a class="solid-button" href="<?= htmlspecialchars(app_url('payment', array('tenancy_id' => $tenancy['id'])), ENT_QUOTES, 'UTF-8') ?>">
                Make another payment
            </a>
        </div>

        <div class="record-table">
            <div class="record-table-row record-table-head">
                <span>Label</span>
                <span>Type</span>
                <span>Amount</span>
                <span>Status</span>
                <span>Reference</span>
            </div>
            <?php foreach ($tenancy['ledger'] as $entry): ?>
                <div class="record-table-row">
                    <span><?= htmlspecialchars((string) $entry['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span><?= htmlspecialchars((string) ucfirst(str_replace('_', ' ', $entry['type'])), ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="font-weight:700"><?= htmlspecialchars(app_currency($entry['amount']), ENT_QUOTES, 'UTF-8') ?></span>
                    <span>
                        <span class="admin-status-pill <?= htmlspecialchars((string) $entry['status'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string) ucfirst($entry['status']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </span>
                    <span style="font-size:.8rem; color:var(--muted); font-family:monospace">
                        <?= htmlspecialchars((string) $entry['paymentReference'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

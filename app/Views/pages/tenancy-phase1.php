<?php
/** @var array|null $tenancy */
/** @var array $maintenanceTickets */
/** @var array $messages */
$maintenanceTickets = isset($maintenanceTickets) ? $maintenanceTickets : array();
$messages = isset($messages) ? $messages : array();
$propertyMessages = array();

if ($tenancy) {
    foreach ($messages as $message) {
        if ((int) $message['propertyId'] === (int) $tenancy['propertyId']) {
            $propertyMessages[] = $message;
        }
    }
}
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">Tenancy app</span>
        <h1>Manage payments, maintenance, and support</h1>
        <p>Active tenants can post rent, open repair tickets, and keep all property messages in one place.</p>
    </div>
</section>

<?php if (! $tenancy): ?>
    <section class="empty-state" style="margin:40px 0">
        <h2>No active tenancy yet.</h2>
        <p>After your application is approved and first payment is completed, your tenancy record will appear here.</p>
        <a class="solid-button" href="<?= htmlspecialchars(app_url('dashboard'), ENT_QUOTES, 'UTF-8') ?>" style="margin-top:20px; display:inline-flex">Go to dashboard</a>
    </section>
<?php else: ?>
    <section class="manager-stats" style="margin:24px 0">
        <article>
            <span>Property</span>
            <strong style="font-size:1rem"><?= htmlspecialchars((string) $tenancy['property']['title'], ENT_QUOTES, 'UTF-8') ?></strong>
        </article>
        <article>
            <span>Annual rent</span>
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

    <section class="section-block">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Ledger</span>
                <h2>Rent and service-charge record</h2>
            </div>
            <a class="solid-button" href="<?= htmlspecialchars(app_url('payment', array('tenancy_id' => $tenancy['id'])), ENT_QUOTES, 'UTF-8') ?>">Make another payment</a>
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
                    <span><span class="admin-status-pill <?= htmlspecialchars((string) $entry['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ucfirst($entry['status']), ENT_QUOTES, 'UTF-8') ?></span></span>
                    <span style="font-size:.8rem; color:var(--muted); font-family:monospace"><?= htmlspecialchars((string) $entry['paymentReference'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="search-layout" style="padding:0 0 28px; gap:20px">
        <article class="detail-card">
            <span class="eyebrow">Maintenance</span>
            <h2 style="font-size:1.1rem">Report an issue</h2>
            <form action="<?= htmlspecialchars(app_url('maintenance-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="filter-form">
                <div>
                    <label for="ticket-title">Issue title</label>
                    <input id="ticket-title" name="title" type="text" placeholder="Leak in guest bathroom" required>
                </div>
                <div>
                    <label for="ticket-priority">Priority</label>
                    <select id="ticket-priority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label for="ticket-description">Describe the issue</label>
                    <textarea id="ticket-description" name="description" rows="5" placeholder="What is happening, where it is happening, and how urgent it feels." required></textarea>
                </div>
                <button type="submit" class="solid-button">Submit ticket</button>
            </form>
        </article>

        <article class="detail-card">
            <span class="eyebrow">Support chat</span>
            <h2 style="font-size:1.1rem">Message the operations team</h2>
            <form action="<?= htmlspecialchars(app_url('message-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="filter-form">
                <input type="hidden" name="property_id" value="<?= (int) $tenancy['propertyId'] ?>">
                <input type="hidden" name="subject" value="Tenancy support">
                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars(app_url('tenancy'), ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label for="support-body">Message</label>
                    <textarea id="support-body" name="body" rows="5" placeholder="Ask about access, repairs, service charge, receipts, or any tenancy issue." required></textarea>
                </div>
                <button type="submit" class="solid-button">Send message</button>
            </form>
        </article>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Open tickets</span>
                <h2>Your maintenance history</h2>
            </div>
        </div>

        <?php if ($maintenanceTickets === array()): ?>
            <article class="empty-state">
                <h2>No maintenance tickets yet.</h2>
                <p>When something needs attention in the property, report it here so the team can track and resolve it.</p>
            </article>
        <?php else: ?>
            <div class="pipeline-grid">
                <?php foreach ($maintenanceTickets as $ticket): ?>
                    <article class="pipeline-card">
                        <div class="pipeline-head">
                            <h3><?= htmlspecialchars((string) $ticket['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="admin-status-pill <?= htmlspecialchars((string) $ticket['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ucfirst(str_replace('_', ' ', $ticket['status'])), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p class="muted-text">Priority: <?= htmlspecialchars((string) ucfirst($ticket['priority']), ENT_QUOTES, 'UTF-8') ?></p>
                        <p><?= htmlspecialchars((string) $ticket['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if ($ticket['adminNotes'] !== ''): ?>
                            <p class="muted-text">Ops note: <?= htmlspecialchars((string) $ticket['adminNotes'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Conversation history</span>
                <h2>Messages for this property</h2>
            </div>
        </div>

        <?php if ($propertyMessages === array()): ?>
            <article class="empty-state">
                <h2>No messages yet.</h2>
                <p>Your conversation with the operations team will appear here.</p>
            </article>
        <?php else: ?>
            <div class="results-list">
                <?php foreach ($propertyMessages as $message): ?>
                    <article class="result-card">
                        <div class="result-card-body result-card-body-wide">
                            <div class="result-card-top">
                                <div>
                                    <h2><?= htmlspecialchars($message['subject'] !== '' ? (string) $message['subject'] : 'Tenancy support', ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p class="muted-text"><?= htmlspecialchars((string) ucfirst($message['sender']), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) date('M j, Y g:i a', strtotime($message['createdAt'])), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                            <p><?= nl2br(htmlspecialchars((string) $message['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

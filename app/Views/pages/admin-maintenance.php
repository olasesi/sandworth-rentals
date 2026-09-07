<?php
/** @var array $portfolio */
/** @var array $tickets */
$activeAdminPage = 'maintenance';
$adminTitle = 'Maintenance queue';
$adminDescription = 'Track live tenant issues, keep residents updated, and close out repairs with visible notes.';
require __DIR__ . '/_admin_phase1_header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Maintenance desk</span>
                <h2>Upcoming and open tickets</h2>
            </div>
        </div>

        <?php if ($tickets === array()): ?>
            <article class="empty-state">
                <h2>No maintenance tickets yet.</h2>
                <p>Tenant maintenance requests will appear here as they come in.</p>
            </article>
        <?php else: ?>
            <div class="results-list">
                <?php foreach ($tickets as $ticket): ?>
                    <article class="result-card">
                        <div class="result-card-body result-card-body-wide">
                            <div class="result-card-top">
                                <div>
                                    <h2><?= htmlspecialchars((string) $ticket['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p class="muted-text">
                                        <?= htmlspecialchars((string) $ticket['user']['name'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (isset($ticket['property']['title'])): ?>
                                            | <?= htmlspecialchars((string) $ticket['property']['title'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <span class="admin-status-pill <?= htmlspecialchars((string) $ticket['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ucfirst($ticket['status']), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <p><strong>Priority:</strong> <?= htmlspecialchars((string) ucfirst($ticket['priority']), ENT_QUOTES, 'UTF-8') ?></p>
                            <p><?= nl2br(htmlspecialchars((string) $ticket['description'], ENT_QUOTES, 'UTF-8')) ?></p>

                            <form action="<?= htmlspecialchars(app_url('admin-maintenance-update'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="tour-request-actions">
                                <input type="hidden" name="ticket_id" value="<?= (int) $ticket['id'] ?>">
                                <div class="admin-field">
                                    <label for="ticket-notes-<?= (int) $ticket['id'] ?>">Admin notes</label>
                                    <textarea id="ticket-notes-<?= (int) $ticket['id'] ?>" name="admin_notes" rows="3"><?= htmlspecialchars((string) $ticket['adminNotes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div class="tour-inline-actions">
                                    <button type="submit" class="ghost-button" name="status" value="open">Keep open</button>
                                    <button type="submit" class="ghost-button" name="status" value="in_progress">Start work</button>
                                    <button type="submit" class="solid-button" name="status" value="resolved">Resolve ticket</button>
                                </div>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

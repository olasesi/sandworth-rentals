<?php
/** @var array $tours */
/** @var array $statusCounts */
$tours = isset($tours) ? $tours : array();
$statusCounts = isset($statusCounts) ? $statusCounts : array();
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">Tour management</span>
        <h1>Confirm and record property tours.</h1>
        <p>Approve a preferred date or set your own, then mark each walkthrough as attended (completed) or a no-show.</p>
    </div>
</section>

<section class="admin-workspace">
    <div class="manager-stats plan-stats" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
        <article><span>Requested</span><strong><?= (int) (isset($statusCounts['requested']) ? $statusCounts['requested'] : 0) ?></strong></article>
        <article><span>Scheduled</span><strong><?= (int) (isset($statusCounts['scheduled']) ? $statusCounts['scheduled'] : 0) ?></strong></article>
        <article><span>Completed</span><strong><?= (int) (isset($statusCounts['completed']) ? $statusCounts['completed'] : 0) ?></strong></article>
        <article><span>No-shows</span><strong><?= (int) (isset($statusCounts['no_show']) ? $statusCounts['no_show'] : 0) ?></strong></article>
    </div>

    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Requests</span>
                <h2>Tour pipeline</h2>
            </div>
        </div>

        <div class="results-list">
            <?php if ($tours === array()): ?>
                <article class="empty-state">
                    <h2>No tour requests yet.</h2>
                    <p>Walkthrough requests booked by renters and buyers will appear here.</p>
                </article>
            <?php else: ?>
                <?php foreach ($tours as $tour): ?>
                    <article class="result-card">
                        <div class="result-card-body result-card-body-wide">
                            <div class="result-card-top">
                                <div>
                                    <h2><?= htmlspecialchars((string) $tour['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p class="muted-text"><?= htmlspecialchars((string) $tour['property']['location'], ENT_QUOTES, 'UTF-8') ?> &nbsp;·&nbsp; <?= htmlspecialchars((string) $tour['property']['price'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <span class="admin-status-pill <?= htmlspecialchars((string) $tour['status'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string) ucwords(str_replace('_', ' ', $tour['status'])), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <p><strong>Requested by:</strong> User #<?= (int) $tour['userId'] ?> &nbsp;·&nbsp; Preferred <?= htmlspecialchars((string) $tour['requestedDate'], ENT_QUOTES, 'UTF-8') ?> at <?= htmlspecialchars((string) $tour['requestedTime'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if ($tour['notes'] !== ''): ?>
                                <p>Notes: <?= htmlspecialchars((string) $tour['notes'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php if (in_array($tour['status'], array('scheduled', 'completed', 'no_show'), true) && $tour['scheduledDate'] !== ''): ?>
                                <p class="muted-text" style="font-size:.85rem">Scheduled: <?= htmlspecialchars((string) $tour['scheduledDate'], ENT_QUOTES, 'UTF-8') ?> at <?= htmlspecialchars((string) $tour['scheduledTime'], ENT_QUOTES, 'UTF-8') ?> · Host: <?= htmlspecialchars((string) $tour['attendedBy'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <form action="<?= htmlspecialchars(app_url('admin-tour-update'), ENT_QUOTES, 'UTF-8') ?>" method="post" style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end">
                                <input type="hidden" name="tour_id" value="<?= (int) $tour['id'] ?>">
                                <div class="admin-field" style="flex:1; min-width:150px">
                                    <label for="tour-status-<?= (int) $tour['id'] ?>">Action</label>
                                    <select id="tour-status-<?= (int) $tour['id'] ?>" name="status">
                                        <option value="scheduled" <?= $tour['status'] === 'scheduled' ? 'selected' : '' ?>>Confirm / Schedule</option>
                                        <option value="completed" <?= $tour['status'] === 'completed' ? 'selected' : '' ?>>Completed (attended)</option>
                                        <option value="no_show" <?= $tour['status'] === 'no_show' ? 'selected' : '' ?>>No-show</option>
                                        <option value="cancelled" <?= $tour['status'] === 'cancelled' ? 'selected' : '' ?>>Cancel request</option>
                                    </select>
                                </div>
                                <div class="admin-field" style="flex:1; min-width:150px">
                                    <label for="tour-date-<?= (int) $tour['id'] ?>">Tour date (when scheduling)</label>
                                    <input id="tour-date-<?= (int) $tour['id'] ?>" name="scheduled_date" type="date" value="<?= htmlspecialchars((string) $tour['requestedDate'], ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <button type="submit" class="solid-button">Update tour</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</section>

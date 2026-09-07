<?php
/** @var array $tours */
$tours = isset($tours) ? $tours : array();
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">My tours</span>
        <h1>Book &amp; track property walkthroughs.</h1>
        <p>Request a site visit on any listing. The leasing desk confirms the date and time, then records whether you attended.</p>
    </div>
</section>

<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Tours</span>
            <h2><?= count($tours) ?> tour request<?= count($tours) === 1 ? '' : 's' ?></h2>
        </div>
        <a class="ghost-button" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>"
           style="background:transparent; border-color:var(--line); color:var(--muted)">Browse listings</a>
    </div>

    <?php if ($tours === array()): ?>
        <article class="empty-state">
            <h2>No tours booked yet.</h2>
            <p>Open any property and request a walkthrough. We'll confirm your date and time here.</p>
            <a class="solid-button" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>" style="margin-top:20px">Find a property to tour</a>
        </article>
    <?php else: ?>
        <div class="results-list">
            <?php foreach ($tours as $tour): ?>
                <article class="result-card">
                    <div class="result-card-body result-card-body-wide">
                        <div class="result-card-top">
                            <div>
                                <h2><?= htmlspecialchars((string) $tour['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="muted-text"><?= htmlspecialchars((string) $tour['property']['location'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="admin-status-pill <?= htmlspecialchars((string) $tour['status'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) ucwords(str_replace('_', ' ', $tour['status'])), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <p><strong>Preferred:</strong> <?= htmlspecialchars((string) $tour['requestedDate'], ENT_QUOTES, 'UTF-8') ?> at <?= htmlspecialchars((string) $tour['requestedTime'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if ($tour['notes'] !== ''): ?>
                            <p>Notes: <?= htmlspecialchars((string) $tour['notes'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ($tour['status'] === 'scheduled' && $tour['scheduledDate'] !== ''): ?>
                            <p style="color:var(--primary)"><strong>Confirmed:</strong> <?= htmlspecialchars((string) $tour['scheduledDate'], ENT_QUOTES, 'UTF-8') ?> at <?= htmlspecialchars((string) $tour['scheduledTime'], ENT_QUOTES, 'UTF-8') ?> &nbsp;·&nbsp; Host: <?= htmlspecialchars((string) $tour['attendedBy'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php elseif ($tour['status'] === 'completed'): ?>
                            <p style="color:var(--green)"><strong>Completed</strong> — attended on <?= htmlspecialchars((string) $tour['scheduledDate'], ENT_QUOTES, 'UTF-8') ?>. Host: <?= htmlspecialchars((string) $tour['attendedBy'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php elseif ($tour['status'] === 'no_show'): ?>
                            <p style="color:#C0392B"><strong>Marked as no-show</strong> — we were unable to reach you on the booked date. Please rebook to continue.</p>
                        <?php endif; ?>
                        <p class="muted-text" style="font-size:.8rem; margin-bottom:0">Requested: <?= htmlspecialchars((string) $tour['requestedAt'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

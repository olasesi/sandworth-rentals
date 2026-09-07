<?php
/** @var array $applications */
/** @var array $portfolio */

$activeAdminPage = 'applications';
$adminTitle = 'Review renter applications on a page built for approvals.';
$adminDescription = 'Keep application review separate from editorial and property editing so the approval workflow stays clean and fast.';

require dirname(__DIR__) . '/partials/admin-header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Applications</span>
                <h2>Review renter pipeline</h2>
            </div>
        </div>

        <div class="results-list">
            <?php foreach ($applications as $application): ?>
                <article class="result-card">
                    <div class="result-card-body result-card-body-wide">
                        <div class="result-card-top">
                            <div>
                                <h2><?= htmlspecialchars((string) $application['property']['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="muted-text"><?= htmlspecialchars((string) $application['user']['name'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $application['user']['email'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="type-pill"><?= htmlspecialchars((string) ucfirst($application['status']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p>Income: <?= htmlspecialchars((string) $application['annualIncome'], ENT_QUOTES, 'UTF-8') ?> | Employer: <?= htmlspecialchars((string) $application['employer'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p>Move-in: <?= htmlspecialchars((string) $application['moveInDate'], ENT_QUOTES, 'UTF-8') ?> | Occupants: <?= (int) $application['occupants'] ?></p>
                        <p><?= htmlspecialchars((string) $application['notes'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if ($application['status'] === 'submitted'): ?>
                            <form action="<?= htmlspecialchars(app_url('application-approve'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                                <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                                <button type="submit" class="solid-button">Approve application</button>
                            </form>
                        <?php else: ?>
                            <span class="muted-text">Approved tenants can now pay online and move into the tenancy app.</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if ($applications === array()): ?>
                <article class="empty-state">
                    <h2>No applications yet.</h2>
                    <p>As users sign in and apply for rent, their records will appear here for approval.</p>
                </article>
            <?php endif; ?>
        </div>
    </section>
</section>

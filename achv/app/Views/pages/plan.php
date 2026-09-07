<?php
/** @var array $budgetSnapshot */
/** @var array $teamMembers */
/** @var array $processSteps */
/** @var array $milestones */
/** @var array $content */

$hero = isset($content['hero']) ? $content['hero'] : array();
$processSection = isset($content['processSection']) ? $content['processSection'] : array();
$milestonesSection = isset($content['milestonesSection']) ? $content['milestonesSection'] : array();
$teamSection = isset($content['teamSection']) ? $content['teamSection'] : array();
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow"><?= htmlspecialchars((string) $hero['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
        <h1><?= htmlspecialchars((string) $hero['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars((string) $hero['description'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</section>

<!-- Budget snapshot -->
<section class="manager-stats plan-stats">
    <article>
        <span>Target budget</span>
        <strong><?= htmlspecialchars(app_currency_text(isset($budgetSnapshot['targetBudget']) ? $budgetSnapshot['targetBudget'] : ''), ENT_QUOTES, 'UTF-8') ?></strong>
    </article>
    <article>
        <span>Monthly comfort</span>
        <strong><?= htmlspecialchars(app_currency_text(isset($budgetSnapshot['monthlyComfort']) ? $budgetSnapshot['monthlyComfort'] : ''), ENT_QUOTES, 'UTF-8') ?></strong>
    </article>
    <article>
        <span>Cash to close</span>
        <strong><?= htmlspecialchars(app_currency_text(isset($budgetSnapshot['cashToClose']) ? $budgetSnapshot['cashToClose'] : ''), ENT_QUOTES, 'UTF-8') ?></strong>
    </article>
</section>

<!-- Process steps -->
<section class="section-block">
    <div class="section-heading">
        <div>
            <span class="eyebrow"><?= htmlspecialchars((string) $processSection['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
            <h2><?= htmlspecialchars((string) $processSection['title'], ENT_QUOTES, 'UTF-8') ?></h2>
        </div>
    </div>
    <div class="pillar-grid">
        <?php foreach ($processSteps as $step): ?>
            <article class="pillar-card">
                <h3><?= htmlspecialchars((string) $step['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars((string) $step['description'], ENT_QUOTES, 'UTF-8') ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- Timeline + Team -->
<section class="manager-layout">
    <div class="pipeline-board">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><?= htmlspecialchars((string) $milestonesSection['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
                <h2><?= htmlspecialchars((string) $milestonesSection['title'], ENT_QUOTES, 'UTF-8') ?></h2>
            </div>
        </div>
        <div class="timeline-list">
            <?php foreach ($milestones as $index => $milestone): ?>
                <article class="timeline-card">
                    <span class="timeline-index">0<?= $index + 1 ?></span>
                    <div>
                        <h3><?= htmlspecialchars((string) $milestone, ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars((string) $milestonesSection['note'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <aside class="manager-sidebar">
        <article class="detail-card">
            <span class="eyebrow"><?= htmlspecialchars((string) $teamSection['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
            <h2 style="font-size:1.1rem; margin:6px 0 16px"><?= htmlspecialchars((string) $teamSection['title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="team-list">
                <?php foreach ($teamMembers as $member): ?>
                    <article class="team-card">
                        <strong><?= htmlspecialchars((string) $member['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <h3><?= htmlspecialchars((string) $member['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars((string) $member['note'], ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>

        <div class="detail-card" style="background:var(--accent-light); border-color:rgba(242,124,58,.2)">
            <span class="eyebrow" style="color:var(--accent-dark)">Ready to start?</span>
            <h3 style="margin:8px 0 10px; font-size:1rem">Browse homes for sale</h3>
            <a class="solid-button" href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; justify-content:center">
                See homes for sale
            </a>
        </div>
    </aside>
</section>

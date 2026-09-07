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

<!-- Affordability & mortgage calculator -->
<section class="section-block" id="calculator">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Affordability tool</span>
            <h2>Mortgage &amp; budget calculator</h2>
            <p>Estimate what you can afford on rent or a home purchase — instantly.</p>
        </div>
    </div>

    <div class="manager-layout">
        <div class="detail-card" style="max-width:520px">
            <h3 style="font-size:1.05rem; margin-bottom:4px">Check your budget</h3>
            <p class="muted-text" style="font-size:.85rem; margin:0 0 16px">Enter your numbers and the tool calculates a realistic range.</p>

            <div style="display:grid; gap:12px">
                <div class="admin-field">
                    <label for="calc-income">Monthly take-home income</label>
                    <div style="display:flex; align-items:center; gap:8px">
                        <span style="color:var(--muted)"><?= htmlspecialchars(app_currency_symbol(), ENT_QUOTES, 'UTF-8') ?></span>
                        <input id="calc-income" type="number" min="0" value="500000" style="flex:1">
                    </div>
                </div>
                <div class="admin-field">
                    <label for="calc-type">What are you planning?</label>
                    <select id="calc-type">
                        <option value="rent">Rent (annual lease)</option>
                        <option value="buy">Buy (mortgage)</option>
                    </select>
                </div>
                <div id="calc-mortgage-fields" style="display:none; gap:12px; grid-template-columns:1fr 1fr">
                    <div class="admin-field">
                        <label for="calc-loan">Home price</label>
                        <input id="calc-loan" type="number" min="0" value="100000000" placeholder="Home price">
                    </div>
                    <div class="admin-field">
                        <label for="calc-down">Down payment %</label>
                        <input id="calc-down" type="number" min="0" max="100" value="20" placeholder="e.g. 20">
                    </div>
                    <div class="admin-field">
                        <label for="calc-rate">Annual interest %</label>
                        <input id="calc-rate" type="number" min="0" step="0.1" value="22" placeholder="e.g. 22">
                    </div>
                    <div class="admin-field">
                        <label for="calc-term">Term (years)</label>
                        <input id="calc-term" type="number" min="1" max="30" value="20" placeholder="e.g. 20">
                    </div>
                </div>
            </div>

            <button type="button" id="calc-run" class="solid-button" style="width:100%; justify-content:center; margin-top:16px">Calculate</button>

            <div id="calc-result" style="margin-top:18px; padding-top:16px; border-top:1px solid var(--line); display:grid; gap:8px; font-size:.95rem"></div>
        </div>

        <aside class="manager-sidebar">
            <div class="detail-card" style="background:var(--accent-light); border-color:rgba(242,124,58,.2)">
                <span class="eyebrow" style="color:var(--accent-dark)">Need the numbers checked?</span>
                <h3 style="margin:8px 0 10px; font-size:1rem">Talk to our finance desk</h3>
                <p class="muted-text" style="font-size:.85rem">Send a message and we'll help confirm affordability or connect you with a mortgage provider.</p>
                <a class="solid-button" href="<?= htmlspecialchars(app_url('messages'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; justify-content:center; margin-top:8px">
                    Message the desk
                </a>
            </div>
        </aside>
    </div>
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

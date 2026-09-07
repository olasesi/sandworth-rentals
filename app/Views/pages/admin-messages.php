<?php
/** @var array $portfolio */
/** @var array $messages */
$activeAdminPage = 'messages';
$adminTitle = 'Customer inbox';
$adminDescription = 'Reply to property, tenancy, and support conversations without leaving the operations desk.';
require __DIR__ . '/_admin_phase1_header.php';
?>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Inbox</span>
                <h2>Recent customer messages</h2>
            </div>
        </div>

        <?php if ($messages === array()): ?>
            <article class="empty-state">
                <h2>No messages yet.</h2>
                <p>Questions from property pages and tenancy support will appear here.</p>
            </article>
        <?php else: ?>
            <div class="results-list">
                <?php foreach ($messages as $message): ?>
                    <?php
                    $propertyTitle = isset($message['property']['title']) ? (string) $message['property']['title'] : 'General support';
                    $userName = isset($message['user']['name']) ? (string) $message['user']['name'] : 'Customer';
                    $replyAnchor = app_url('admin-messages') . '#reply-' . (int) $message['id'];
                    ?>
                    <article class="result-card" id="reply-<?= (int) $message['id'] ?>">
                        <div class="result-card-body result-card-body-wide">
                            <div class="result-card-top">
                                <div>
                                    <h2><?= htmlspecialchars($propertyTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p class="muted-text"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) $message['subject'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <span class="admin-status-pill <?= $message['sender'] === 'admin' ? 'confirmed' : 'requested' ?>"><?= htmlspecialchars((string) ucfirst($message['sender']), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <p><?= nl2br(htmlspecialchars((string) $message['body'], ENT_QUOTES, 'UTF-8')) ?></p>

                            <?php if ($message['sender'] !== 'admin'): ?>
                                <form action="<?= htmlspecialchars(app_url('message-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="tour-request-actions">
                                    <input type="hidden" name="user_id" value="<?= (int) $message['userId'] ?>">
                                    <input type="hidden" name="property_id" value="<?= (int) $message['propertyId'] ?>">
                                    <input type="hidden" name="subject" value="<?= htmlspecialchars((string) $message['subject'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="sender" value="admin">
                                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($replyAnchor, ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="admin-field">
                                        <label for="reply-body-<?= (int) $message['id'] ?>">Reply</label>
                                        <textarea id="reply-body-<?= (int) $message['id'] ?>" name="body" rows="3" placeholder="Reply to this customer inside the app." required></textarea>
                                    </div>
                                    <button type="submit" class="solid-button">Send reply</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

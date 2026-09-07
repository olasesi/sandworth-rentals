<?php
/** @var array $conversation */
$conversation = isset($conversation) ? $conversation : array();
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">Messages</span>
        <h1>Renter &amp; buyer enquiries.</h1>
        <p>Reply to questions sent from property pages. Your reply lands in the user's message inbox.</p>
    </div>
</section>

<section class="admin-workspace">
    <section class="admin-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Inbox</span>
                <h2>Conversation</h2>
            </div>
        </div>

        <?php if ($conversation === array()): ?>
            <article class="empty-state">
                <h2>No messages yet.</h2>
                <p>User questions will appear here for you to answer.</p>
            </article>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:12px; max-width:820px">
                <?php foreach ($conversation as $message): ?>
                    <?php $fromLeasing = $message['sender'] === 'admin'; ?>
                    <article class="result-card" style="margin:0">
                        <div class="result-card-body">
                            <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:6px">
                                <span style="font-size:.8rem; color:var(--muted)">
                                    <?= $fromLeasing ? 'Leasing desk' : htmlspecialchars((string) $message['user']['name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($message['user']): ?> &lt;<?= htmlspecialchars((string) $message['user']['email'], ENT_QUOTES, 'UTF-8') ?>&gt;<?php endif; ?>
                                    <?php if ($message['property']): ?> · <?= htmlspecialchars((string) $message['property']['title'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                </span>
                                <span style="font-size:.8rem; color:var(--muted)"><?= htmlspecialchars((string) $message['createdAt'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p style="line-height:1.6"><?= nl2br(htmlspecialchars((string) $message['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php if (! $fromLeasing): ?>
                                <form action="<?= htmlspecialchars(app_url('admin-message-reply'), ENT_QUOTES, 'UTF-8') ?>" method="post" style="margin-top:12px; display:flex; gap:10px; align-items:flex-end">
                                    <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                    <div class="admin-field" style="flex:1">
                                        <label for="reply-<?= (int) $message['id'] ?>">Reply</label>
                                        <input id="reply-<?= (int) $message['id'] ?>" name="reply" type="text" placeholder="Type your reply…" required>
                                    </div>
                                    <button type="submit" class="solid-button">Reply</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php
/** @var array $conversation */
/** @var array $properties */
$conversation = isset($conversation) ? $conversation : array();
$properties = isset($properties) ? $properties : array();
?>
<section class="page-hero compact">
    <div>
        <span class="eyebrow">Messages</span>
        <h1>Talk to the leasing desk.</h1>
        <p>Send a question about any listing and read replies here — no phone calls needed.</p>
    </div>
</section>

<section class="section-block">
    <div class="detail-card" style="max-width:760px; padding:0; overflow:hidden">
        <div style="padding:20px 22px; border-bottom:1px solid var(--line)">
            <h2 style="font-size:1.1rem">Start a new message</h2>
            <p class="muted-text" style="font-size:.875rem; margin:4px 0 14px">Choose a property (optional) and write your question.</p>
            <form action="<?= htmlspecialchars(app_url('message-send'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                <div class="admin-field">
                    <label for="msg-property">Property (optional)</label>
                    <select id="msg-property" name="property_id">
                        <option value="0">General enquiry</option>
                        <?php foreach ($properties as $property): ?>
                            <option value="<?= (int) $property['id'] ?>"><?= htmlspecialchars((string) $property['title'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars((string) $property['location'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-field">
                    <label for="msg-body">Message</label>
                    <textarea id="msg-body" name="body" rows="4" required placeholder="e.g. Is this still available, and what are the exact move-in costs?"></textarea>
                </div>
                <button type="submit" class="solid-button">Send message</button>
            </form>
        </div>

        <div style="padding:20px 22px">
            <h2 style="font-size:1.1rem; margin-bottom:14px">Conversation</h2>
            <?php if ($conversation === array()): ?>
                <article class="empty-state" style="border:none; padding:24px 0">
                    <h2>No messages yet.</h2>
                    <p>Send your first question above and the leasing desk will reply right here.</p>
                </article>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:12px">
                    <?php foreach ($conversation as $message): ?>
                        <?php $isMine = $message['sender'] === 'user'; ?>
                        <div style="align-self:<?= $isMine ? 'flex-end' : 'flex-start' ?>; max-width:85%;
                             background:<?= $isMine ? 'var(--primary)' : 'var(--bg-muted,#F4F5F7)' ?>;
                             color:<?= $isMine ? '#fff' : 'var(--ink)' ?>;
                             padding:10px 14px; border-radius:12px; font-size:.9rem; line-height:1.5">
                            <div style="font-size:.72rem; opacity:.75; margin-bottom:4px">
                                <?= $isMine ? 'You' : 'Leasing desk' ?> · <?= htmlspecialchars((string) $message['createdAt'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (! $isMine && $message['propertyId'] > 0): ?>
                                    · <?= htmlspecialchars((string) $message['property']['title'], ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>
                            <?= nl2br(htmlspecialchars((string) $message['body'], ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

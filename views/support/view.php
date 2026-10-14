<a class="btn btn-outline-secondary btn-sm mb-3" href="<?= e($basePath) ?>">Back to support</a>
<h1 class="h4 mb-3" data-no-translate><?= e($ticket['subject']) ?></h1>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if (!empty($message)): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<dl class="row">
    <dt class="col-sm-3">Created by</dt><dd class="col-sm-9" data-no-translate><?= e($ticket['creator_name']) ?></dd>
    <dt class="col-sm-3">Recipient</dt><dd class="col-sm-9">
        <?php if ($ticket['recipient_type'] === 'system'): ?>System support<?php else: ?><span data-no-translate><?= e($ticket['recipient_name']) ?></span><?php endif; ?>
    </dd>
    <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge text-bg-secondary"><?= e($ticket['status']) ?></span></dd>
    <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?= e($ticket['created_at']) ?></dd>
    <?php if ($ticket['status'] === 'closed'): ?>
        <dt class="col-sm-3">Closed</dt><dd class="col-sm-9"><?= e($ticket['closed_at']) ?></dd>
    <?php endif; ?>
</dl>
<p class="text-muted small">Only the creator, recipient and invited participants can see this conversation.</p>
<?php if ($ticket['recipient_type'] === 'system'): ?>
    <p class="text-muted small">System support tickets are visible to the system support team.</p>
<?php endif; ?>
<h2 class="h5">Conversation</h2>
<?php $messages = array_merge([['author_name' => $ticket['creator_name'], 'created_at' => $ticket['created_at'], 'message' => $ticket['message']]], $replies); ?>
<?php foreach ($messages as $entry): ?>
    <article class="card mb-3">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <strong data-no-translate><?= e($entry['author_name']) ?></strong>
            <time><?= e($entry['created_at']) ?></time>
        </div>
        <div class="card-body" data-no-translate style="white-space: pre-wrap; overflow-wrap: anywhere;"><?= e($entry['message']) ?></div>
    </article>
<?php endforeach; ?>
<?php if ($participants !== []): ?>
    <h2 class="h5">Invited participants</h2>
    <ul data-no-translate><?php foreach ($participants as $participant): ?><li><?= e($participant['name']) ?></li><?php endforeach; ?></ul>
<?php endif; ?>
<?php if ($ticket['status'] === 'closed'): ?>
    <div class="alert alert-secondary">This ticket is closed and read-only.</div>
<?php else: ?>
    <form method="post" action="<?= e($basePath) ?>/reply" class="mb-4">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int) $ticket['id'] ?>">
        <label class="form-label" for="reply-message">Reply</label>
        <textarea class="form-control mb-2" id="reply-message" name="message" rows="4" required><?= e($old['message'] ?? '') ?></textarea>
        <button class="btn btn-primary" type="submit">Send reply</button>
    </form>
    <?php if ($ticket['can_manage']): ?>
        <?php if ($inviteOptions !== []): ?>
            <form method="post" action="<?= e($basePath) ?>/invite" class="mb-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $ticket['id'] ?>">
                <label class="form-label" for="participant">Add participant</label>
                <div class="d-flex gap-2">
                    <select class="form-select" id="participant" name="user_id" required>
                        <option value="">Choose participant</option>
                        <?php foreach ($inviteOptions as $person): ?>
                            <option value="<?= (int) $person['id'] ?>" data-no-translate><?= e($person['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-primary text-nowrap" type="submit">Add participant</button>
                </div>
            </form>
        <?php endif; ?>
        <form method="post" action="<?= e($basePath) ?>/close">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $ticket['id'] ?>">
            <button class="btn btn-outline-danger" type="submit">Close ticket</button>
        </form>
    <?php endif; ?>
<?php endif; ?>

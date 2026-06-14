<?php

namespace PeopleInside\FirstPostApproval;

use Flarum\Approval\Event\PostWasApproved;
use Flarum\Extend;
use Flarum\Post\Event\Saving;
use Flarum\Post\Event\Deleting as PostDeleting;
use Flarum\Post\Event\Hidden as PostHidden;
use Flarum\Post\Event\Restored as PostRestored;
use Flarum\Discussion\Event\Deleting as DiscussionDeleting;
use Flarum\Discussion\Event\Hidden as DiscussionHidden;
use Flarum\Discussion\Event\Restored as DiscussionRestored;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    new Extend\Locales(__DIR__ . '/resources/locale'),

    (new Extend\Event())
        ->listen(PostWasApproved::class, Listeners\CountPostApprovals::class)
        ->listen(Saving::class, Listeners\UnapproveNewPosts::class)
        ->listen(PostDeleting::class, Listeners\AdjustApprovalCounts::class)
        ->listen(PostHidden::class, Listeners\AdjustApprovalCounts::class)
        ->listen(PostRestored::class, Listeners\AdjustApprovalCounts::class)
        ->listen(DiscussionDeleting::class, Listeners\AdjustApprovalCounts::class)
        ->listen(DiscussionHidden::class, Listeners\AdjustApprovalCounts::class)
        ->listen(DiscussionRestored::class, Listeners\AdjustApprovalCounts::class),
];

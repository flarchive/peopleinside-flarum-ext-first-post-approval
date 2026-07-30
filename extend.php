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
use Flarum\User\User;
use PeopleInside\FirstPostApproval\Models\UserFirstPostApproval;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    new Extend\Locales(__DIR__ . '/resources/locale'),

    // Aggiungi la relazione al modello User
    (new Extend\Model(User::class))
        ->hasOne('firstPostApproval', UserFirstPostApproval::class, 'user_id'),

    (new Extend\Event())
        ->listen(PostWasApproved::class, Listeners\CountPostApprovals::class)
        ->listen(Saving::class, Listeners\UnapproveNewPosts::class)
        ->listen(PostDeleting::class, Listeners\SyncPostDeleting::class)
        ->listen(PostHidden::class, Listeners\SyncPostHidden::class)
        ->listen(PostRestored::class, Listeners\SyncPostRestored::class)
        ->listen(DiscussionDeleting::class, Listeners\SyncDiscussionDeleting::class)
        ->listen(DiscussionHidden::class, Listeners\SyncDiscussionHidden::class)
        ->listen(DiscussionRestored::class, Listeners\SyncDiscussionRestored::class),
];

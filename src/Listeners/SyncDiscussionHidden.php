<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Discussion\Event\Hidden;

class SyncDiscussionHidden
{
    use SyncsUserCounts;

    public function handle(Hidden $event)
    {
        $userIds = $event->discussion->posts()->pluck('user_id')->unique()->toArray();
        $this->syncUsers($userIds, $event->discussion->id);
    }
}

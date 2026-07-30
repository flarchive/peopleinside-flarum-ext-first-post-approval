<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Discussion\Event\Deleting;

class SyncDiscussionDeleting
{
    use SyncsUserCounts;

    public function handle(Deleting $event)
    {
        $userIds = $event->discussion->posts()->pluck('user_id')->unique()->toArray();
        $this->syncUsers($userIds, $event->discussion->id);
    }
}

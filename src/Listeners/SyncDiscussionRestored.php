<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Discussion\Event\Restored;

class SyncDiscussionRestored
{
    use SyncsUserCounts;

    public function handle(Restored $event)
    {
        $userIds = $event->discussion->posts()->pluck('user_id')->unique()->toArray();
        $this->syncUsers($userIds, 0);
    }
}

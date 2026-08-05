<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Discussion\Event\Restored;

class SyncDiscussionRestored
{
    use SyncsUserCounts;

    public function handle(Restored $event)
    {
        $this->syncUsersIncrement($event->discussion->id);
    }
}

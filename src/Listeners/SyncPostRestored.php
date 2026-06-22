<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Event\Restored;

class SyncPostRestored
{
    use SyncsUserCounts;

    public function handle(Restored $event)
    {
        $this->syncUser($event->post->user, $event->post->id);
    }
}

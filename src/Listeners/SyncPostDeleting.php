<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Event\Deleting;

class SyncPostDeleting
{
    use SyncsUserCounts;

    public function handle(Deleting $event)
    {
        $this->syncUser($event->post->user, $event->post->id);
    }
}

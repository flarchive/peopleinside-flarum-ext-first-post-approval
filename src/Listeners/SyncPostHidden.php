<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Event\Hidden;

class SyncPostHidden
{
    use SyncsUserCounts;

    public function handle(Hidden $event)
    {
        $this->syncUser($event->post->user, $event->post->id);
    }
}

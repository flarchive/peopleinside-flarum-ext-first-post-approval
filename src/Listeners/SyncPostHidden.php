<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Event\Hidden;

class SyncPostHidden
{
    use SyncsUserCounts;

    public function handle(Hidden $event)
    {
        $post = $event->post;
        if ($post->is_approved) {
            $this->syncUser($post->user);
        }
    }
}

<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Event\Deleting;

class SyncPostDeleting
{
    use SyncsUserCounts;

    public function handle(Deleting $event)
    {
        $post = $event->post;
        if ($post->is_approved) {
            $this->syncUser($post->user, $post->id);
        }
    }
}

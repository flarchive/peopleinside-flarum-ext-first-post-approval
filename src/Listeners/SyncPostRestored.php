<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Event\Restored;

class SyncPostRestored
{
    public function handle(Restored $event)
    {
        $post = $event->post;
        if ($post->is_approved) {
            $user = $post->user;
            if ($user) {
                if ($post->number == 1) {
                    $user->increment('first_discussion_approval_count');
                } else {
                    $user->increment('first_post_approval_count');
                }
            }
        }
    }
}

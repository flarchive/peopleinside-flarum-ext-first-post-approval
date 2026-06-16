<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Discussion\Event\Restored;
use Flarum\Post\Post;
use Flarum\User\User;

class SyncDiscussionRestored
{
    public function handle(Restored $event)
    {
        $discussion = $event->discussion;
        
        // Find all approved posts in this discussion
        $posts = Post::where('discussion_id', $discussion->id)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->get();

        $discussionIncrements = [];
        $postIncrements = [];

        foreach ($posts as $post) {
            $userId = $post->user_id;
            if (!$userId) {
                continue;
            }

            if ($post->number == 1) {
                $discussionIncrements[$userId] = ($discussionIncrements[$userId] ?? 0) + 1;
            } else {
                $postIncrements[$userId] = ($postIncrements[$userId] ?? 0) + 1;
            }
        }

        // Group the user IDs by the specific increment values to run bulk queries
        $discussionIncGroups = [];
        $postIncGroups = [];

        foreach ($discussionIncrements as $userId => $inc) {
            if ($inc > 0) {
                $discussionIncGroups[$inc][] = $userId;
            }
        }

        foreach ($postIncrements as $userId => $inc) {
            if ($inc > 0) {
                $postIncGroups[$inc][] = $userId;
            }
        }

        // Mass-update using single database queries per increment value (extremely high performance)
        foreach ($discussionIncGroups as $inc => $ids) {
            User::whereIn('id', $ids)->increment('first_discussion_approval_count', $inc);
        }

        foreach ($postIncGroups as $inc => $ids) {
            User::whereIn('id', $ids)->increment('first_post_approval_count', $inc);
        }
    }
}

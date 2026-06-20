<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Discussion\Event\Deleting;
use Flarum\Post\Post;

class SyncDiscussionDeleting
{
    use SyncsUserCounts;

    public function handle(Deleting $event)
    {
        $discussion = $event->discussion;
        
        // Find all unique users who have approved posts in this discussion
        $userIds = Post::where('discussion_id', $discussion->id)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->pluck('user_id')
            ->unique()
            ->filter()
            ->toArray();

        $this->syncUsers($userIds, $discussion->id);
    }
}

<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Discussion\Event\Hidden;
use Flarum\Post\Post;

class SyncDiscussionHidden
{
    use SyncsUserCounts;

    public function handle(Hidden $event)
    {
        $discussion = $event->discussion;
        
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

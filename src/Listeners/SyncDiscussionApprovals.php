<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Discussion\Event\Deleting;
use Flarum\Discussion\Event\Hidden;
use Flarum\Discussion\Event\Restored;

class SyncDiscussionApprovals
{
    public function handle($event)
    {
        if ($event instanceof Deleting) {
            $this->handleDiscussionDeleting($event);
        } elseif ($event instanceof Hidden) {
            $this->handleDiscussionHidden($event);
        } elseif ($event instanceof Restored) {
            $this->handleDiscussionRestored($event);
        }
    }

    protected function handleDiscussionDeleting(Deleting $event)
    {
        $discussion = $event->discussion;
        
        // Find all unique users who have approved posts in this discussion
        $userIds = \Flarum\Post\Post::where('discussion_id', $discussion->id)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->pluck('user_id')
            ->unique()
            ->filter()
            ->toArray();

        $this->syncUsers($userIds, $discussion->id);
    }

    protected function handleDiscussionHidden(Hidden $event)
    {
        $discussion = $event->discussion;
        
        $userIds = \Flarum\Post\Post::where('discussion_id', $discussion->id)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->pluck('user_id')
            ->unique()
            ->filter()
            ->toArray();

        $this->syncUsers($userIds, $discussion->id);
    }

    protected function handleDiscussionRestored(Restored $event)
    {
        $discussion = $event->discussion;
        
        // Fix the lazy relationship loading N+1 issue on discussion restore by eager-loading 'user' relation
        $posts = \Flarum\Post\Post::where('discussion_id', $discussion->id)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->with('user')
            ->get();

        foreach ($posts as $post) {
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

    protected function syncUsers($userIds, $excludingDiscussionId)
    {
        if (empty($userIds)) {
            return;
        }

        // Get actual discussion (number = 1) counts for all these users (O(1) bulk query)
        $actualDiscussions = \Flarum\Post\Post::whereIn('user_id', $userIds)
            ->where('number', 1)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->where('discussion_id', '!=', $excludingDiscussionId)
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as count')
            ->pluck('count', 'user_id')
            ->toArray();

        // Get actual post (number > 1) counts for all these users (O(1) bulk query)
        $actualPosts = \Flarum\Post\Post::whereIn('user_id', $userIds)
            ->where('number', '>', 1)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->where('discussion_id', '!=', $excludingDiscussionId)
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as count')
            ->pluck('count', 'user_id')
            ->toArray();

        // Fetch user models in bulk
        $users = \Flarum\User\User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $userDiscussions = $actualDiscussions[$user->id] ?? 0;
            $userPosts = $actualPosts[$user->id] ?? 0;

            $user->first_discussion_approval_count = min($user->first_discussion_approval_count, $userDiscussions);
            $user->first_post_approval_count = min($user->first_post_approval_count, $userPosts);
            $user->save();
        }
    }
}

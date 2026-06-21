<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Discussion\Event\Deleting;
use Flarum\Discussion\Event\Hidden;
use Flarum\Discussion\Event\Restored;
use Flarum\Post\Post;
use Flarum\User\User;

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
        $userIds = Post::where('discussion_id', $discussion->id)
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
        
        $userIds = Post::where('discussion_id', $discussion->id)
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
        
        // Optimize the lazy relationship loading and prevent O(N) database UPDATE queries
        // by grouping post approvals per user and updating them in bulk.
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

        $userIds = array_unique(array_merge(array_keys($discussionIncrements), array_keys($postIncrements)));

        if (!empty($userIds)) {
            $users = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                $incDisc = $discussionIncrements[$user->id] ?? 0;
                $incPost = $postIncrements[$user->id] ?? 0;

                if ($incDisc > 0) {
                    $user->first_discussion_approval_count += $incDisc;
                }
                if ($incPost > 0) {
                    $user->first_post_approval_count += $incPost;
                }
                
                $user->save();
            }
        }
    }

    protected function syncUsers($userIds, $excludingDiscussionId)
    {
        if (empty($userIds)) {
            return;
        }

        // Get actual discussion (number = 1) counts for all these users (O(1) bulk query)
        $actualDiscussions = Post::whereIn('user_id', $userIds)
            ->where('number', 1)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->where('discussion_id', '!=', $excludingDiscussionId)
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as count')
            ->pluck('count', 'user_id')
            ->toArray();

        // Get actual post (number > 1) counts for all these users (O(1) bulk query)
        $actualPosts = Post::whereIn('user_id', $userIds)
            ->where('number', '>', 1)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->where('discussion_id', '!=', $excludingDiscussionId)
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as count')
            ->pluck('count', 'user_id')
            ->toArray();

        // Fetch user models in bulk
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $userDiscussions = $actualDiscussions[$user->id] ?? 0;
            $userPosts = $actualPosts[$user->id] ?? 0;

            $user->first_discussion_approval_count = min($user->first_discussion_approval_count, $userDiscussions);
            $user->first_post_approval_count = min($user->first_post_approval_count, $userPosts);
            $user->save();
        }
    }
}

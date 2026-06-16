<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Post;
use Flarum\User\User;

trait SyncsUserCounts
{
    protected function syncUser($user, $excludingPostId = null)
    {
        if (!$user) {
            return;
        }

        $discussionQuery = Post::where('user_id', $user->id)
            ->where('number', 1)
            ->where('is_approved', 1)
            ->whereNull('hidden_at');

        if ($excludingPostId) {
            $discussionQuery->where('id', '!=', $excludingPostId);
        }

        $actualDiscussions = $discussionQuery->count();

        $postQuery = Post::where('user_id', $user->id)
            ->where('number', '>', 1)
            ->where('is_approved', 1)
            ->whereNull('hidden_at');

        if ($excludingPostId) {
            $postQuery->where('id', '!=', $excludingPostId);
        }

        $actualPosts = $postQuery->count();

        $user->first_discussion_approval_count = min($user->first_discussion_approval_count, $actualDiscussions);
        $user->first_post_approval_count = min($user->first_post_approval_count, $actualPosts);
        $user->save();
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

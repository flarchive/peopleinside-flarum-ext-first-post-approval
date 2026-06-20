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

        // Fetch only the current counters in bulk (no full models needed, avoids per-row saves)
        $currentCounts = User::whereIn('id', $userIds)
            ->get(['id', 'first_discussion_approval_count', 'first_post_approval_count'])
            ->keyBy('id');

        // Compute the final (post-min) value for each user, then group user IDs by
        // their resulting (discussion, post) pair so each distinct pair can be written
        // with a single bulk UPDATE instead of one save() per user.
        $groups = [];

        foreach ($currentCounts as $userId => $user) {
            $userDiscussions = $actualDiscussions[$userId] ?? 0;
            $userPosts = $actualPosts[$userId] ?? 0;

            $newDiscussionCount = min($user->first_discussion_approval_count, $userDiscussions);
            $newPostCount = min($user->first_post_approval_count, $userPosts);

            // No-op for this user: skip it entirely, no need to touch the row.
            if ($newDiscussionCount === $user->first_discussion_approval_count
                && $newPostCount === $user->first_post_approval_count) {
                continue;
            }

            $groups[$newDiscussionCount . ':' . $newPostCount][] = $userId;
        }

        foreach ($groups as $key => $ids) {
            [$discussionCount, $postCount] = array_map('intval', explode(':', $key));

            User::whereIn('id', $ids)->update([
                'first_discussion_approval_count' => $discussionCount,
                'first_post_approval_count' => $postCount,
            ]);
        }
    }
}

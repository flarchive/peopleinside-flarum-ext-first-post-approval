<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Post;

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

        $current = $this->db->table('user_first_post_approval')->where('user_id', $user->id)->first();
        $currentDisc = $current ? (int) $current->first_discussion_approval_count : 0;
        $currentPost = $current ? (int) $current->first_post_approval_count : 0;

        $this->db->table('user_first_post_approval')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'first_discussion_approval_count' => min($currentDisc, $actualDiscussions),
                'first_post_approval_count' => min($currentPost, $actualPosts),
            ]
        );
    }

    protected function syncUsers($userIds, $excludingDiscussionId)
    {
        if (empty($userIds)) {
            return;
        }

        $groups = $this->diffUserCounts($userIds, $excludingDiscussionId);
        $this->applyUserCountUpdates($groups);
    }

    private function diffUserCounts(array $userIds, int $excludingDiscussionId): array
    {
        $actualDiscussions = Post::whereIn('user_id', $userIds)
            ->where('number', 1)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->where('discussion_id', '!=', $excludingDiscussionId)
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as count')
            ->pluck('count', 'user_id')
            ->toArray();

        $actualPosts = Post::whereIn('user_id', $userIds)
            ->where('number', '>', 1)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->where('discussion_id', '!=', $excludingDiscussionId)
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as count')
            ->pluck('count', 'user_id')
            ->toArray();

        $currentCounts = $this->db->table('user_first_post_approval')
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'first_discussion_approval_count', 'first_post_approval_count'])
            ->keyBy('user_id');

        $groups = [];
        foreach ($userIds as $userId) {
            $current = $currentCounts[$userId] ?? null;
            $currentDisc = $current ? (int) $current->first_discussion_approval_count : 0;
            $currentPost = $current ? (int) $current->first_post_approval_count : 0;

            $userDiscussions = $actualDiscussions[$userId] ?? 0;
            $userPosts = $actualPosts[$userId] ?? 0;

            $newDiscussionCount = min($currentDisc, $userDiscussions);
            $newPostCount = min($currentPost, $userPosts);

            if ($newDiscussionCount === $currentDisc && $newPostCount === $currentPost) {
                continue;
            }

            $groups[$newDiscussionCount . ':' . $newPostCount][] = $userId;
        }

        return $groups;
    }

    private function applyUserCountUpdates(array $groups): void
    {
        foreach ($groups as $key => $ids) {
            [$discussionCount, $postCount] = array_map('intval', explode(':', $key));

            $this->db->table('user_first_post_approval')
                ->whereIn('user_id', $ids)
                ->update([
                    'first_discussion_approval_count' => $discussionCount,
                    'first_post_approval_count' => $postCount,
                ]);

            $existingIds = $this->db->table('user_first_post_approval')
                ->whereIn('user_id', $ids)
                ->pluck('user_id')
                ->toArray();

            $missingIds = array_diff($ids, $existingIds);
            if (!empty($missingIds)) {
                $insertData = [];
                foreach ($missingIds as $userId) {
                    $insertData[] = [
                        'user_id' => $userId,
                        'first_discussion_approval_count' => $discussionCount,
                        'first_post_approval_count' => $postCount,
                    ];
                }
                $this->db->table('user_first_post_approval')->insert($insertData);
            }
        }
    }

    protected function syncUsersIncrement($discussionId)
    {
        $increments = $this->aggregatePostIncrements($discussionId);
        $groups = $this->groupIncrements($increments);
        $this->applyIncrements($groups);
    }

    private function aggregatePostIncrements(int $discussionId): array
    {
        return Post::where('discussion_id', $discussionId)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->groupBy('user_id')
            ->selectRaw('user_id, SUM(CASE WHEN number = 1 THEN 1 ELSE 0 END) as disc_inc, SUM(CASE WHEN number > 1 THEN 1 ELSE 0 END) as post_inc')
            ->get()
            ->keyBy('user_id')
            ->toArray();
    }

    private function groupIncrements(array $increments): array
    {
        $groups = [];
        foreach ($increments as $userId => $row) {
            $discInc = (int) $row->disc_inc;
            $postInc = (int) $row->post_inc;

            if ($discInc > 0 || $postInc > 0) {
                $groups[$discInc . ':' . $postInc][] = $userId;
            }
        }
        return $groups;
    }

    private function applyIncrements(array $groups): void
    {
        foreach ($groups as $key => $ids) {
            [$discInc, $postInc] = array_map('intval', explode(':', $key));

            if ($discInc > 0) {
                $this->db->table('user_first_post_approval')
                    ->whereIn('user_id', $ids)
                    ->increment('first_discussion_approval_count', $discInc);
            }
            
            if ($postInc > 0) {
                $this->db->table('user_first_post_approval')
                    ->whereIn('user_id', $ids)
                    ->increment('first_post_approval_count', $postInc);
            }

            $existingIds = $this->db->table('user_first_post_approval')
                ->whereIn('user_id', $ids)
                ->pluck('user_id')
                ->toArray();

            $missingIds = array_diff($ids, $existingIds);
            if (!empty($missingIds)) {
                $insertData = [];
                foreach ($missingIds as $userId) {
                    $insertData[] = [
                        'user_id' => $userId,
                        'first_discussion_approval_count' => $discInc,
                        'first_post_approval_count' => $postInc,
                    ];
                }
                $this->db->table('user_first_post_approval')->insert($insertData);
            }
        }
    }
}
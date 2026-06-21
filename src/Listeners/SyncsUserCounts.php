<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Support\Facades\DB;

trait SyncsUserCounts
{
    /**
     * Ricalcola i conteggi per un singolo utente (usato per post hiding/deleting).
     */
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

    /**
     * Ricalcola i conteggi per più utenti dopo la cancellazione/nascondimento di una discussione.
     * Imposta i valori al numero effettivo di post approvati e visibili.
     */
    protected function syncUsers($userIds, $excludingDiscussionId)
    {
        if (empty($userIds)) {
            return;
        }

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

        $currentCounts = User::whereIn('id', $userIds)
            ->get(['id', 'first_discussion_approval_count', 'first_post_approval_count'])
            ->keyBy('id');

        $groups = [];
        foreach ($currentCounts as $userId => $user) {
            $userDiscussions = $actualDiscussions[$userId] ?? 0;
            $userPosts = $actualPosts[$userId] ?? 0;

            $newDiscussionCount = min($user->first_discussion_approval_count, $userDiscussions);
            $newPostCount = min($user->first_post_approval_count, $userPosts);

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

    /**
     * Incrementa i conteggi per tutti gli utenti che hanno post in una discussione ripristinata.
     * Raggruppa gli utenti per coppia (discInc, postInc) ed esegue un singolo UPDATE per gruppo,
     * aggiornando entrambi i campi in una sola query.
     */
    protected function syncUsersIncrement($discussionId)
    {
        $posts = Post::where('discussion_id', $discussionId)
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

        // Raggruppa gli utenti per la coppia (incremento discussioni, incremento post)
        $allUserIds = array_unique(array_merge(
            array_keys($discussionIncrements),
            array_keys($postIncrements)
        ));

        $groups = [];
        foreach ($allUserIds as $userId) {
            $discInc = $discussionIncrements[$userId] ?? 0;
            $postInc = $postIncrements[$userId] ?? 0;

            if ($discInc > 0 || $postInc > 0) {
                $groups[$discInc . ':' . $postInc][] = $userId;
            }
        }

        // Un singolo UPDATE per gruppo: aggiorna entrambi i campi in una query
        foreach ($groups as $key => $ids) {
            [$discInc, $postInc] = array_map('intval', explode(':', $key));

            $updateData = [];
            if ($discInc > 0) {
                $updateData['first_discussion_approval_count'] = DB::raw(
                    "first_discussion_approval_count + $discInc"
                );
            }
            if ($postInc > 0) {
                $updateData['first_post_approval_count'] = DB::raw(
                    "first_post_approval_count + $postInc"
                );
            }

            if (!empty($updateData)) {
                User::whereIn('id', $ids)->update($updateData);
            }
        }
    }
}

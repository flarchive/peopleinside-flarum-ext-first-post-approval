<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Event\Deleting as PostDeleting;
use Flarum\Post\Event\Hidden as PostHidden;
use Flarum\Post\Event\Restored as PostRestored;
use Flarum\Discussion\Event\Deleting as DiscussionDeleting;
use Flarum\Discussion\Event\Hidden as DiscussionHidden;
use Flarum\Discussion\Event\Restored as DiscussionRestored;

class AdjustApprovalCounts
{
    public function handle($event)
    {
        if ($event instanceof PostDeleting) {
            $this->handlePostDeleting($event);
        } elseif ($event instanceof PostHidden) {
            $this->handlePostHidden($event);
        } elseif ($event instanceof PostRestored) {
            $this->handlePostRestored($event);
        } elseif ($event instanceof DiscussionDeleting) {
            $this->handleDiscussionDeleting($event);
        } elseif ($event instanceof DiscussionHidden) {
            $this->handleDiscussionHidden($event);
        } elseif ($event instanceof DiscussionRestored) {
            $this->handleDiscussionRestored($event);
        }
    }

    protected function handlePostDeleting(PostDeleting $event)
    {
        $post = $event->post;
        if ($post->is_approved) {
            $this->syncUser($post->user, $post->id);
        }
    }

    protected function handlePostHidden(PostHidden $event)
    {
        $post = $event->post;
        if ($post->is_approved) {
            $this->syncUser($post->user);
        }
    }

    protected function handlePostRestored(PostRestored $event)
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

    protected function handleDiscussionDeleting(DiscussionDeleting $event)
    {
        $discussion = $event->discussion;
        
        // Find all unique users who have approved posts in this discussion
        $userIds = \Flarum\Post\Post::where('discussion_id', $discussion->id)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->pluck('user_id')
            ->unique()
            ->filter();

        foreach ($userIds as $userId) {
            $user = \Flarum\User\User::find($userId);
            if ($user) {
                $this->syncUser($user, null, $discussion->id);
            }
        }
    }

    protected function handleDiscussionHidden(DiscussionHidden $event)
    {
        $discussion = $event->discussion;
        
        $userIds = \Flarum\Post\Post::where('discussion_id', $discussion->id)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
            ->pluck('user_id')
            ->unique()
            ->filter();

        foreach ($userIds as $userId) {
            $user = \Flarum\User\User::find($userId);
            if ($user) {
                $this->syncUser($user, null, $discussion->id);
            }
        }
    }

    protected function handleDiscussionRestored(DiscussionRestored $event)
    {
        $discussion = $event->discussion;
        
        $posts = \Flarum\Post\Post::where('discussion_id', $discussion->id)
            ->where('is_approved', 1)
            ->whereNull('hidden_at')
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

    protected function syncUser($user, $excludingPostId = null, $excludingDiscussionId = null)
    {
        if (!$user) {
            return;
        }

        $discussionQuery = \Flarum\Post\Post::where('user_id', $user->id)
            ->where('number', 1)
            ->where('is_approved', 1)
            ->whereNull('hidden_at');

        if ($excludingPostId) {
            $discussionQuery->where('id', '!=', $excludingPostId);
        }
        if ($excludingDiscussionId) {
            $discussionQuery->where('discussion_id', '!=', $excludingDiscussionId);
        }

        $actualDiscussions = $discussionQuery->count();

        $postQuery = \Flarum\Post\Post::where('user_id', $user->id)
            ->where('number', '>', 1)
            ->where('is_approved', 1)
            ->whereNull('hidden_at');

        if ($excludingPostId) {
            $postQuery->where('id', '!=', $excludingPostId);
        }
        if ($excludingDiscussionId) {
            $postQuery->where('discussion_id', '!=', $excludingDiscussionId);
        }

        $actualPosts = $postQuery->count();

        $user->first_discussion_approval_count = min($user->first_discussion_approval_count, $actualDiscussions);
        $user->first_post_approval_count = min($user->first_post_approval_count, $actualPosts);
        $user->save();
    }
}

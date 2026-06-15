<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Event\Deleting;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Restored;

class SyncPostApprovals
{
    public function handle($event)
    {
        if ($event instanceof Deleting) {
            $this->handlePostDeleting($event);
        } elseif ($event instanceof Hidden) {
            $this->handlePostHidden($event);
        } elseif ($event instanceof Restored) {
            $this->handlePostRestored($event);
        }
    }

    protected function handlePostDeleting(Deleting $event)
    {
        $post = $event->post;
        if ($post->is_approved) {
            $this->syncUser($post->user, $post->id);
        }
    }

    protected function handlePostHidden(Hidden $event)
    {
        $post = $event->post;
        if ($post->is_approved) {
            $this->syncUser($post->user);
        }
    }

    protected function handlePostRestored(Restored $event)
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

    protected function syncUser($user, $excludingPostId = null)
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

        $actualDiscussions = $discussionQuery->count();

        $postQuery = \Flarum\Post\Post::where('user_id', $user->id)
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
}

<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Carbon\Carbon;
use Flarum\Flags\Flag;
use Flarum\Post\Event\Saving;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;

class UnapproveNewPosts
{
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function handle(Saving $event)
    {
        $post = $event->post;

        if ($post->exists) {
            return;
        }

        if (!$post->discussion) {
            return;
        }

        if ($event->actor->can('firstPostWithoutApproval', $post->discussion)) {
            return;
        }

        $discussionCount = (int) ($this->settings->get('peopleinside-first-post-approval.discussionCount') ?? $this->settings->get('clarkwinkelmann-first-post-approval.discussionCount'));
        $postCount = (int) ($this->settings->get('peopleinside-first-post-approval.postCount') ?? $this->settings->get('clarkwinkelmann-first-post-approval.postCount'));

        $isFirstPost = $post->discussion->first_post_id === null && !$post->discussion->posts()->exists();

        if ($isFirstPost && $discussionCount > 0) {
            // If this is a new discussion and if a rule has been defined for new discussions
            if (((int)$event->actor->first_discussion_approval_count) >= $discussionCount) {
                return;
            }
        } else {
            // If this is a reply, or if there's no rule defined for new discussions
            $currentApprovedReplica = (int)$event->actor->first_post_approval_count;
            if ($discussionCount === 0) {
                // If there's no separate rule for discussions, then discussion approvals also count towards the post limit
                $currentApprovedReplica += (int)$event->actor->first_discussion_approval_count;
            }

            if ($currentApprovedReplica >= $postCount) {
                return;
            }
        }

        $post->is_approved = false;

        $post->afterSave(function (Post $post) {
            if ($post->number == 1) {
                $post->discussion->is_approved = false;
                $post->discussion->save();
            }

            $flag = new Flag();

            $flag->post_id = $post->id;
            $flag->type = 'approval';
            $flag->created_at = Carbon::now();

            $flag->save();
        });
    }
}

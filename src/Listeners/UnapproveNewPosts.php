<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Carbon\Carbon;
use Flarum\Flags\Flag;
use Flarum\Post\Event\Saving;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use PeopleInside\FirstPostApproval\Models\UserFirstPostApproval;

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

        $isFirstPost = $post->discussion->first_post_id === null;

        // Lettura tramite il modello Eloquent
        $approval = UserFirstPostApproval::find($event->actor->id);
        $actorDiscussionCount = $approval ? (int) $approval->first_discussion_approval_count : 0;
        $actorPostCount = $approval ? (int) $approval->first_post_approval_count : 0;

        if ($isFirstPost && $discussionCount > 0) {
            if ($actorDiscussionCount >= $discussionCount) {
                return;
            }
        } else {
            $currentApprovedReplica = $actorPostCount;
            if ($discussionCount === 0) {
                $currentApprovedReplica += $actorDiscussionCount;
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

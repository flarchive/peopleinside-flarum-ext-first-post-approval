<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Approval\Event\PostWasApproved;
use Illuminate\Database\ConnectionInterface;

class CountPostApprovals
{
    protected $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function handle(PostWasApproved $event)
    {
        $user = $event->post->user;

        if (!$user) {
            return;
        }

        if ($event->post->hidden_at) {
            return;
        }

        $isDiscussion = $event->post->number == 1;
        $exists = $this->db->table('user_first_post_approval')->where('user_id', $user->id)->exists();
        
        if (!$exists) {
            $this->db->table('user_first_post_approval')->insert([
                'user_id' => $user->id,
                'first_discussion_approval_count' => $isDiscussion ? 1 : 0,
                'first_post_approval_count' => !$isDiscussion ? 1 : 0,
            ]);
        } else {
            $this->db->table('user_first_post_approval')
                ->where('user_id', $user->id)
                ->increment($isDiscussion ? 'first_discussion_approval_count' : 'first_post_approval_count');
        }
    }
}
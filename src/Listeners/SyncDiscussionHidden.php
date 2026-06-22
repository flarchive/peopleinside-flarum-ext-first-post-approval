<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Discussion\Event\Hidden;
use Illuminate\Database\ConnectionInterface;

class SyncDiscussionHidden
{
    use SyncsUserCounts;

    protected $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function handle(Hidden $event)
    {
        $userIds = $event->discussion->posts()->pluck('user_id')->unique()->toArray();
        $this->syncUsers($userIds, $event->discussion->id);
    }
}
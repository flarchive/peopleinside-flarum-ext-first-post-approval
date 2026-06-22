<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Discussion\Event\Deleting;
use Illuminate\Database\ConnectionInterface;

class SyncDiscussionDeleting
{
    use SyncsUserCounts;

    protected $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function handle(Deleting $event)
    {
        $userIds = $event->discussion->posts()->pluck('user_id')->unique()->toArray();
        $this->syncUsers($userIds, $event->discussion->id);
    }
}
<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Discussion\Event\Restored;
use Illuminate\Database\ConnectionInterface;

class SyncDiscussionRestored
{
    use SyncsUserCounts;

    protected $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function handle(Restored $event)
    {
        $this->syncUsersIncrement($event->discussion->id);
    }
}
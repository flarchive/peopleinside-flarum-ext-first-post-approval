<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Event\Restored;
use Illuminate\Database\ConnectionInterface;

class SyncPostRestored
{
    use SyncsUserCounts;

    protected $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function handle(Restored $event)
    {
        $this->syncUser($event->post->user);
    }
}
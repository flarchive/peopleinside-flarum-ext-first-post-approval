<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Event\Hidden;
use Illuminate\Database\ConnectionInterface;

class SyncPostHidden
{
    use SyncsUserCounts;

    protected $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function handle(Hidden $event)
    {
        $this->syncUser($event->post->user);
    }
}
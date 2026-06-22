<?php

namespace PeopleInside\FirstPostApproval\Listeners;

use Flarum\Post\Event\Deleting;
use Illuminate\Database\ConnectionInterface;

class SyncPostDeleting
{
    use SyncsUserCounts;

    protected $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function handle(Deleting $event)
    {
        $this->syncUser($event->post->user, $event->post->id);
    }
}
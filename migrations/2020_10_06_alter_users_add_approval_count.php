<?php

use Flarum\Database\Migration;

return Migration::addColumns('users', [
    'first_post_approval_count' => ['integer', 'unsigned' => true, 'default' => 0],
    'first_discussion_approval_count' => ['integer', 'unsigned' => true, 'default' => 0],
]);

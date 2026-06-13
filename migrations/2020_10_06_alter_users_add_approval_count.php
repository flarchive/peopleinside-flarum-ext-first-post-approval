<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if (!$schema->hasColumn('users', 'first_post_approval_count')) {
            $schema->table('users', function (Blueprint $table) {
                $table->tinyInteger('first_post_approval_count')->unsigned()->default(0);
            });
        }
        if (!$schema->hasColumn('users', 'first_discussion_approval_count')) {
            $schema->table('users', function (Blueprint $table) {
                $table->tinyInteger('first_discussion_approval_count')->unsigned()->default(0);
            });
        }
    },
    'down' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn('first_post_approval_count');
            $table->dropColumn('first_discussion_approval_count');
        });
    }
];

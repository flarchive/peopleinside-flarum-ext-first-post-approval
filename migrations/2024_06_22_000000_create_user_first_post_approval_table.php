<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

// Utilizzo dell'helper di Flarum per la creazione dello schema
$migration = Migration::createTable('user_first_post_approval', function (Blueprint $table) {
    $table->unsignedInteger('user_id')->primary();
    $table->unsignedInteger('first_post_approval_count')->default(0);
    $table->unsignedInteger('first_discussion_approval_count')->default(0);

    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
});

$originalUp = $migration['up'];
$originalDown = $migration['down'];

return [
    'up' => function (Builder $schema) use ($originalUp) {
        // Esegue la creazione della tabella
        $originalUp($schema);
        
        // Migrazione dei dati esistente: usa il query builder per gestire i prefissi in modo sicuro
        if ($schema->hasColumn('users', 'first_post_approval_count')) {
            $connection = $schema->getConnection();
            $connection->table('user_first_post_approval')->truncate();
            
            $connection->table('user_first_post_approval')
                ->insertUsing(
                    ['user_id', 'first_post_approval_count', 'first_discussion_approval_count'],
                    $connection->table('users')
                        ->select('id', 'first_post_approval_count', 'first_discussion_approval_count')
                        ->where('first_post_approval_count', '>', 0)
                        ->orWhere('first_discussion_approval_count', '>', 0)
                );

            $schema->table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'first_post_approval_count',
                    'first_discussion_approval_count'
                ]);
            });
        }
    },
    'down' => function (Builder $schema) use ($originalDown) {
        if (!$schema->hasColumn('users', 'first_post_approval_count')) {
            $schema->table('users', function (Blueprint $table) {
                $table->unsignedInteger('first_post_approval_count')->default(0);
                $table->unsignedInteger('first_discussion_approval_count')->default(0);
            });
        }

        if ($schema->hasTable('user_first_post_approval')) {
            $connection = $schema->getConnection();
            $connection->table('user_first_post_approval')
                ->orderBy('user_id')
                ->chunk(500, function ($approvals) use ($connection) {
                    foreach ($approvals as $approval) {
                        $connection->table('users')
                            ->where('id', $approval->user_id)
                            ->update([
                                'first_post_approval_count' => $approval->first_post_approval_count,
                                'first_discussion_approval_count' => $approval->first_discussion_approval_count,
                            ]);
                    }
                });
        }
        
        // Esegue la rimozione della tabella
        $originalDown($schema);
    }
];

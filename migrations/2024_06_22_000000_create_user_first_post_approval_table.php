<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $prefix = $schema->getConnection()->getTablePrefix();
        $connection = $schema->getConnection();

        // Crea la tabella solo se non esiste già
        if (!$schema->hasTable('user_first_post_approval')) {
            $schema->create('user_first_post_approval', function (Blueprint $table) {
                $table->unsignedInteger('user_id')->primary();
                $table->unsignedInteger('first_post_approval_count')->default(0);
                $table->unsignedInteger('first_discussion_approval_count')->default(0);
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Migra i dati solo se le colonne esistono ancora nella tabella users
        if ($schema->hasColumn('users', 'first_post_approval_count')) {
            // Svuota la tabella di destinazione per evitare duplicati in caso di re-run
            $connection->table('user_first_post_approval')->truncate();

            $connection->statement('
                INSERT INTO ' . $prefix . 'user_first_post_approval 
                    (user_id, first_post_approval_count, first_discussion_approval_count)
                SELECT id, first_post_approval_count, first_discussion_approval_count
                FROM ' . $prefix . 'users
                WHERE first_post_approval_count > 0 OR first_discussion_approval_count > 0
            ');
            
            $schema->table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'first_post_approval_count',
                    'first_discussion_approval_count'
                ]);
            });
        }
    },
    'down' => function (Builder $schema) {
        $prefix = $schema->getConnection()->getTablePrefix();
        $connection = $schema->getConnection();

        // Ricrea le colonne nella tabella users solo se non esistono già
        if (!$schema->hasColumn('users', 'first_post_approval_count')) {
            $schema->table('users', function (Blueprint $table) {
                $table->unsignedInteger('first_post_approval_count')->default(0);
                $table->unsignedInteger('first_discussion_approval_count')->default(0);
            });
        }

        // Riporta i dati solo se la tabella companion esiste
        if ($schema->hasTable('user_first_post_approval')) {
            $approvals = $connection->table('user_first_post_approval')->get();
            
            foreach ($approvals as $approval) {
                $connection->table('users')
                    ->where('id', $approval->user_id)
                    ->update([
                        'first_post_approval_count' => $approval->first_post_approval_count,
                        'first_discussion_approval_count' => $approval->first_discussion_approval_count,
                    ]);
            }

            $schema->dropIfExists('user_first_post_approval');
        }
    }
];

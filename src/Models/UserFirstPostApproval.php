<?php

namespace PeopleInside\FirstPostApproval\Models;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

class UserFirstPostApproval extends AbstractModel
{
    protected $table = 'user_first_post_approval';
    public $timestamps = false;
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = ['user_id', 'first_post_approval_count', 'first_discussion_approval_count'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

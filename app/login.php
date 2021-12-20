<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class login extends Model
{
    protected $fillable = [ 'id', 'action', 'iin', 'password', 'logged', 'token' ];
}

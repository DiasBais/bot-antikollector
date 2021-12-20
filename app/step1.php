<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class step1 extends Model
{
    protected $fillable = [ 'id', 'fio', 'iin', 'phone_number', 'email', 'password', 'confirmPhoneNumber' ];
}

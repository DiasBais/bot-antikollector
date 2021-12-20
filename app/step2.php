<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class step2 extends Model
{
    protected $fillable = [ 'id', 'problem', 'description_problem', 'name_organization', 'debt', 'loan_data' ];
}

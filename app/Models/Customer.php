<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'local_amt',
        'no_due_days',
        'number1',
        'number2',
    ];
}

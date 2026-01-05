<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelLoanRule extends Model
{
    protected $table = 'loan_rules';

    protected $fillable = [
        'min_year',
        'max_year',
        'max_nominal',
        'max_tenor',
    ];
}

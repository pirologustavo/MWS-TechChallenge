<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @mixin Builder */
class Status extends Model
{
    protected $table = 'status';

    protected $primaryKey = 'statid';

    protected $fillable = ['statusdesc'];
}

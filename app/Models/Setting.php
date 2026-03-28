<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'kv_settings';

    protected $fillable = [
        'key',
        'value',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagePlan extends Model
{
    protected $table = 'package_plans';

    protected $fillable = [
        'major',
        'name',
        'description',
        'price',
        'courses',
        'active',
        'sort_order'
    ];

    protected $casts = [
        'courses' => 'array',
        'active' => 'boolean',
        'price' => 'float',
        'sort_order' => 'integer'
    ];
}

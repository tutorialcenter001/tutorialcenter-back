<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SupportCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [

        'name',

        'slug',

        'description',

        'icon',

        'color',

        'sort_order',

        'is_active',

    ];

    protected $casts = [

        'id' => 'integer',

        'sort_order' => 'integer',

        'is_active' => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | Boot
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {

            if (empty($category->slug)) {

                $category->slug = Str::slug(
                    $category->name
                );

            }

        });

        static::updating(function ($category) {

            if ($category->isDirty('name')) {

                $category->slug = Str::slug(
                    $category->name
                );

            }

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function tickets()
    {
        return $this->hasMany(
            SupportTicket::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where(
            'is_active',
            true
        );
    }
}
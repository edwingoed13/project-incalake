<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Configuration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name',
        'index_title',
        'index_keywords',
        'index_description',
        'index_logo_id',
        'index_favicon_id',
        'google_analytics_code',
        'zoopim_code',
        'user_id',
    ];

    protected $casts = [
        'index_logo_id' => 'integer',
        'index_favicon_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

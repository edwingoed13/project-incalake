<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'language', 'source', 'unsubscribed_at'];

    protected $casts = ['unsubscribed_at' => 'datetime'];
}

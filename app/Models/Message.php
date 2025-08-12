<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'type',
        'status',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class);
    }

    public function getRecipientCountAttribute(): int
    {
        return $this->recipients()->count();
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')->where('status', 'scheduled');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }
}

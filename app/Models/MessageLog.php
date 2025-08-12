<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'content',
        'total_recipients',
        'success_count',
        'failed_count',
        'cost',
        'details',
        'user_id'
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSms($query)
    {
        return $query->where('type', 'sms');
    }

    public function scopeWhatsapp($query)
    {
        return $query->where('type', 'whatsapp');
    }

    public function successRate()
    {
        if ($this->total_recipients === 0) return 0;
        return ($this->success_count / $this->total_recipients) * 100;
    }
}

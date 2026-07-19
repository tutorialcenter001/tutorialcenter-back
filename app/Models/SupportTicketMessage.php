<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketMessage extends Model
{
    protected $fillable = [

        'support_ticket_id',

        'sender_type',

        'sender_id',

        'message',

        'is_internal',

    ];

    protected $casts = [

        'id' => 'integer',

        'support_ticket_id' => 'integer',

        'is_internal' => 'boolean',

    ];

    public function ticket()
    {
        return $this->belongsTo(
            SupportTicket::class,
            'support_ticket_id'
        );
    }

    public function sender()
    {
        return $this->morphTo();
    }

    public function attachments()
    {
        return $this->hasMany(
            SupportTicketAttachment::class
        );
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketAttachment extends Model
{
    protected $fillable = [

        'support_ticket_message_id',

        'file_name',

        'file_path',

        'mime_type',

        'file_size',

    ];

    protected $casts = [

        'id' => 'integer',

        'support_ticket_message_id' => 'integer',

        'file_size' => 'integer',

    ];

    public function message()
    {
        return $this->belongsTo(
            SupportTicketMessage::class,
            'support_ticket_message_id'
        );
    }
}
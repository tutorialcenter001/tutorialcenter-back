<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    const OPEN = 'open';

    const PENDING = 'pending';

    const IN_PROGRESS = 'in_progress';

    const RESOLVED = 'resolved';

    const CLOSED = 'closed';

    protected $fillable = [

        'ticket_number',

        'requester_type',

        'requester_id',

        'assigned_staff_id',

        // 'category',
        'support_category_id',

        'subject',

        'description',

        'priority',

        'status',

        'last_reply_at',

        'resolved_at',

        'closed_at',

    ];

    protected $casts = [

        'id' => 'integer',

        'support_category_id' => 'integer',

        'assigned_staff_id' => 'integer',

        'last_reply_at' => 'datetime',

        'resolved_at' => 'datetime',

        'closed_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(
            SupportCategory::class,
            'support_category_id'
        );
    }

    public function requester()
    {
        return $this->morphTo();
    }

    public function assignedStaff()
    {
        return $this->belongsTo(
            Staff::class,
            'assigned_staff_id'
        );
    }

    public function messages()
    {
        return $this->hasMany(
            SupportTicketMessage::class
        );
    }
}

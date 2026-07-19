<?php

namespace App\Services;

use Exception;
use App\Models\Staff;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\SupportTicket;
use App\Models\SupportCategory;
use App\Models\SupportTicketMessage;

class SupportService
{
    /**
     * Generate unique ticket number.
     */
    protected function generateTicketNumber(): string
    {
        return sprintf(
            'TCS-%s-%06d',
            now()->format('Ymd'),
            SupportTicket::count() + 1
        );
    }

    /**
     * Create Ticket
     */
    public function createTicket(
        Model $requester,
        array $data
    ): SupportTicket {

        return DB::transaction(function () use (
            $requester,
            $data
        ) {

            $category = SupportCategory::findOrFail(
                $data['support_category_id']
            );

            if (!$category->is_active) {
                throw new Exception(
                    'Support category is inactive.'
                );
            }

            $ticket = SupportTicket::create([

                'ticket_number' => $this->generateTicketNumber(),

                'requester_type' => get_class($requester),

                'requester_id' => $requester->id,

                'support_category_id' => $category->id,

                'subject' => $data['subject'],

                'description' => $data['description'],

                'priority' => $data['priority'] ?? 'medium',

                'status' => SupportTicket::OPEN,

                'last_reply_at' => now(),

            ]);

            SupportTicketMessage::create([

                'support_ticket_id' => $ticket->id,

                'sender_type' => get_class($requester),

                'sender_id' => $requester->id,

                'message' => $data['description'],

            ]);

            return $ticket->load([
                'category',
                'messages'
            ]);

        });
    }

    /**
     * Reply to Ticket
     */
    public function reply(
        SupportTicket $ticket,
        Model $sender,
        string $message
    ): SupportTicketMessage {

        $reply = SupportTicketMessage::create([

            'support_ticket_id' => $ticket->id,

            'sender_type' => get_class($sender),

            'sender_id' => $sender->id,

            'message' => $message,

        ]);

        $ticket->update([
            'last_reply_at' => now(),
        ]);

        return $reply;
    }

    /**
     * Close Ticket
     */
    public function close(
        SupportTicket $ticket
    ): SupportTicket {

        $ticket->update([

            'status' => SupportTicket::CLOSED,

            'closed_at' => now(),

        ]);

        return $ticket->fresh();
    }

    /**
     * Reopen Ticket
     */
    public function reopen(
        SupportTicket $ticket
    ): SupportTicket {

        $ticket->update([

            'status' => SupportTicket::OPEN,

            'closed_at' => null,

        ]);

        return $ticket->fresh();
    }

    /**
     * Assign Ticket
     */
    public function assign(
        SupportTicket $ticket,
        Staff $staff
    ): SupportTicket {

        $ticket->update([

            'assigned_staff_id' => $staff->id,

            'status' => SupportTicket::IN_PROGRESS,

        ]);

        return $ticket->fresh();
    }

    /**
     * Change Status
     */
    public function changeStatus(
        SupportTicket $ticket,
        string $status
    ): SupportTicket {

        $allowed = [

            SupportTicket::OPEN,

            SupportTicket::PENDING,

            SupportTicket::IN_PROGRESS,

            SupportTicket::RESOLVED,

            SupportTicket::CLOSED,

        ];

        if (!in_array($status, $allowed)) {
            throw new Exception(
                'Invalid ticket status.'
            );
        }

        $ticket->status = $status;

        if ($status === SupportTicket::RESOLVED) {

            $ticket->resolved_at = now();

        }

        if ($status === SupportTicket::CLOSED) {

            $ticket->closed_at = now();

        }

        $ticket->save();

        return $ticket->fresh();
    }

    /**
     * Change Priority
     */
    public function changePriority(
        SupportTicket $ticket,
        string $priority
    ): SupportTicket {

        $allowed = [

            'low',

            'medium',

            'high',

            'critical',

        ];

        if (!in_array($priority, $allowed)) {
            throw new Exception(
                'Invalid priority.'
            );
        }

        $ticket->update([
            'priority' => $priority,
        ]);

        return $ticket->fresh();
    }

    /**
     * Delete Ticket
     */
    public function delete(
        SupportTicket $ticket
    ): bool {

        return (bool) $ticket->delete();

    }

    /**
     * Ticket Statistics
     */
    public function statistics(): array
    {
        return [

            'total_tickets' =>
                SupportTicket::count(),

            'open' =>
                SupportTicket::whereStatus(
                    SupportTicket::OPEN
                )->count(),

            'pending' =>
                SupportTicket::whereStatus(
                    SupportTicket::PENDING
                )->count(),

            'in_progress' =>
                SupportTicket::whereStatus(
                    SupportTicket::IN_PROGRESS
                )->count(),

            'resolved' =>
                SupportTicket::whereStatus(
                    SupportTicket::RESOLVED
                )->count(),

            'closed' =>
                SupportTicket::whereStatus(
                    SupportTicket::CLOSED
                )->count(),

            'critical' =>
                SupportTicket::wherePriority(
                    'critical'
                )->count(),

        ];
    }
}
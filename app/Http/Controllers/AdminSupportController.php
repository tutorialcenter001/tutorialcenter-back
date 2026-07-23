<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Staff;
use App\Models\SupportTicket;
use App\Services\SupportService;

class AdminSupportController extends Controller
{
    protected $supportService;

    public function __construct(
        SupportService $supportService
    ) {
        $this->supportService = $supportService;
    }

    /**
     * List all tickets
     */
    public function index(Request $request)
    {
        $query = SupportTicket::with([
            'category',
            'requester',
            'assignedStaff'
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('support_category_id')) {
            $query->where(
                'support_category_id',
                $request->support_category_id
            );
        }

        if ($request->filled('assigned_staff_id')) {
            $query->where(
                'assigned_staff_id',
                $request->assigned_staff_id
            );
        }

        return response()->json([
            'data' => $query
                ->latest()
                ->paginate(20)
        ]);
    }

    /**
     * View ticket
     */
    public function show(
        SupportTicket $supportTicket
    ) {

        return response()->json([
            'data' => $supportTicket->load([
                'category',
                'requester',
                'assignedStaff',
                'messages.sender',
                'messages.attachments'
            ])
        ]);
    }

    /**
     * Assign ticket
     */
    public function assign(
        Request $request,
        SupportTicket $supportTicket
    ) {

        $validator = Validator::make($request->all(), [

            'staff_id' => [
                'required',
                'exists:staff,id'
            ],

        ]);

        if ($validator->fails()) {

            return response()->json([
                'errors' => $validator->errors(),
            ], 422);

        }

        $staff = Staff::findOrFail(
            $request->staff_id
        );

        $ticket = $this->supportService
            ->assign(
                $supportTicket,
                $staff
            );

        return response()->json([
            'message' => 'Ticket assigned successfully.',
            'data' => $ticket
        ]);
    }

    /**
     * Reply
     */
    public function reply(
        Request $request,
        SupportTicket $supportTicket
    ) {

        $validator = Validator::make($request->all(), [

            'message' => [
                'required',
                'string'
            ],

        ]);

        if ($validator->fails()) {

            return response()->json([
                'errors' => $validator->errors(),
            ], 422);

        }

        $reply = $this->supportService
            ->reply(
                $supportTicket,
                $request->user(),
                $request->message
            );

        return response()->json([
            'message' => 'Reply sent.',
            'data' => $reply
        ]);
    }

    /**
     * Change Status
     */
    public function status(
        Request $request,
        SupportTicket $supportTicket
    ) {

        $validator = Validator::make($request->all(), [

            'status' => [
                'required',
                'in:open,pending,in_progress,resolved,closed'
            ],

        ]);

        if ($validator->fails()) {

            return response()->json([
                'errors' => $validator->errors(),
            ], 422);

        }

        $ticket = $this->supportService
            ->changeStatus(
                $supportTicket,
                $request->status
            );

        return response()->json([
            'message' => 'Status updated.',
            'data' => $ticket
        ]);
    }

    /**
     * Change Priority
     */
    public function priority(
        Request $request,
        SupportTicket $supportTicket
    ) {

        $validator = Validator::make($request->all(), [

            'priority' => [
                'required',
                'in:low,medium,high,critical'
            ],

        ]);

        if ($validator->fails()) {

            return response()->json([
                'errors' => $validator->errors(),
            ], 422);

        }

        $ticket = $this->supportService
            ->changePriority(
                $supportTicket,
                $request->priority
            );

        return response()->json([
            'message' => 'Priority updated.',
            'data' => $ticket
        ]);
    }

    /**
     * Dashboard Statistics
     */
    public function analytics()
    {
        return response()->json([
            'data' => $this->supportService
                ->statistics()
        ]);
    }

    /**
     * Delete Ticket
     */
    public function destroy(
        SupportTicket $supportTicket
    ) {

        $this->supportService
            ->delete($supportTicket);

        return response()->json([
            'message' => 'Ticket deleted successfully.'
        ]);
    }
}
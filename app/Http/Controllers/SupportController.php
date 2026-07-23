<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\SupportTicket;
use App\Services\SupportService;

class SupportController extends Controller
{
    protected $supportService;

    public function __construct(
        SupportService $supportService
    ) {
        $this->supportService = $supportService;
    }

    /**
     * Create Ticket
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'support_category_id' => [
                'required',
                'exists:support_categories,id'
            ],

            'subject' => [
                'required',
                'string',
                'max:255'
            ],

            'description' => [
                'required',
                'string'
            ],

            'priority' => [
                'nullable',
                'in:low,medium,high,critical'
            ],

        ]);

        if ($validator->fails()) {

            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);

        }

        try {

            $ticket = $this->supportService
                ->createTicket(
                    $request->user(),
                    $request->all()
                );

            return response()->json([
                'message' => 'Support ticket created successfully.',
                'data' => $ticket,
            ], 201);

        } catch (Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * My Tickets
     */
    public function index(Request $request)
    {
        $tickets = $request
            ->user()
            ->supportTickets()
            ->with([
                'category',
                'assignedStaff'
            ])
            ->latest()
            ->paginate(20);

        return response()->json($tickets);
    }

    /**
     * View Ticket
     */
    public function show(
        SupportTicket $supportTicket,
        Request $request
    ) {

        if (

            $supportTicket->requester_id !=
            $request->user()->id ||

            $supportTicket->requester_type !=
            get_class($request->user())

        ) {
            abort(403);
        }

        return response()->json([

            'data' => $supportTicket->load([

                'category',

                'assignedStaff',

                'messages.sender',

                'messages.attachments',

            ])

        ]);
    }

    /**
     * Reply
     */
    public function reply(
        SupportTicket $supportTicket,
        Request $request
    ) {

        if (

            $supportTicket->requester_id !=
            $request->user()->id ||

            $supportTicket->requester_type !=
            get_class($request->user())

        ) {
            abort(403);
        }

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

            'message' => 'Reply sent successfully.',

            'data' => $reply,

        ]);
    }

    /**
     * Close Ticket
     */
    public function close(
        SupportTicket $supportTicket,
        Request $request
    ) {

        if (

            $supportTicket->requester_id !=
            $request->user()->id ||

            $supportTicket->requester_type !=
            get_class($request->user())

        ) {
            abort(403);
        }

        $ticket = $this->supportService
            ->close($supportTicket);

        return response()->json([

            'message' => 'Ticket closed successfully.',

            'data' => $ticket,

        ]);
    }

    /**
     * Reopen Ticket
     */
    public function reopen(
        SupportTicket $supportTicket,
        Request $request
    ) {

        if (

            $supportTicket->requester_id !=
            $request->user()->id ||

            $supportTicket->requester_type !=
            get_class($request->user())

        ) {
            abort(403);
        }

        $ticket = $this->supportService
            ->reopen($supportTicket);

        return response()->json([

            'message' => 'Ticket reopened successfully.',

            'data' => $ticket,

        ]);
    }

    /**
     * Delete Ticket
     */
    public function destroy(
        SupportTicket $supportTicket,
        Request $request
    ) {
        if (

            $supportTicket->requester_id !=
            $request->user()->id ||

            $supportTicket->requester_type !=
            get_class($request->user())

        ) {
            abort(403);
        }

        $this->supportService
            ->delete($supportTicket);

        return response()->json([

            'message' => 'Ticket deleted successfully.'

        ]);
    }
}
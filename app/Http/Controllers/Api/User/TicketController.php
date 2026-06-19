<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Support\AdminValidation as V;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tickets = $request->user()->tickets()->latest()->paginate(15);

        return $this->success([
            'items' => collect($tickets->items())->map(fn ($t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
                'type' => $t->type,
                'email' => $t->email,
                'status' => $t->status->value,
                'created_at' => $t->created_at->format('d M Y'),
            ]),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'email' => V::emailRules(),
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', V::maxRule('notes')],
        ]);

        $ticket = Ticket::create([
            'ticket_number' => 'TKT-'.strtoupper(Str::random(8)),
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'type' => $data['type'],
            'email' => $data['email'],
            'status' => TicketStatus::Open,
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => get_class($request->user()),
            'sender_id' => $request->user()->id,
            'message' => $data['description'],
        ]);

        return $this->success([
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'status' => $ticket->status->value,
        ], 'Support ticket submitted.', 201);
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        abort_if($ticket->user_id !== $request->user()->id, 403);

        $ticket->load('messages');

        return $this->success([
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'type' => $ticket->type,
            'email' => $ticket->email,
            'status' => $ticket->status->value,
            'messages' => $ticket->messages->map(fn ($m) => [
                'message' => $m->message,
                'created_at' => $m->created_at->format('d M Y H:i'),
            ]),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::with(['user:id,name,email', 'assignee:id,name'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($tickets);
    }

    public function show(Ticket $ticket): JsonResponse
    {
        return $this->success($ticket->load(['user', 'messages.sender', 'assignee']));
    }

    public function reply(Request $request, Ticket $ticket): JsonResponse
    {
        $request->validate(['message' => ['required', 'string']]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => get_class($request->user()),
            'sender_id' => $request->user()->id,
            'message' => $request->message,
        ]);

        if ($ticket->status === TicketStatus::Open) {
            $ticket->update(['status' => TicketStatus::InProgress]);
        }

        return $this->success($message, 'Reply sent.');
    }

    public function close(Ticket $ticket): JsonResponse
    {
        $ticket->update(['status' => TicketStatus::Closed]);

        return $this->success($ticket->fresh(), 'Ticket closed.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateStatusRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Ticket::query();

        if ($user->hasRole('Admin')) {
        } elseif ($user->hasRole('Staff')) {
            $query->where('assigned_to', $user->id);
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        return TicketResource::collection($query->latest()->paginate(15));
    }

    public function store(StoreTicketRequest $request)
    {
        $ticket = Ticket::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'status' => 'Open',
        ]);

        return new TicketResource($ticket);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        $allowed = $user->hasRole('Admin')
            || ($user->hasRole('Staff') && $ticket->assigned_to === $user->id)
            || $ticket->user_id === $user->id;

        abort_unless($allowed, 403);

        return new TicketResource($ticket);
    }

    public function updateStatus(UpdateStatusRequest $request, Ticket $ticket)
    {
        $ticket->update(['status' => $request->status]);

        return new TicketResource($ticket);
    }

    public function assign(AssignTicketRequest $request, Ticket $ticket)
    {
        $ticket->update(['assigned_to' => $request->assigned_to]);

        return new TicketResource($ticket);
    }
}
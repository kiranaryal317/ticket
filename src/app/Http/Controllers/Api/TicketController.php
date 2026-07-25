<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateStatusRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketCreated;
use App\Notifications\TicketStatusChanged;
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

        // notify every Admin (no one's assigned yet at creation time)
        User::role('Admin')->get()->each(
            fn ($admin) => $admin->notify(new TicketCreated($ticket))
        );

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

        $ticket->creator->notify(new TicketStatusChanged($ticket));

        return new TicketResource($ticket);
    }

    public function assign(AssignTicketRequest $request, Ticket $ticket)
    {
        $staff = User::findOrFail($request->assigned_to);

        abort_unless($staff->hasRole('Staff'), 422, 'User must have the Staff role.');

        $ticket->update(['assigned_to' => $staff->id]);

        $staff->notify(new TicketAssigned($ticket));

        return new TicketResource($ticket);
    }
}
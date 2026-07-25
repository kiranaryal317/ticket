<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Ticket;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        $this->authorizeTicketAccess($request, $ticket);

        return CommentResource::collection($ticket->comments);
    }

    public function store(StoreCommentRequest $request, Ticket $ticket)
    {
        $this->authorizeTicketAccess($request, $ticket);

        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        return new CommentResource($comment);
    }

    private function authorizeTicketAccess(Request $request, Ticket $ticket): void
    {
        $user = $request->user();

        $allowed = $user->hasRole('Admin')
            || ($user->hasRole('Staff') && $ticket->assigned_to === $user->id)
            || $ticket->user_id === $user->id;

        abort_unless($allowed, 403);
    }
}
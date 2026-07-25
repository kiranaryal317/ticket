<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Ticket;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class CommentController extends Controller
{
    #[OA\Get(
        path: '/api/tickets/{ticket}/comments',
        operationId: 'getTicketComments',
        description: 'Returns list of comments for the ticket if authorized.',
        summary: 'List comments for a ticket',
        security: [['bearerAuth' => []]],
        tags: ['Comments'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - Access Denied'),
        ]
    )]
    public function index(Request $request, Ticket $ticket)
    {
        $this->authorizeTicketAccess($request, $ticket);

        return CommentResource::collection($ticket->comments);
    }

    #[OA\Post(
        path: '/api/tickets/{ticket}/comments',
        operationId: 'createTicketComment',
        description: 'Adds a comment to the specified ticket if authorized.',
        summary: 'Add a comment to a ticket',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['body'],
                properties: [
                    new OA\Property(property: 'body', type: 'string', example: 'I tried restarting, but the issue persists.'),
                ]
            )
        ),
        tags: ['Comments'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Comment created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - Access Denied'),
            new OA\Response(response: 422, description: 'Validation Error'),
        ]
    )]
    public function store(StoreCommentRequest $request, Ticket $ticket)
    {
        $this->authorizeTicketAccess($request, $ticket);

        DB::beginTransaction();
        try {
            $comment = $ticket->comments()->create([
                'user_id' => $request->user()->id,
                'body' => $request->body,
            ]);

            DB::commit();

            return new CommentResource($comment);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
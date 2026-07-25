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
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class TicketController extends Controller
{
    #[OA\Get(
        path: '/api/tickets',
        operationId: 'getTicketsList',
        description: 'Returns paginated list of tickets based on role (Users see own, Staff sees assigned, Admin sees all).',
        summary: 'List tickets',
        security: [['bearerAuth' => []]],
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(
                name: 'status',
                description: 'Filter by status (Open, In Progress, Resolved)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'priority',
                description: 'Filter by priority (Low, Medium, High)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/tickets',
        operationId: 'createTicket',
        description: 'Creates a new ticket and sends notifications to Admins.',
        summary: 'Create a new ticket',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['subject', 'description', 'priority'],
                properties: [
                    new OA\Property(property: 'subject', type: 'string', example: 'System crash on login'),
                    new OA\Property(property: 'description', type: 'string', example: 'App throws error 500 when logging in'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['Low', 'Medium', 'High'], example: 'High'),
                ]
            )
        ),
        tags: ['Tickets'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Ticket created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation Error'),
        ]
    )]
    public function store(StoreTicketRequest $request)
    {
        DB::beginTransaction();
        try {
            $ticket = Ticket::create([
                ...$request->validated(),
                'user_id' => $request->user()->id,
                'status' => 'Open',
            ]);

            // notify every Admin (no one's assigned yet at creation time)
            User::role('Admin')->get()->each(
                fn ($admin) => $admin->notify(new TicketCreated($ticket))
            );

            DB::commit();

            return new TicketResource($ticket);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    #[OA\Get(
        path: '/api/tickets/{ticket}',
        operationId: 'getTicketById',
        description: 'Returns details of specified ticket if user has permission.',
        summary: 'Get single ticket details',
        security: [['bearerAuth' => []]],
        tags: ['Tickets'],
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
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - Access Denied'),
            new OA\Response(response: 404, description: 'Ticket Not Found'),
        ]
    )]
    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        $allowed = $user->hasRole('Admin')
            || ($user->hasRole('Staff') && $ticket->assigned_to === $user->id)
            || $ticket->user_id === $user->id;

        abort_unless($allowed, 403);

        return new TicketResource($ticket);
    }

    #[OA\Patch(
        path: '/api/tickets/{ticket}/status',
        operationId: 'updateTicketStatus',
        description: 'Updates status of ticket and notifies creator.',
        summary: 'Update ticket status (Staff or Admin only)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['Open', 'In Progress', 'Resolved'], example: 'In Progress'),
                ]
            )
        ),
        tags: ['Tickets'],
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
                description: 'Status updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - Requires Staff or Admin role'),
            new OA\Response(response: 422, description: 'Validation Error'),
        ]
    )]
    public function updateStatus(UpdateStatusRequest $request, Ticket $ticket)
    {
        DB::beginTransaction();
        try {
            $ticket->update(['status' => $request->status]);

            $ticket->creator->notify(new TicketStatusChanged($ticket));

            DB::commit();

            return new TicketResource($ticket);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    #[OA\Patch(
        path: '/api/tickets/{ticket}/assign',
        operationId: 'assignTicket',
        description: 'Assigns ticket to a staff member and sends notification.',
        summary: 'Assign ticket to staff (Admin only)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['assigned_to'],
                properties: [
                    new OA\Property(property: 'assigned_to', type: 'integer', example: 2),
                ]
            )
        ),
        tags: ['Tickets'],
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
                description: 'Ticket assigned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - Requires Admin role'),
            new OA\Response(response: 422, description: 'Validation Error - Target user must be Staff'),
        ]
    )]
    public function assign(AssignTicketRequest $request, Ticket $ticket)
    {
        DB::beginTransaction();
        try {
            $staff = User::findOrFail($request->assigned_to);

            abort_unless($staff->hasRole('Staff'), 422, 'User must have the Staff role.');

            $ticket->update(['assigned_to' => $staff->id]);

            $staff->notify(new TicketAssigned($ticket));

            DB::commit();

            return new TicketResource($ticket);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
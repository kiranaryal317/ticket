<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'creator' => $this->creator->only(['id', 'name', 'email']),
            'assignee' => $this->assignee ? $this->assignee->only(['id', 'name', 'email']) : null,
            'created_at' => $this->created_at,
        ];
    }
}
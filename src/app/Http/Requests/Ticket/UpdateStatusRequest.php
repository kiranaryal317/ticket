<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{

    public function authorize(): bool { 
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:Open,In Progress,Resolved'],
        ];
    }
}

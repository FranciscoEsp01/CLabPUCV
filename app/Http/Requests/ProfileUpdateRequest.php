<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $emailRules = [
            'required',
            'string',
            'lowercase',
            'email',
            'max:255',
            Rule::unique(User::class)->ignore($user->id),
        ];

        // Non-admin users must maintain their @mail.pucv.cl institutional domain
        if (!$user->isAdmin()) {
            $emailRules[] = 'ends_with:@mail.pucv.cl';
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'email.ends_with' => 'El correo electrónico debe pertenecer al dominio institucional (@mail.pucv.cl).',
        ];
    }
}

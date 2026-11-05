<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** API login: email + password for a token. Attempts are limited by the `api-login` rate limiter. */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * The user for these credentials. Same message for a wrong password and an inactive account, like the web login.
     *
     * @throws ValidationException
     */
    public function authenticatedUser(): User
    {
        $user = User::where('email', $this->validated('email'))->first();

        if (! $user || ! $user->is_active || ! Hash::check($this->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        return $user;
    }
}

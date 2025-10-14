<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// ============================================
// REQUEST: LoginRequest
// ============================================
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Tout le monde peut tenter de se connecter
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'adresse email est obligatoire',
            'email.email' => 'L\'adresse email n\'est pas valide',
            'email.max' => 'L\'adresse email ne peut dépasser 255 caractères',
            'password.required' => 'Le mot de passe est obligatoire',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'device_name.max' => 'Le nom de l\'appareil ne peut dépasser 255 caractères',
        ];
    }

    /**
     * Obtenir les credentials pour l'authentification
     */
    public function credentials(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }

    /**
     * Throttling par email pour éviter le brute force
     */
    public function throttleKey(): string
    {
        return strtolower($this->email) . '|' . $this->ip();
    }
}

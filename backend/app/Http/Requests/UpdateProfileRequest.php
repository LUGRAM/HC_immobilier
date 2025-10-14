<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

// ============================================
// REQUEST: UpdateProfileRequest
// ============================================
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'utilisateur ne peut modifier que son propre profil
        return $this->user() && $this->user()->id === (int) $this->route('user');
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'phone' => ['sometimes', 'string', 'max:20', 'unique:users,phone,' . $userId],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'current_password' => ['required_with:new_password', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.max' => 'Le prénom ne peut dépasser 255 caractères',
            'last_name.max' => 'Le nom ne peut dépasser 255 caractères',
            'email.email' => 'L\'adresse email n\'est pas valide',
            'email.unique' => 'Cette adresse email est déjà utilisée',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé',
            'profile_photo.image' => 'Le fichier doit être une image',
            'profile_photo.mimes' => 'L\'image doit être au format jpeg, jpg, png ou webp',
            'profile_photo.max' => 'L\'image ne peut dépasser 2 Mo',
            'address.max' => 'L\'adresse ne peut dépasser 500 caractères',
            'city.max' => 'La ville ne peut dépasser 100 caractères',
            'country.max' => 'Le pays ne peut dépasser 100 caractères',
            'current_password.required_with' => 'Le mot de passe actuel est requis pour changer de mot de passe',
            'new_password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères',
            'new_password.confirmed' => 'La confirmation du mot de passe ne correspond pas',
            'new_password.different' => 'Le nouveau mot de passe doit être différent de l\'ancien',
        ];
    }

    /**
     * Validation additionnelle : vérifier le mot de passe actuel
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->filled('current_password') && $this->filled('new_password')) {
                if (!Hash::check($this->current_password, $this->user()->password)) {
                    $validator->errors()->add(
                        'current_password',
                        'Le mot de passe actuel est incorrect'
                    );
                }
            }
        });
    }
}
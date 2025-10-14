<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Storage, Validator};
use Illuminate\Validation\Rules\Password;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    /**
     * Mettre à jour le profil de l'utilisateur
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
            'phone' => 'sometimes|string|unique:users,phone,' . $request->user()->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            
            $user->update($request->only([
                'first_name',
                'last_name',
                'email',
                'phone',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => new UserResource($user->fresh()),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Changer le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Vérifier le mot de passe actuel
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe actuel est incorrect',
            ], 400);
        }

        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de mot de passe',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload photo de profil
     */
    public function uploadPhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();

            // Supprimer l'ancienne photo si elle existe
            if ($user->profile_photo) {
                Storage::delete($user->profile_photo);
            }

            // Upload la nouvelle photo
            $path = $request->file('photo')->store('profile-photos', 'public');

            $user->update([
                'profile_photo' => $path,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo de profil mise à jour avec succès',
                'data' => [
                    'photo_url' => Storage::url($path),
                    'user' => new UserResource($user->fresh()),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload de la photo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer le compte utilisateur
     */
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'confirmation' => 'required|in:DELETE',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect',
            ], 400);
        }

        try {
            // Supprimer la photo de profil
            if ($user->profile_photo) {
                Storage::delete($user->profile_photo);
            }

            // Révoquer tous les tokens
            $user->tokens()->delete();

            // Soft delete du compte
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Compte supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du compte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
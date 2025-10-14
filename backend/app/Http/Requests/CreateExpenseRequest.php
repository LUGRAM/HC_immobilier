<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// ============================================
// REQUEST: CreateExpenseRequest
// ============================================
class CreateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Tous les utilisateurs authentifiés peuvent créer des dépenses
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'in:alimentation,transport,loisirs,santé,éducation,logement,autres'],
            'description' => ['required', 'string', 'min:3', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'La catégorie est obligatoire',
            'category.in' => 'Catégorie invalide. Choisissez parmi : alimentation, transport, loisirs, santé, éducation, logement, autres',
            'description.required' => 'La description est obligatoire',
            'description.min' => 'La description doit contenir au moins 3 caractères',
            'description.max' => 'La description ne peut dépasser 255 caractères',
            'amount.required' => 'Le montant est obligatoire',
            'amount.numeric' => 'Le montant doit être un nombre',
            'amount.min' => 'Le montant doit être supérieur à 0',
            'amount.max' => 'Le montant est trop élevé',
            'expense_date.required' => 'La date de dépense est obligatoire',
            'expense_date.date' => 'Format de date invalide',
            'expense_date.before_or_equal' => 'La date ne peut pas être dans le futur',
            'notes.max' => 'Les notes ne peuvent dépasser 1000 caractères',
        ];
    }

    /**
     * Préparer les données pour validation (convertir les virgules en points)
     */
    protected function prepareForValidation()
    {
        if ($this->has('amount')) {
            $this->merge([
                'amount' => str_replace(',', '.', $this->amount),
            ]);
        }
    }
}
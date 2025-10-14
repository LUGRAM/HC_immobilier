<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


// ============================================
// REQUEST: CreateInvoiceRequest
// ============================================
class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seuls les bailleurs et admins peuvent créer des factures
        return $this->user() && ($this->user()->isLandlord() || $this->user()->isAdmin());
    }

    public function rules(): array
    {
        return [
            'lease_id' => ['required', 'integer', 'exists:leases,id'],
            'invoice_type' => ['required', 'string', 'in:rent,water,electricity,maintenance,other'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'due_date' => ['required', 'date', 'after:today'],
            'description' => ['nullable', 'string', 'max:500'],
            'invoice_period_start' => ['nullable', 'date', 'before:invoice_period_end'],
            'invoice_period_end' => ['nullable', 'date', 'after:invoice_period_start'],
        ];
    }

    public function messages(): array
    {
        return [
            'lease_id.required' => 'Le contrat de location est obligatoire',
            'lease_id.exists' => 'Le contrat sélectionné n\'existe pas',
            'invoice_type.required' => 'Le type de facture est obligatoire',
            'invoice_type.in' => 'Type de facture invalide (rent, water, electricity, maintenance, other)',
            'amount.required' => 'Le montant est obligatoire',
            'amount.numeric' => 'Le montant doit être un nombre',
            'amount.min' => 'Le montant doit être supérieur à 0',
            'amount.max' => 'Le montant est trop élevé',
            'due_date.required' => 'La date d\'échéance est obligatoire',
            'due_date.after' => 'La date d\'échéance doit être ultérieure à aujourd\'hui',
            'description.max' => 'La description ne peut dépasser 500 caractères',
            'invoice_period_start.before' => 'La date de début doit être avant la date de fin',
            'invoice_period_end.after' => 'La date de fin doit être après la date de début',
        ];
    }

    /**
     * Validation additionnelle
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérifier que le contrat est actif
            if ($this->lease_id) {
                $lease = \App\Models\Lease::find($this->lease_id);
                if ($lease && $lease->status !== 'active') {
                    $validator->errors()->add(
                        'lease_id',
                        'Le contrat doit être actif pour émettre une facture'
                    );
                }
            }

            // Si c'est une facture de loyer, vérifier qu'elle n'existe pas déjà pour la période
            if ($this->invoice_type === 'rent' && $this->invoice_period_start && $this->invoice_period_end) {
                $existingInvoice = \App\Models\Invoice::where('lease_id', $this->lease_id)
                    ->where('invoice_type', 'rent')
                    ->where('invoice_period_start', $this->invoice_period_start)
                    ->where('invoice_period_end', $this->invoice_period_end)
                    ->exists();

                if ($existingInvoice) {
                    $validator->errors()->add(
                        'invoice_period_start',
                        'Une facture de loyer existe déjà pour cette période'
                    );
                }
            }
        });
    }

    /**
     * Préparer les données pour validation
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

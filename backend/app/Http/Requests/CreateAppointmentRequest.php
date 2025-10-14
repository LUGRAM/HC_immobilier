<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// ============================================
// REQUEST: CreateAppointmentRequest
// ============================================
class CreateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seuls les clients authentifiés peuvent créer des rendez-vous
        return $this->user() && $this->user()->isClient();
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'preferred_date' => ['required', 'date', 'after:now'],
            'preferred_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.required' => 'Le bien immobilier est obligatoire',
            'property_id.exists' => 'Le bien sélectionné n\'existe pas',
            'preferred_date.required' => 'La date de visite est obligatoire',
            'preferred_date.after' => 'La date doit être ultérieure à aujourd\'hui',
            'preferred_time.required' => 'L\'heure de visite est obligatoire',
            'preferred_time.date_format' => 'Format d\'heure invalide (HH:mm attendu)',
            'notes.max' => 'Les notes ne peuvent dépasser 500 caractères',
        ];
    }

    /**
     * Validation additionnelle : vérifier que l'heure est dans les créneaux disponibles
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $date = $this->input('preferred_date');
            $time = $this->input('preferred_time');
            
            // Vérifier que c'est un jour ouvrable (lundi-samedi)
            $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;
            if ($dayOfWeek === 0) { // Dimanche
                $validator->errors()->add(
                    'preferred_date', 
                    'Les visites ne sont pas possibles le dimanche'
                );
            }

            // Vérifier les heures ouvrables (8h-18h)
            $hour = (int) substr($time, 0, 2);
            if ($hour < 8 || $hour >= 18) {
                $validator->errors()->add(
                    'preferred_time',
                    'Les visites sont possibles uniquement entre 8h00 et 18h00'
                );
            }
        });
    }
}
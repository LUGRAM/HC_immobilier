<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Enregistrer une activité
     *
     * @param string $action
     * @param User|null $user
     * @param mixed $model
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return self
     */
    public static function logActivity(
        string $action,
        ?User $user = null,
        mixed $model =null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        // Déterminer l'ID utilisateur en utilisant getKey() au lieu de ->id
        $userId = null;
        
        if ($user !== null) {
            $userId = $user->getKey(); // ✅ Utilise getKey()
        } else {
            /** @var User|null $currentUser */
            $currentUser = Auth::user();
            if ($currentUser !== null) {
                $userId = $currentUser->getKey(); // ✅ Utilise getKey()
            }
        }

        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $model !== null ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Scope pour filtrer par utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour filtrer par action
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope pour filtrer par modèle
     */
    public function scopeForModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope pour les activités récentes
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Notifications\MaintenanceRequestCreated;
use App\Notifications\MaintenanceRequestUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaintenanceRequestController extends Controller
{
    /**
     * Liste des demandes de maintenance du locataire
     */
    public function index(Request $request)
    {
        $query = MaintenanceRequest::with(['property', 'assignedTo'])
            ->where('tenant_id', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $maintenanceRequests = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $maintenanceRequests,
        ]);
    }

    /**
     * Créer une demande de maintenance
     */
    public function store(Request $request)
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:plumbing,electrical,hvac,appliance,structural,security,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120', // 5MB max
        ]);

        // Vérifier que l'utilisateur est locataire de cette propriété
        $property = Property::findOrFail($request->property_id);
        
        $hasActiveLease = $property->leases()
            ->where('tenant_id', $request->user()->id)
            ->where('status', 'active')
            ->exists();

        if (!$hasActiveLease) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas locataire de cette propriété',
            ], 403);
        }

        // Upload des images
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('maintenance', 'public');
                $imagePaths[] = $path;
            }
        }

        $maintenanceRequest = MaintenanceRequest::create([
            'property_id' => $request->property_id,
            'tenant_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'priority' => $request->priority,
            'status' => 'pending',
            'images' => $imagePaths,
        ]);

        // Notifier le bailleur
        $property->landlord->notify(new MaintenanceRequestCreated($maintenanceRequest));

        return response()->json([
            'success' => true,
            'message' => 'Demande de maintenance créée avec succès',
            'data' => $maintenanceRequest->load(['property', 'tenant']),
        ], 201);
    }

    /**
     * Détails d'une demande
     */
    public function show($id, Request $request)
    {
        $maintenanceRequest = MaintenanceRequest::with(['property', 'tenant', 'assignedTo'])
            ->where('tenant_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $maintenanceRequest,
        ]);
    }

    /**
     * Annuler une demande (locataire seulement)
     */
    public function cancel($id, Request $request)
    {
        $maintenanceRequest = MaintenanceRequest::where('tenant_id', $request->user()->id)
            ->findOrFail($id);

        if (!$maintenanceRequest->canBeUpdated()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette demande ne peut plus être annulée',
            ], 400);
        }

        $maintenanceRequest->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Demande annulée avec succès',
        ]);
    }

    /**
     * Liste des demandes pour le bailleur
     */
    public function landlordIndex(Request $request)
    {
        $query = MaintenanceRequest::with(['property', 'tenant', 'assignedTo'])
            ->whereHas('property', function($q) use ($request) {
                $q->where('landlord_id', $request->user()->id);
            });

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        $maintenanceRequests = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $maintenanceRequests,
        ]);
    }

    /**
     * Mettre à jour une demande (bailleur seulement)
     */
    public function update($id, Request $request)
    {
        $request->validate([
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'scheduled_date' => 'nullable|date',
            'resolution_notes' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $maintenanceRequest = MaintenanceRequest::whereHas('property', function($q) use ($request) {
            $q->where('landlord_id', $request->user()->id);
        })->findOrFail($id);

        $oldStatus = $maintenanceRequest->status;

        $maintenanceRequest->update($request->only([
            'status',
            'priority',
            'scheduled_date',
            'resolution_notes',
            'cost',
            'assigned_to',
        ]));

        if ($request->status === 'completed' && !$maintenanceRequest->completed_date) {
            $maintenanceRequest->update(['completed_date' => now()]);
        }

        // Notifier le locataire si le statut a changé
        if ($oldStatus !== $maintenanceRequest->status) {
            $maintenanceRequest->tenant->notify(
                new MaintenanceRequestUpdated($maintenanceRequest, 'status_changed')
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Demande mise à jour avec succès',
            'data' => $maintenanceRequest->load(['property', 'tenant', 'assignedTo']),
        ]);
    }

    /**
     * Statistiques des demandes de maintenance
     */
    public function stats(Request $request)
    {
        $userId = $request->user()->id;
        $isLandlord = $request->user()->role === 'landlord';

        if ($isLandlord) {
            $query = MaintenanceRequest::whereHas('property', function($q) use ($userId) {
                $q->where('landlord_id', $userId);
            });
        } else {
            $query = MaintenanceRequest::where('tenant_id', $userId);
        }

        $stats = [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'urgent' => (clone $query)->where('priority', 'urgent')->count(),
            'total_cost' => (clone $query)->where('status', 'completed')->sum('cost'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
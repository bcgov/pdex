<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\InstitutionRelationship;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionRelationshipController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of institution relationships.
     */
    public function index(Institution $institution, Request $request): Response|JsonResponse
    {
        $this->authorize('view', $institution);
        
        $query = $institution->allRelationships();

        // Get filter parameters
        $search = $request->get('search');
        $type = $request->get('type');
        $status = $request->get('status');
        $perPage = $request->get('per_page', 10);

        // Apply filters
        if ($search) {
            // This would need to join with institutions table to search by name
            // For now, let's keep it simple
        }

        if ($type) {
            $query->where('relationship_type', $type);
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', (bool) $status);
        }

        // Get paginated results with related institutions
        $relationships = $query->with(['institutionA', 'institutionB'])
                              ->orderBy('created_at', 'desc')
                              ->paginate($perPage)
                              ->withQueryString();

        // Transform relationships to include related institution info
        $relationships->getCollection()->transform(function ($relationship) use ($institution) {
            $relatedInstitution = $relationship->getOtherInstitution($institution->guid);
            $relationship->related_institution = $relatedInstitution;
            return $relationship;
        });

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'relationships' => $relationships,
                'institution' => $institution
            ]);
        }

        return Inertia::render('Admin::InstitutionRelationships/Index', [
            'institution' => $institution,
            'relationships' => $relationships,
            'filters' => $request->only(['search', 'type', 'status']),
            'relationshipTypes' => InstitutionRelationship::getRelationshipTypes(),
            'canManageInstitutions' => auth()->user()->canManageInstitutions(),
        ]);
    }

    /**
     * Show the form for creating a new institution relationship.
     */
    public function create(Institution $institution): Response
    {
        $this->authorize('update', $institution);

        // Get other institutions for selection
        $otherInstitutions = Institution::where('id', '!=', $institution->id)
                                       ->where('active_status', true)
                                       ->select(['id', 'guid', 'legal_operating_name', 'institution_type', 'dli', 'active_status'])
                                       ->orderBy('legal_operating_name')
                                       ->get();

        return Inertia::render('Admin::InstitutionRelationships/Create', [
            'institution' => $institution,
            'availableInstitutions' => $otherInstitutions,
        ]);
    }

    /**
     * Store a newly created institution relationship.
     */
    public function store(Institution $institution, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $institution);

        // Debug: Log the incoming request data
        \Log::info('Relationship creation request', [
            'institution_id' => $institution->id,
            'request_data' => $request->all()
        ]);

        $validated = $request->validate([
            'related_institution_guids' => 'required|array|min:1',
            'related_institution_guids.*' => 'required|exists:institutions,guid',
            'relationship_type' => 'required|string|in:' . implode(',', array_keys(InstitutionRelationship::getRelationshipTypes())),
            'relationship_reason' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'metadata' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        // Debug: Log validated data
        \Log::info('Validated data', $validated);

        $relationships = [];
        $updated = [];
        $created = [];

        // Include the primary institution in the list for full mesh relationships
        $allInstitutions = array_merge([$institution->guid], $validated['related_institution_guids']);
        
        // Create relationships between all pairs of institutions
        for ($i = 0; $i < count($allInstitutions); $i++) {
            for ($j = $i + 1; $j < count($allInstitutions); $j++) {
                $institutionA = $allInstitutions[$i];
                $institutionB = $allInstitutions[$j];
                
                try {
                                        // Check if a relationship of this type already exists
                    $existing = InstitutionRelationship::where(function($query) use ($institutionA, $institutionB) {
                        $query->where('institution_a_guid', $institutionA)
                              ->where('institution_b_guid', $institutionB);
                    })->orWhere(function($query) use ($institutionA, $institutionB) {
                        $query->where('institution_a_guid', $institutionB)
                              ->where('institution_b_guid', $institutionA);
                                        })->where('relationship_type', $validated['relationship_type'])
                                            ->first();

                    $relationship = InstitutionRelationship::createBidirectional(
                        $institutionA,
                        $institutionB,
                        $validated['relationship_type'],
                        !empty($validated['relationship_reason']) ? $validated['relationship_reason'] : 'Partnership established',
                        [
                            'description' => $validated['description'] ?? null,
                            'is_active' => $validated['is_active'] ?? true,
                            'effective_date' => $validated['effective_date'] ?? null,
                            'expiry_date' => $validated['expiry_date'] ?? null,
                            'metadata' => $validated['metadata'] ?? null,
                            'notes' => $validated['notes'] ?? null,
                        ]
                    );
                    
                    $relationships[] = $relationship;
                    
                    if ($existing) {
                        $updated[] = $relationship;
                        \Log::info('Relationship updated', [
                            'id' => $relationship->id,
                            'institution_a' => $institutionA,
                            'institution_b' => $institutionB
                        ]);
                    } else {
                        $created[] = $relationship;
                        \Log::info('Relationship created', [
                            'id' => $relationship->id,
                            'institution_a' => $institutionA,
                            'institution_b' => $institutionB
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    \Log::error('Failed to create relationship', [
                        'institution_a' => $institutionA,
                        'institution_b' => $institutionB,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }
        }

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Institution relationships processed successfully',
                'relationships' => collect($relationships)->load(['institutionA', 'institutionB']),
                'summary' => [
                    'total' => count($relationships),
                    'created' => count($created),
                    'updated' => count($updated)
                ]
            ], 201);
        }

        // Create informative success message
        $messages = [];
        if (count($created) > 0) {
            $messages[] = count($created) . ' new relationship' . (count($created) === 1 ? '' : 's') . ' created';
        }
        if (count($updated) > 0) {
            $messages[] = count($updated) . ' existing relationship' . (count($updated) === 1 ? '' : 's') . ' updated';
        }
        
        $successMessage = !empty($messages) ? implode(' and ', $messages) . '.' : 'No changes made.';

        return redirect()->route('admin.institutions.relationships.index', $institution)
            ->with('success', $successMessage);
    }

    /**
     * Display the specified institution relationship.
     */
    public function show(Institution $institution, InstitutionRelationship $relationship): Response|JsonResponse
    {
        $this->authorize('view', $institution);

        // Ensure the relationship belongs to the institution
        if ($relationship->institution_a_guid !== $institution->guid && 
            $relationship->institution_b_guid !== $institution->guid) {
            abort(404);
        }

        $relationship->load(['institutionA', 'institutionB']);

        if (request()->expectsJson()) {
            return response()->json($relationship);
        }

        return Inertia::render('Admin::InstitutionRelationships/Show', [
            'institution' => $institution,
            'relationship' => $relationship,
            'canManageInstitutions' => auth()->user()->canManageInstitutions(),
        ]);
    }

    /**
     * Show the form for editing the specified institution relationship.
     */
    public function edit(Institution $institution, InstitutionRelationship $relationship): Response
    {
        $this->authorize('update', $institution);

        // Ensure the relationship belongs to the institution
        if ($relationship->institution_a_guid !== $institution->guid && 
            $relationship->institution_b_guid !== $institution->guid) {
            abort(404);
        }

        $relationship->load(['institutionA', 'institutionB']);

        // Get other institutions for selection (excluding current institution and already related one)
        $relatedGuid = $relationship->institution_a_guid === $institution->guid 
            ? $relationship->institution_b_guid 
            : $relationship->institution_a_guid;
        
        $otherInstitutions = Institution::where('id', '!=', $institution->id)
                                       ->where('active_status', true)
                                       ->select(['id', 'guid', 'legal_operating_name'])
                                       ->orderBy('legal_operating_name')
                                       ->get();

        return Inertia::render('Admin::InstitutionRelationships/Edit', [
            'institution' => $institution,
            'relationship' => $relationship,
            'otherInstitutions' => $otherInstitutions,
            'relationshipTypes' => InstitutionRelationship::getRelationshipTypes(),
            'relationshipReasons' => InstitutionRelationship::getRelationshipReasons(),
        ]);
    }

    /**
     * Update the specified institution relationship.
     */
    public function update(Institution $institution, InstitutionRelationship $relationship, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $institution);

        // Ensure the relationship belongs to the institution
        if ($relationship->institution_a_guid !== $institution->guid && 
            $relationship->institution_b_guid !== $institution->guid) {
            abort(404);
        }

        $validated = $request->validate([
            'relationship_type' => 'sometimes|required|string|in:' . implode(',', array_keys(InstitutionRelationship::getRelationshipTypes())),
            'relationship_reason' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'metadata' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $relationship->update($validated);

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Institution relationship updated successfully',
                'relationship' => $relationship->fresh()->load(['institutionA', 'institutionB'])
            ]);
        }

        return redirect()->route('admin.institutions.relationships.show', [$institution, $relationship])
            ->with('success', 'Institution relationship updated successfully.');
    }

    /**
     * Remove the specified institution relationship.
     */
    public function destroy(Institution $institution, InstitutionRelationship $relationship): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $institution);

        // Ensure the relationship belongs to the institution
        if ($relationship->institution_a_guid !== $institution->guid && 
            $relationship->institution_b_guid !== $institution->guid) {
            abort(404);
        }

        $relationship->delete();

        // Return JSON for API requests
        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Institution relationship deleted successfully'
            ]);
        }

        return redirect()->route('admin.institutions.show', $institution)
            ->with('success', 'Institution relationship deleted successfully.');
    }

    /**
     * Toggle the active status of an institution relationship.
     */
    public function toggleStatus(Institution $institution, InstitutionRelationship $relationship): RedirectResponse
    {
        $this->authorize('update', $institution);

        // Ensure the relationship belongs to the institution
        if ($relationship->institution_a_guid !== $institution->guid && 
            $relationship->institution_b_guid !== $institution->guid) {
            abort(404);
        }

        $relationship->update([
            'is_active' => !$relationship->is_active
        ]);

        $status = $relationship->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
            ->with('success', "Institution relationship {$status} successfully.");
    }
}

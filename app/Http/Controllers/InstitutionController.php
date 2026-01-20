<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Apply auth middleware to all methods
        $this->middleware('auth');
    }
    /**
     * Display a listing of institutions.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $this->authorize('viewAny', Institution::class);
        
        $query = Institution::query();

        // Get filter parameters
        $search = $request->get('search');
        $type = $request->get('type');
        $activeStatus = $request->get('active_status');
        $perPage = $request->get('per_page', 10);

        // Apply filters
        if ($search) {
            $query->where('legal_operating_name', 'ilike', "%{$search}%");
        }

        if ($type) {
            $query->where('institution_type', $type);
        }

        if ($activeStatus !== null && $activeStatus !== '') {
            $query->where('active_status', (bool) $activeStatus);
        }

        // Get paginated results
        $institutions = $query->orderBy('legal_operating_name')
                            ->paginate($perPage)
                            ->withQueryString();

        // Return JSON for API requests (but not Inertia requests)
        if ($request->expectsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'institutions' => $institutions,
                'filters' => [
                    'types' => Institution::getInstitutionTypes(),
                ]
            ]);
        }

        // Get statistics for cards
        $stats = [
            'total' => Institution::count(),
            'active' => Institution::where('active_status', true)->count(),
            'by_type' => Institution::selectRaw('institution_type, count(*) as count')
                                  ->groupBy('institution_type')
                                  ->pluck('count', 'institution_type'),
        ];

        // Get filter options
        $filterOptions = [
            'types' => Institution::distinct()->pluck('institution_type')->filter()->sort()->values(),
        ];

        return Inertia::render('Admin::Institutions/Index', [
            'institutions' => $institutions,
            'stats' => $stats,
            'filters' => $request->only(['search', 'type', 'active_status']),
            'filterOptions' => $filterOptions,
            'canManageInstitutions' => auth()->user()->canManageInstitutions(),
        ]);
    }

    /**
     * Display the specified institution.
     */
    public function show(Institution $institution): Response|JsonResponse
    {
        $this->authorize('view', $institution);
        
        if (request()->expectsJson() && !request()->header('X-Inertia')) {
            return response()->json($institution);
        }

        // Get institution users (users with the same bceid_business_guid)
        $institutionUsers = collect();
        if ($institution->bceid_business_guid) {
            try {
                // First try direct comparison
                $institutionUsers = User::where('bceid_business_guid', $institution->bceid_business_guid)
                    ->with('roles')
                    ->get();
            } catch (\Exception $e) {
                // If that fails due to type mismatch, use CAST for compatibility
                $institutionUsers = User::whereRaw('CAST(bceid_business_guid AS TEXT) = ?', [$institution->bceid_business_guid])
                    ->with('roles')
                    ->get();
            }
            
            $institutionUsers = $institutionUsers->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'is_active' => $user->is_active,
                        'created_at' => $user->created_at,
                        'roles' => $user->roles->pluck('name')->toArray(),
                        'institution_role' => $user->roles->whereIn('name', ['Institution Admin', 'Institution User'])->first()?->name ?? null,
                        'has_institution_role' => $user->hasAnyRole(['Institution Admin', 'Institution User']),
                    ];
                });
        }

        return Inertia::render('Admin::Institutions/Show', [
            'institution' => $institution->load(['sites']),
            'relationships' => $institution->allRelationships()->with(['institutionA', 'institutionB'])->get(),
            'institutionUsers' => $institutionUsers,
            'canManageInstitutions' => auth()->user()->canManageInstitutions(),
        ]);
    }

    /**
     * Show the form for creating a new institution.
     */
    public function create(): Response
    {
        $this->authorize('create', Institution::class);

        return Inertia::render('Admin::Institutions/Create', [
            'institutionTypes' => Institution::getInstitutionTypes(),
        ]);
    }

    /**
     * Show the form for editing the specified institution.
     */
    public function edit(Institution $institution): Response
    {
        $this->authorize('update', $institution);

        return Inertia::render('Admin::Institutions/Edit', [
            'institution' => $institution,
            'institutionTypes' => Institution::getInstitutionTypes(),
        ]);
    }

    /**
     * Get institutions summary statistics.
     */
    public function stats(): JsonResponse
    {
        $this->authorize('viewStats', Institution::class);
        
        $stats = [
            'total' => Institution::count(),
            'active' => Institution::where('active_status', true)->count(),
            'inactive' => Institution::where('active_status', false)->count(),
            
            'by_type' => Institution::selectRaw('institution_type, count(*) as count')
                                   ->groupBy('institution_type')
                                   ->pluck('count', 'institution_type'),
        ];

        return response()->json($stats);
    }

    /**
     * Get active institutions for dropdowns/selects.
     */
    public function active(): JsonResponse
    {
        $this->authorize('viewAny', Institution::class);
        
        $institutions = Institution::where('active_status', true)
                                  ->select(['id', 'guid', 'legal_operating_name'])
                                  ->orderBy('legal_operating_name')
                                  ->get()
                                  ->map(function ($institution) {
                                      return [
                                          'id' => $institution->id,
                                          'guid' => $institution->guid,
                                          'name' => $institution->legal_operating_name,
                                          'legal_name' => $institution->legal_operating_name,
                                      ];
                                  });

        return response()->json($institutions);
    }

    /**
     * Search institutions by name.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('search', Institution::class);
        
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'limit' => 'sometimes|integer|min:1|max:50'
        ]);

        $query = $request->q;
        $limit = $request->get('limit', 10);

        $institutions = Institution::where('legal_operating_name', 'ILIKE', "%{$query}%")
                                  ->where('active_status', true)
                                  ->select(['id', 'guid', 'legal_operating_name'])
                                  ->limit($limit)
                                  ->get()
                                  ->map(function ($institution) {
                                      return [
                                          'id' => $institution->id,
                                          'guid' => $institution->guid,
                                          'name' => $institution->legal_operating_name,
                                          'legal_name' => $institution->legal_operating_name,
                                      ];
                                  });

        return response()->json($institutions);
    }

    /**
     * Store a newly created institution.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', Institution::class);

        $validated = $request->validate([
            'legal_operating_name' => 'required|string|max:255',
            'institution_type' => 'required|string|in:' . implode(',', Institution::getInstitutionTypes()),
            'dli' => 'nullable|string|max:20',
            'bceid_business_guid' => 'nullable|string|max:255',
            'active_status' => 'boolean',
        ]);

        $institution = Institution::create($validated);

        // Return JSON for API requests (but not Inertia requests)
        if ($request->expectsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Institution created successfully',
                'institution' => $institution
            ], 201);
        }

        return redirect()->route('admin.institutions.show', $institution)
            ->with('success', 'Institution created successfully.');
    }

    /**
     * Update the specified institution.
     */
    public function update(Request $request, Institution $institution): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $institution);

        $validated = $request->validate([
            'legal_operating_name' => 'sometimes|required|string|max:255',
            'institution_type' => 'sometimes|required|string|in:' . implode(',', Institution::getInstitutionTypes()),
            'dli' => 'nullable|string|max:20',
            'bceid_business_guid' => 'nullable|string|max:255',
            'active_status' => 'boolean',
        ]);

        $institution->update($validated);

        // Return JSON for API requests (but not Inertia requests)
        if ($request->expectsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Institution updated successfully',
                'institution' => $institution->fresh()
            ]);
        }

        return redirect()->route('admin.institutions.show', $institution)
            ->with('success', 'Institution updated successfully.');
    }

    /**
     * Toggle institution user's active status.
     */
    public function toggleUserStatus(Request $request, Institution $institution, User $user): RedirectResponse
    {
        $this->authorize('update', $institution);
        
        // Verify the user belongs to this institution
        if ($user->bceid_business_guid !== $institution->bceid_business_guid) {
            abort(403, 'User does not belong to this institution.');
        }

        $user->update(['is_active' => !$user->is_active]);

        return redirect()->route('admin.institutions.show', $institution)
            ->with('success', 'User status updated successfully.');
    }

    /**
     * Toggle institution user's role between Institution Admin and Institution User.
     */
    public function toggleUserRole(Request $request, Institution $institution, User $user): RedirectResponse
    {
        $this->authorize('update', $institution);
        
        // Verify the user belongs to this institution
        if ($user->bceid_business_guid !== $institution->bceid_business_guid) {
            abort(403, 'User does not belong to this institution.');
        }

        // Get the current institution role
        $currentRole = $user->roles->whereIn('name', [Role::INSTITUTION_ADMIN, Role::INSTITUTION_USER])->first();

        if ($currentRole) {
            // Toggle between Institution Admin and Institution User
            $newRoleName = $currentRole->name === Role::INSTITUTION_ADMIN ? Role::INSTITUTION_USER : Role::INSTITUTION_ADMIN;
            $newRole = Role::where('name', $newRoleName)->first();
            
            if ($newRole) {
                // Remove current institution role and add new one
                $user->roles()->detach($currentRole->id);
                $user->roles()->attach($newRole->id);
            }
        } else {
            // If user has no institution role, assign Institution User by default
            $defaultRole = Role::where('name', Role::INSTITUTION_USER)->first();
            if ($defaultRole) {
                $user->roles()->attach($defaultRole->id);
            }
        }

        return redirect()->route('admin.institutions.show', $institution)
            ->with('success', 'User role updated successfully.');
    }

    /**
     * Remove the specified institution.
     */
    public function destroy(Institution $institution): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $institution);

        $institution->delete();

        // Return JSON for API requests (but not Inertia requests)
        if (request()->expectsJson() && !request()->header('X-Inertia')) {
            return response()->json([
                'message' => 'Institution deleted successfully'
            ]);
        }

        return redirect()->route('admin.institutions.index')
            ->with('success', 'Institution deleted successfully.');
    }

    /**
     * Toggle the active status of an institution.
     */
    public function toggleStatus(Institution $institution): RedirectResponse
    {
        $this->authorize('update', $institution);

        $institution->update([
            'active_status' => !$institution->active_status
        ]);

        $status = $institution->active_status ? 'activated' : 'deactivated';
        
        return redirect()->back()
            ->with('success', "Institution {$status} successfully.");
    }
}

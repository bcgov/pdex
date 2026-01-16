<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\InstitutionSite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionSiteController extends Controller
{
    private const PHONE_REGEX = 'regex:/^(\+?1[-.\s]?)?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4}$/';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of institution sites.
     */
    public function index(Institution $institution, Request $request): Response|JsonResponse
    {
        $this->authorize('view', $institution);
        
        $query = $institution->sites();

        // Get filter parameters
        $search = $request->get('search');
        $status = $request->get('status');
        $perPage = $request->get('per_page', 10);

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('site_name', 'ilike', "%{$search}%")
                  ->orWhere('city', 'ilike', "%{$search}%")
                  ->orWhere('primary_contact_name', 'ilike', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('is_main_campus', (bool) $status);
        }

        // Get paginated results
        $sites = $query->orderBy('is_main_campus', 'desc')
                      ->orderBy('site_name')
                      ->paginate($perPage)
                      ->withQueryString();

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'sites' => $sites,
                'institution' => $institution
            ]);
        }

        return Inertia::render('Admin::InstitutionSites/Index', [
            'institution' => $institution,
            'sites' => $sites,
            'filters' => $request->only(['search', 'status']),
            'canManageInstitutions' => auth()->user()->canManageInstitutions(),
        ]);
    }

    /**
     * Show the form for creating a new institution site.
     */
    public function create(Institution $institution): Response
    {
        $this->authorize('update', $institution);

        return Inertia::render('Admin::InstitutionSites/Create', [
            'institution' => $institution,
            'economicRegions' => InstitutionSite::getEconomicRegions(),
            'regulatingBodies' => InstitutionSite::getRegulatingBodies(),
            'standingStatuses' => InstitutionSite::getStandingStatuses(),
        ]);
    }

    /**
     * Store a newly created institution site.
     */
    public function store(Institution $institution, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $institution);

        $validated = $request->validate([
            'operating_name' => 'required|string|max:255',
            'primary_phone' => [
                'required',
                'string',
                self::PHONE_REGEX,
                'max:20'
            ],
            'primary_email' => 'required|email|max:255',
            'website' => 'nullable|url|max:255',
            'regulating_body' => 'required|string|max:255',
            'other_regulating_body' => 'nullable|string|max:255',
            'established_date' => 'nullable|date',
            'info_sharing_agreement' => 'boolean',
            'contact_first_name' => 'required|string|max:100',
            'contact_last_name' => 'required|string|max:100',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => [
                'required',
                'string',
                self::PHONE_REGEX,
                'max:20'
            ],
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'province_state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => [
                'required',
                'string',
                'regex:/^[A-Za-z]\d[A-Za-z][\s\-]?\d[A-Za-z]\d$/',
                'max:10'
            ],
            'public' => 'boolean',
            'active_status' => 'boolean',
            'standing_status' => 'nullable|string|in:' . implode(',', InstitutionSite::getStandingStatuses()),
            'economic_region' => 'nullable|string|in:' . implode(',', InstitutionSite::getEconomicRegions()),
            'notes' => 'nullable|string',
        ], [
            'primary_phone.regex' => 'Primary phone must be a valid North American phone number (e.g., (555) 123-4567).',
            'contact_phone.regex' => 'Contact phone must be a valid North American phone number (e.g., (555) 123-4567).',
            'postal_code.regex' => 'Postal code must be a valid Canadian postal code (e.g., A1A 1A1).',
        ]);

        // Add institution GUID
        $validated['institution_guid'] = $institution->guid;

        $site = InstitutionSite::create($validated);

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Institution site created successfully',
                'site' => $site
            ], 201);
        }

        return redirect()->route('admin.institutions.sites.show', [$institution, $site])
            ->with('success', 'Institution site created successfully.');
    }

    /**
     * Display the specified institution site.
     */
    public function show(Institution $institution, InstitutionSite $site): Response|JsonResponse
    {
        $this->authorize('view', $institution);

        // Ensure the site belongs to the institution
        if ($site->institution_guid !== $institution->guid) {
            abort(404);
        }

        if (request()->expectsJson()) {
            return response()->json($site);
        }

        return Inertia::render('Admin::InstitutionSites/Show', [
            'institution' => $institution,
            'site' => $site,
            'canManageInstitutions' => auth()->user()->canManageInstitutions(),
        ]);
    }

    /**
     * Show the form for editing the specified institution site.
     */
    public function edit(Institution $institution, InstitutionSite $site): Response
    {
        $this->authorize('update', $institution);

        // Ensure the site belongs to the institution
        if ($site->institution_guid !== $institution->guid) {
            abort(404);
        }

        return Inertia::render('Admin::InstitutionSites/Edit', [
            'institution' => $institution,
            'site' => $site,
            'economicRegions' => InstitutionSite::getEconomicRegions(),
            'regulatingBodies' => InstitutionSite::getRegulatingBodies(),
            'standingStatuses' => InstitutionSite::getStandingStatuses(),
        ]);
    }

    /**
     * Update the specified institution site.
     */
    public function update(Institution $institution, InstitutionSite $site, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $institution);

        // Ensure the site belongs to the institution
        if ($site->institution_guid !== $institution->guid) {
            abort(404);
        }

        $validated = $request->validate([
            'operating_name' => 'sometimes|required|string|max:255',
            'primary_phone' => [
                'sometimes',
                'required',
                'string',
                self::PHONE_REGEX,
                'max:20'
            ],
            'primary_email' => 'sometimes|required|email|max:255',
            'website' => 'nullable|url|max:255',
            'regulating_body' => 'sometimes|required|string|max:255',
            'other_regulating_body' => 'nullable|string|max:255',
            'established_date' => 'nullable|date',
            'info_sharing_agreement' => 'boolean',
            'contact_first_name' => 'sometimes|required|string|max:100',
            'contact_last_name' => 'sometimes|required|string|max:100',
            'contact_email' => 'sometimes|required|email|max:255',
            'contact_phone' => [
                'sometimes',
                'required',
                'string',
                self::PHONE_REGEX,
                'max:20'
            ],
            'address_line_1' => 'sometimes|required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'sometimes|required|string|max:100',
            'province_state' => 'sometimes|required|string|max:100',
            'country' => 'sometimes|required|string|max:100',
            'postal_code' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[A-Za-z]\d[A-Za-z][\s\-]?\d[A-Za-z]\d$/',
                'max:10'
            ],
            'public' => 'boolean',
            'active_status' => 'boolean',
            'standing_status' => 'nullable|string|in:' . implode(',', InstitutionSite::getStandingStatuses()),
            'economic_region' => 'nullable|string|in:' . implode(',', InstitutionSite::getEconomicRegions()),
            'notes' => 'nullable|string',
        ], [
            'primary_phone.regex' => 'Primary phone must be a valid North American phone number (e.g., (555) 123-4567).',
            'contact_phone.regex' => 'Contact phone must be a valid North American phone number (e.g., (555) 123-4567).',
            'postal_code.regex' => 'Postal code must be a valid Canadian postal code (e.g., A1A 1A1).',
        ]);

        $site->update($validated);

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Institution site updated successfully',
                'site' => $site->fresh()
            ]);
        }

        return redirect()->route('admin.institutions.sites.show', [$institution, $site])
            ->with('success', 'Institution site updated successfully.');
    }

    /**
     * Remove the specified institution site.
     */
    public function destroy(Institution $institution, InstitutionSite $site): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $institution);

        // Ensure the site belongs to the institution
        if ($site->institution_guid !== $institution->guid) {
            abort(404);
        }

        $site->delete();

        // Return JSON for API requests
        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Institution site deleted successfully'
            ]);
        }

        return redirect()->route('admin.institutions.show', $institution)
            ->with('success', 'Institution site deleted successfully.');
    }

    /**
     * Toggle the active status of an institution site.
     */
    public function toggleStatus(Institution $institution, InstitutionSite $site): RedirectResponse
    {
        $this->authorize('update', $institution);

        // Ensure the site belongs to the institution
        if ($site->institution_guid !== $institution->guid) {
            abort(404);
        }

        $site->update([
            'active_status' => !$site->active_status
        ]);

        $status = $site->active_status ? 'activated' : 'deactivated';
        
        return redirect()->back()
            ->with('success', "Institution site {$status} successfully.");
    }
}

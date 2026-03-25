<?php

namespace Modules\Student\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class StudentLogoutController extends Controller
{
    /**
     * Log the user out and redirect to SSO logout URL.
     * This ensures Laravel session is properly terminated before redirecting to Keycloak logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        if ($user) {
            Log::info('Student user logging out', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // Get logout URLs from session
            $logoutUrl = Session::get('kc_logout_uri_' . $user->id);
        }

        // Perform Laravel logout
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flush();

        // Redirect to SSO logout URL if available, otherwise to login
        return redirect($logoutUrl ?? '/login');
    }
}

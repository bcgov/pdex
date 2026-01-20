<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use App\Models\User;
use App\Models\Institution;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Prepare the request for Inertia by setting the correct root URL
     * to prevent redirects to backend ALB domain when behind CloudFront
     */
    public function handle($request, $next)
    {
        // Force Laravel to use APP_URL for URL generation within Inertia
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        return parent::handle($request, $next);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $logoutUrl = null;
        $logoutBcscUrl = null;
        $user = $request->user();

        //if the user is logged in set $logoutUrl and $logoutBcscUrl
        if ($request->user()) {
            $logoutUrl = Session::get('kc_logout_uri_' . $request->user()->id);
            $logoutBcscUrl = Session::get('bcsc_logout_uri_' . $request->user()->id);
            $user = User::where('id', $request->user()->id)->with('roles')->first();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                // Include user information with roles
                'user' => $user,
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'logoutUrl' => $logoutUrl,
            'logoutBcscUrl' => $logoutBcscUrl,

        ];
    }
}

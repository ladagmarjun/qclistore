<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrderingIsEnabled
{
    public function __construct(private SettingService $settings) {}

    /**
     * Reject new orders while online ordering is switched off.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->settings->cartEnabled(), 403, __('Online ordering is currently unavailable.'));

        return $next($request);
    }
}

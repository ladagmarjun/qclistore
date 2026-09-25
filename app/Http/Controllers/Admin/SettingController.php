<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(private SettingService $settings) {}

    public function show(): Response
    {
        return Inertia::render('admin/settings', [
            'settings' => [
                'cart_enabled' => $this->settings->cartEnabled(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cart_enabled' => ['required', 'boolean'],
        ]);

        $this->settings->set('cart_enabled', $validated['cart_enabled'] ? 'true' : 'false');

        Inertia::flash('success', $validated['cart_enabled'] ? __('Ordering enabled.') : __('Ordering disabled.'));

        return to_route('admin.settings.show');
    }
}

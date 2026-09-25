<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private SettingService $settings) {}

    public function show(): JsonResponse
    {
        return response()->json($this->current());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_enabled' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('cart_enabled', $validated)) {
            $this->settings->set('cart_enabled', $validated['cart_enabled'] ? 'true' : 'false');
        }

        return response()->json($this->current());
    }

    /**
     * @return array<string, mixed>
     */
    private function current(): array
    {
        return [
            'cart_enabled' => $this->settings->cartEnabled(),
        ];
    }
}

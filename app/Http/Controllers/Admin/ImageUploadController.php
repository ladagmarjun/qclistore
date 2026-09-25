<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageUploadController extends Controller
{
    /**
     * Store an image on the public disk (under products/ or banners/) and return its URL, which the product and banner forms then save like any pasted link.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'folder' => ['sometimes', 'in:products,banners'],
        ], [
            'image.max' => __('Images must be 5 MB or smaller.'),
        ]);

        $path = $validated['image']->store($validated['folder'] ?? 'products', 'public');

        // Built from the current host rather than APP_URL so links work however the admin is reached.
        return response()->json(['url' => asset('storage/'.$path)], 201);
    }
}

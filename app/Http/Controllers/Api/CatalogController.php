<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Services\SettingService;
use App\Services\StorefrontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only storefront data that isn't tied to one product.
 */
class CatalogController extends Controller
{
    public function __construct(private StorefrontService $storefront) {}

    /**
     * Everything the home page needs in one request.
     */
    public function home(): JsonResponse
    {
        return response()->json([
            'banners' => BannerResource::collection($this->storefront->banners()),
            'categories' => CategoryResource::collection($this->storefront->categories()),
            'featured' => ProductResource::collection($this->storefront->featuredProducts()),
            'new_arrivals' => ProductResource::collection($this->storefront->newArrivals()),
        ]);
    }

    public function categories(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->storefront->categories());
    }

    public function brands(): AnonymousResourceCollection
    {
        return BrandResource::collection($this->storefront->brands());
    }

    public function banners(): AnonymousResourceCollection
    {
        return BannerResource::collection($this->storefront->banners());
    }

    public function stores(): AnonymousResourceCollection
    {
        return StoreResource::collection($this->storefront->stores());
    }

    /**
     * Public store settings the frontend needs, e.g. whether to show the cart.
     */
    public function settings(SettingService $settings): JsonResponse
    {
        return response()->json([
            'cart_enabled' => $settings->cartEnabled(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Models\Banner;
use App\Services\SettingService;
use App\Services\StorefrontService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Read-only storefront data that isn't tied to one product. Responses are cached
 * until an admin changes the data behind them (see StorefrontService::flushCache).
 */
class CatalogController extends Controller
{
    public function __construct(private StorefrontService $storefront) {}

    /**
     * Everything the home page needs in one request.
     */
    public function home(): JsonResponse
    {
        return response()->json($this->storefront->remember('home', fn () => [
            'banners' => $this->toJson(BannerResource::collection($this->storefront->banners('hero')))['data'],
            'middle_banners' => $this->toJson(BannerResource::collection($this->storefront->banners('middle')))['data'],
            'categories' => $this->toJson(CategoryResource::collection($this->storefront->categories()))['data'],
            'featured' => $this->toJson(ProductResource::collection($this->storefront->featuredProducts()))['data'],
            'new_arrivals' => $this->toJson(ProductResource::collection($this->storefront->newArrivals()))['data'],
        ]));
    }

    public function categories(): JsonResponse
    {
        return $this->cached('categories', fn () => CategoryResource::collection($this->storefront->categories()));
    }

    public function brands(): JsonResponse
    {
        return $this->cached('brands', fn () => BrandResource::collection($this->storefront->brands()));
    }

    /**
     * Active banners, optionally limited to one slideshow with ?placement=hero|middle.
     */
    public function banners(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'placement' => ['sometimes', Rule::in(Banner::PLACEMENTS)],
        ]);
        $placement = $validated['placement'] ?? null;

        return $this->cached(
            'banners:'.($placement ?? 'all'),
            fn () => BannerResource::collection($this->storefront->banners($placement)),
        );
    }

    public function stores(): JsonResponse
    {
        return $this->cached('stores', fn () => StoreResource::collection($this->storefront->stores()));
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

    /**
     * Serve a resource collection's JSON from the cache, building it on a miss.
     *
     * @param  Closure(): AnonymousResourceCollection  $collection
     */
    private function cached(string $key, Closure $collection): JsonResponse
    {
        return response()->json($this->storefront->remember($key, fn () => $this->toJson($collection())));
    }

    /**
     * The exact JSON a resource collection would send, as plain arrays safe to cache.
     *
     * @return array<string, mixed>
     */
    private function toJson(AnonymousResourceCollection $collection): array
    {
        return $collection->response()->getData(true);
    }
}

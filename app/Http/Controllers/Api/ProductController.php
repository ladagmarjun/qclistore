<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    /**
     * List active products.
     *
     * Filters: category (slug), brand, tag, search, min_price, max_price,
     * sort (newest, price_asc, price_desc, rating, popular), per_page.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = array_filter($request->only(['category', 'brand', 'tag', 'search', 'min_price', 'max_price', 'sort']));
        $perPage = min(max($request->integer('per_page', 12), 1), 60);

        return ProductResource::collection($this->products->paginate($filters, $perPage));
    }

    /**
     * Show an active product by slug, with a few related products.
     */
    public function show(string $slug): ProductResource
    {
        $product = $this->products->findActiveBySlug($slug);

        $related = Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return ProductResource::make($product)->additional([
            'related' => ProductResource::collection($related),
        ]);
    }
}

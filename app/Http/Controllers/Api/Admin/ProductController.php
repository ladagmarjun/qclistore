<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    /**
     * List all products, including hidden ones. Takes the same filters as the shop.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = array_filter($request->only(['category', 'brand', 'tag', 'search', 'min_price', 'max_price', 'sort']));
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        return ProductResource::collection($this->products->paginateForAdmin($filters, $perPage));
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->productData());

        return ProductResource::make($product->refresh()->load('category'))->response()->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product->load('category'));
    }

    public function update(ProductRequest $request, Product $product): ProductResource
    {
        $this->products->update($product, $request->productData());

        return ProductResource::make($product->refresh()->load('category'));
    }

    /**
     * Delete a product, or hide it if it appears on past orders.
     */
    public function destroy(Product $product): JsonResponse
    {
        if ($product->orderItems()->exists()) {
            $product->update(['is_active' => false]);

            return response()->json([
                'message' => __('This product is on past orders, so it was hidden instead of deleted.'),
                'data' => ProductResource::make($product),
            ]);
        }

        $product->delete();

        return response()->json(null, 204);
    }
}

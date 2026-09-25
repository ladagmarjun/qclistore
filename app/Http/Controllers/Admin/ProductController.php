<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    /**
     * List all products, including hidden ones.
     */
    public function index(Request $request): Response
    {
        $filters = array_filter($request->only(['category', 'search', 'sort']));

        return Inertia::render('admin/products/index', [
            'products' => ProductResource::collection($this->products->paginateForAdmin($filters)),
            'categories' => $this->categories(),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return $this->form(null);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->productData());

        Inertia::flash('success', __('Product added.'));

        return to_route('admin.products.edit', $product);
    }

    public function edit(Product $product): Response
    {
        return $this->form($product);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->productData());

        Inertia::flash('success', __('Product saved.'));

        return to_route('admin.products.edit', $product);
    }

    /**
     * Delete a product, or hide it if it appears on past orders.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            $product->update(['is_active' => false]);

            Inertia::flash('success', __('This product is on past orders, so it was hidden instead of deleted.'));
        } else {
            $product->delete();

            Inertia::flash('success', __('Product deleted.'));
        }

        return to_route('admin.products.index');
    }

    private function form(?Product $product): Response
    {
        return Inertia::render('admin/products/form', [
            'product' => $product ? ProductResource::make($product)->resolve() : null,
            'categories' => $this->categories(),
            'brands' => BrandResource::collection(Brand::query()->orderBy('sort_order')->orderBy('name')->get())->resolve(),
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    private function categories(): array
    {
        return CategoryResource::collection(Category::query()->orderBy('sort_order')->orderBy('name')->get())->resolve();
    }
}

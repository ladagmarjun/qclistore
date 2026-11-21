<?php

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;

// Use the production cache store: the array store skips serialization, which
// hid cached models coming back as __PHP_Incomplete_Class.
beforeEach(fn () => config(['cache.default' => 'database']));

test('every cached storefront endpoint returns the same JSON on a cache hit', function (string $route, array $query) {
    Banner::factory()->create();
    Banner::factory()->middle()->create();
    Brand::factory()->create();
    Product::factory()->create(['tag' => 'Bestseller']);

    $first = $this->getJson(route($route, $query))->assertOk()->json();

    $this->getJson(route($route, $query))->assertOk()->assertExactJson($first);
})->with([
    'home' => ['home', []],
    'all banners' => ['banners.index', []],
    'hero banners' => ['banners.index', ['placement' => 'hero']],
    'middle banners' => ['banners.index', ['placement' => 'middle']],
    'categories' => ['categories.index', []],
    'brands' => ['brands.index', []],
    'stores' => ['stores.index', []],
]);

test('storefront lists are served from the cache', function () {
    Brand::factory()->create();
    $this->getJson(route('brands.index'))->assertJsonCount(1, 'data');

    // A raw insert skips model events, so a cached list doesn't see it.
    DB::table('brands')->insert(['name' => 'Sneaky', 'slug' => 'sneaky', 'is_active' => true, 'sort_order' => 0]);

    $this->getJson(route('brands.index'))->assertJsonCount(1, 'data');
});

test('the home page cache refreshes when a banner changes', function () {
    $banner = Banner::factory()->create();
    $this->getJson(route('home'))->assertJsonCount(1, 'banners');

    Banner::factory()->create();
    $this->getJson(route('home'))->assertJsonCount(2, 'banners');

    $banner->update(['is_active' => false]);
    $this->getJson(route('home'))->assertJsonCount(1, 'banners');

    Banner::query()->get()->each->delete();
    $this->getJson(route('home'))->assertJsonCount(0, 'banners');
});

test('the home page cache refreshes when a product changes', function () {
    $product = Product::factory()->create(['tag' => 'Bestseller']);
    $this->getJson(route('home'))->assertJsonCount(1, 'featured')->assertJsonCount(1, 'new_arrivals');

    $product->update(['is_active' => false]);

    $this->getJson(route('home'))->assertJsonCount(0, 'featured')->assertJsonCount(0, 'new_arrivals');
});

test('stock shown on the home page updates after an order', function () {
    $product = Product::factory()->create(['tag' => 'Bestseller', 'stock' => 5, 'colors' => [], 'color_stock' => []]);
    $this->getJson(route('home'))->assertJsonPath('featured.0.stock', 5);

    app(OrderService::class)->place(
        ['customer_name' => 'Maria Santos', 'customer_email' => 'maria@example.com', 'shipping_address' => '1 Ayala Ave', 'payment_method' => 'gcash'],
        [['product_id' => $product->id, 'quantity' => 2]],
    );

    $this->getJson(route('home'))->assertJsonPath('featured.0.stock', 3);
});

test('storefront requests are rate limited per visitor', function () {
    config(['app.storefront_rate_limit' => 3]);

    foreach (range(1, 3) as $i) {
        $this->getJson(route('settings.show'))->assertOk();
    }

    $this->getJson(route('settings.show'))->assertTooManyRequests();
    $this->getJson(route('settings.show'), ['REMOTE_ADDR' => '10.0.0.2'])->assertOk();
});

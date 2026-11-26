<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\SettingService;
use Laravel\Sanctum\Sanctum;

function orderPayload(array $items): array
{
    return [
        'customer_name' => 'Maria Santos',
        'customer_email' => 'maria@example.com',
        'shipping_address' => '1 Ayala Ave',
        'payment_method' => 'gcash',
        'items' => $items,
    ];
}

test('the home endpoint returns banners and products', function () {
    Banner::factory()->create();
    Product::factory()->create(['tag' => 'Bestseller']);

    $this->getJson(route('home'))
        ->assertOk()
        ->assertJsonCount(1, 'banners')
        ->assertJsonCount(1, 'featured')
        ->assertJsonCount(1, 'new_arrivals');
});

test('the home endpoint splits banners by slideshow placement', function () {
    Banner::factory()->count(2)->create();
    Banner::factory()->middle()->create();
    Banner::factory()->middle()->inactive()->create();

    $this->getJson(route('home'))
        ->assertOk()
        ->assertJsonCount(2, 'banners')
        ->assertJsonPath('banners.0.placement', 'hero')
        ->assertJsonCount(1, 'middle_banners')
        ->assertJsonPath('middle_banners.0.placement', 'middle');
});

test('banners can be filtered by placement', function () {
    Banner::factory()->count(2)->create();
    Banner::factory()->middle()->create();

    $this->getJson(route('banners.index'))->assertJsonCount(3, 'data');
    $this->getJson(route('banners.index', ['placement' => 'hero']))->assertJsonCount(2, 'data');
    $this->getJson(route('banners.index', ['placement' => 'middle']))->assertJsonCount(1, 'data');
    $this->getJson(route('banners.index', ['placement' => 'footer']))->assertUnprocessable();
});

test('filtering by a parent category includes its subcategories', function () {
    $bags = Category::factory()->create(['slug' => 'bags']);
    $totes = Category::factory()->childOf($bags)->create(['slug' => 'totes']);
    Product::factory()->for($bags)->create();
    Product::factory()->for($totes)->create();
    Product::factory()->create();

    $this->getJson(route('products.index', ['category' => 'bags']))->assertJsonCount(2, 'data');
    $this->getJson(route('products.index', ['category' => 'totes']))->assertJsonCount(1, 'data');
});

test('products can be filtered by category and are paginated', function () {
    $bags = Category::factory()->create(['slug' => 'bags']);
    Product::factory()->count(2)->for($bags)->create();
    Product::factory()->create();

    $this->getJson(route('products.index', ['category' => 'bags']))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'price', 'colors', 'images', 'links', 'category']], 'links', 'meta']);
});

test('a product can be fetched by slug with related products', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();
    Product::factory()->for($category)->create();

    $this->getJson(route('products.show', $product->slug))
        ->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonCount(1, 'related');
});

test('hidden products return 404', function () {
    $product = Product::factory()->inactive()->create();

    $this->getJson(route('products.show', $product->slug))->assertNotFound();
});

test('only active stores are listed', function () {
    Store::factory()->create();
    Store::factory()->inactive()->create();

    $this->getJson(route('stores.index'))->assertOk()->assertJsonCount(1, 'data');
});

test('the public settings say whether the cart is on', function () {
    $this->getJson(route('settings.show'))->assertExactJson(['cart_enabled' => true]);
});

test('a guest can place an order', function () {
    $product = Product::factory()->create(['price' => 1000, 'stock' => 5, 'colors' => ['Tan']]);

    $this->postJson(route('orders.store'), orderPayload([
        ['product_id' => $product->id, 'quantity' => 2, 'color' => 'Tan'],
    ]))
        ->assertCreated()
        ->assertJsonPath('data.total_amount', '2000.00')
        ->assertJsonPath('data.user_id', null)
        ->assertJsonPath('data.items.0.color', 'Tan')
        ->assertJsonPath('data.items.0.subtotal', '2000.00');

    expect($product->fresh()->stock)->toBe(3);
});

test('an order placed with a token belongs to the user', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['colors' => []]);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson(route('orders.store'), orderPayload([['product_id' => $product->id, 'quantity' => 1]]))
        ->assertCreated()
        ->assertJsonPath('data.user_id', $user->id);
});

test('orders are validated', function () {
    $product = Product::factory()->create(['stock' => 1, 'colors' => []]);

    $this->postJson(route('orders.store'), orderPayload([]))->assertJsonValidationErrors('items');

    $this->postJson(route('orders.store'), orderPayload([['product_id' => $product->id, 'quantity' => 2]]))
        ->assertJsonValidationErrors('items.0.quantity');
});

test('orders are refused when online ordering is off', function () {
    app(SettingService::class)->set('cart_enabled', 'false');
    $product = Product::factory()->create(['colors' => []]);

    $this->postJson(route('orders.store'), orderPayload([['product_id' => $product->id, 'quantity' => 1]]))
        ->assertForbidden();
});

test('customers only see their own orders', function () {
    $customer = User::factory()->create();
    $mine = Order::factory()->for($customer)->create();
    $theirs = Order::factory()->create();

    Sanctum::actingAs($customer);

    $this->getJson(route('orders.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine->id);

    $this->getJson(route('orders.show', $mine))->assertOk();
    $this->getJson(route('orders.show', $theirs))->assertNotFound();
});

test('filtering by men or women includes unisex products', function () {
    $purses = Category::factory()->create(['slug' => 'coin-purse']);
    $mens = Product::factory()->for($purses)->forGender('men')->create();
    $womens = Product::factory()->for($purses)->forGender('women')->create();
    $unisex = Product::factory()->for($purses)->forGender('unisex')->create();
    Product::factory()->forGender('men')->create();

    $ids = fn (array $query) => collect($this->getJson(route('products.index', $query))->assertOk()->json('data'))->pluck('id')->sort()->values()->all();

    expect($ids(['category' => 'coin-purse', 'gender' => 'men']))->toBe([$mens->id, $unisex->id])
        ->and($ids(['category' => 'coin-purse', 'gender' => 'women']))->toBe([$womens->id, $unisex->id])
        ->and($ids(['category' => 'coin-purse', 'gender' => 'unisex']))->toBe([$unisex->id])
        ->and($ids(['category' => 'coin-purse']))->toBe([$mens->id, $womens->id, $unisex->id]);

    $this->getJson(route('products.index', ['gender' => 'kids']))->assertJsonValidationErrors('gender');
});

test('categories list the gender menus they have active products for', function () {
    $accessories = Category::factory()->create(['slug' => 'accessories', 'sort_order' => 1]);
    $purses = Category::factory()->childOf($accessories)->create(['slug' => 'coin-purse']);
    $belts = Category::factory()->create(['slug' => 'belts', 'sort_order' => 2]);
    $scarves = Category::factory()->create(['slug' => 'scarves', 'sort_order' => 3]);
    Product::factory()->for($purses)->forGender('unisex')->create();
    Product::factory()->for($belts)->forGender('men')->create();
    Product::factory()->for($scarves)->forGender('women')->inactive()->create();

    $genders = collect($this->getJson(route('categories.index'))->assertOk()->json('data'))->pluck('genders', 'slug');

    expect($genders->all())->toBe([
        'accessories' => ['men', 'women'],
        'coin-purse' => ['men', 'women'],
        'belts' => ['men'],
        'scarves' => [],
    ]);
});

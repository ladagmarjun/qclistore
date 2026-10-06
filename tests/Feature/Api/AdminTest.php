<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\SettingService;
use Laravel\Sanctum\Sanctum;

function actingAsAdmin(): User
{
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    return $admin;
}

test('guests get a 401 and customers a 403', function () {
    $this->getJson(route('api.admin.dashboard'))->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.admin.dashboard'))->assertForbidden();
});

test('admins with an unverified email are refused', function () {
    Sanctum::actingAs(User::factory()->admin()->unverified()->create());

    $this->getJson(route('api.admin.dashboard'))->assertForbidden();
});

test('every admin list endpoint responds', function (string $route) {
    actingAsAdmin();

    $this->getJson(route($route))->assertOk();
})->with([
    'admin.dashboard',
    'admin.products.index',
    'admin.categories.index',
    'admin.brands.index',
    'admin.stores.index',
    'admin.banners.index',
    'admin.orders.index',
    'admin.users.index',
    'admin.settings.show',
]);

test('an admin can create a product', function () {
    actingAsAdmin();
    $category = Category::factory()->create();

    $this->postJson(route('api.admin.products.store'), [
        'category_id' => $category->id,
        'name' => 'Heritage Tote',
        'price' => 2499,
        'stock' => 10,
        'colors' => ['Black', 'Tan'],
        'images' => [['url' => 'https://example.com/black.jpg', 'color' => 'Black']],
        'links' => ['shopee' => 'https://shopee.ph/heritage-tote', 'lazada' => null],
    ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'heritage-tote')
        ->assertJsonPath('data.price', '2499.00')
        ->assertJsonPath('data.colors', ['Black', 'Tan'])
        ->assertJsonPath('data.links', ['shopee' => 'https://shopee.ph/heritage-tote'])
        ->assertJsonPath('data.glyph', '👜')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.category.id', $category->id);
});

test('updating a product keeps fields that were not sent', function () {
    actingAsAdmin();
    $product = Product::factory()->create(['name' => 'Old Name', 'rating' => 4.2, 'is_active' => true]);

    $this->putJson(route('api.admin.products.update', $product), [
        'name' => 'New Name',
        'price' => 100,
        'stock' => 3,
    ])
        ->assertOk()
        ->assertJsonPath('data.slug', 'new-name')
        ->assertJsonPath('data.rating', '4.2')
        ->assertJsonPath('data.is_active', true);
});

test('deleting a product that was ordered hides it instead', function () {
    actingAsAdmin();
    $product = Product::factory()->create();
    OrderItem::factory()->for($product)->create();

    $this->deleteJson(route('api.admin.products.destroy', $product))
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect($product->fresh())->not->toBeNull();
});

test('an unordered product is deleted', function () {
    actingAsAdmin();
    $product = Product::factory()->create();

    $this->deleteJson(route('api.admin.products.destroy', $product))->assertNoContent();

    expect($product->fresh())->toBeNull();
});

test('a category with products cannot be deleted', function () {
    actingAsAdmin();
    $category = Category::factory()->create();
    Product::factory()->for($category)->create();

    $this->deleteJson(route('api.admin.categories.destroy', $category))->assertConflict();
});

test('a category with subcategories cannot be deleted', function () {
    actingAsAdmin();
    $parent = Category::factory()->create();
    Category::factory()->childOf($parent)->create();

    $this->deleteJson(route('api.admin.categories.destroy', $parent))->assertConflict();
});

test('subcategories only go one level deep', function () {
    actingAsAdmin();
    $bags = Category::factory()->create();
    $totes = Category::factory()->childOf($bags)->create();

    $this->postJson(route('api.admin.categories.store'), ['name' => 'Mini Totes', 'parent_id' => $totes->id])
        ->assertJsonValidationErrors('parent_id');

    $this->putJson(route('api.admin.categories.update', $bags), ['name' => $bags->name, 'parent_id' => $bags->id])
        ->assertJsonValidationErrors('parent_id');

    $shoes = Category::factory()->create();
    $this->putJson(route('api.admin.categories.update', $bags), ['name' => $bags->name, 'parent_id' => $shoes->id])
        ->assertJsonValidationErrors('parent_id');

    $this->postJson(route('api.admin.categories.store'), ['name' => 'Clutches', 'parent_id' => $bags->id])
        ->assertCreated()
        ->assertJsonPath('data.parent_id', $bags->id);
});

test('a category can have an image', function () {
    actingAsAdmin();

    $response = $this->postJson(route('api.admin.categories.store'), ['name' => 'Totes', 'image_url' => 'https://example.com/totes.jpg'])
        ->assertCreated()
        ->assertJsonPath('data.image_url', 'https://example.com/totes.jpg');

    $category = Category::query()->findOrFail($response->json('data.id'));

    $this->putJson(route('api.admin.categories.update', $category), ['name' => 'Totes', 'image_url' => null])
        ->assertOk()
        ->assertJsonPath('data.image_url', null);

    $this->putJson(route('api.admin.categories.update', $category), ['name' => 'Totes', 'image_url' => 'not a link'])
        ->assertJsonValidationErrors('image_url');
});

test('a category gets a slug from its name', function () {
    actingAsAdmin();

    $this->postJson(route('api.admin.categories.store'), ['name' => 'Crossbody Bags'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'crossbody-bags')
        ->assertJsonPath('data.sort_order', 0);
});

test('an admin can move an order through its statuses', function () {
    actingAsAdmin();
    $product = Product::factory()->create(['stock' => 1]);
    $order = Order::factory()->status('pending')->create();
    OrderItem::factory()->for($order)->for($product)->create(['quantity' => 2]);

    $this->getJson(route('api.admin.orders.show', $order))
        ->assertJsonPath('next_statuses', ['processing', 'cancelled']);

    $this->putJson(route('api.admin.orders.update', $order), ['status' => 'delivered'])
        ->assertJsonValidationErrors('status');

    $this->putJson(route('api.admin.orders.update', $order), ['status' => 'cancelled'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled')
        ->assertJsonPath('next_statuses', []);

    expect($product->fresh()->stock)->toBe(3);
});

test('deactivating a user revokes their tokens', function () {
    $admin = actingAsAdmin();
    $customer = User::factory()->create();
    $customer->createToken('phone');

    $this->putJson(route('api.admin.users.update', $customer), ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    $this->putJson(route('api.admin.users.update', $admin), ['role' => 'customer'])
        ->assertJsonValidationErrors('user');

    expect($customer->tokens()->count())->toBe(0);
});

test('an admin can promote a customer and filter users by role', function () {
    actingAsAdmin();
    $customer = User::factory()->create();

    $this->putJson(route('api.admin.users.update', $customer), ['role' => 'admin'])
        ->assertOk()
        ->assertJsonPath('data.role', 'admin');

    expect($customer->fresh()->getRoleNames()->all())->toBe(['admin']);

    $this->getJson(route('api.admin.users.index', ['role' => 'admin']))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('an admin can turn off online ordering', function () {
    actingAsAdmin();

    $this->putJson(route('api.admin.settings.update'), ['cart_enabled' => false])
        ->assertExactJson(['cart_enabled' => false]);

    expect(app(SettingService::class)->cartEnabled())->toBeFalse();
});

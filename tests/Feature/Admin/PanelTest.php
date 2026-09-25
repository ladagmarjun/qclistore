<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function signInAdmin(): User
{
    $admin = User::factory()->admin()->create();
    test()->actingAs($admin);

    return $admin;
}

test('guests are sent to the admin login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

    $this->get(route('admin.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/login'));
});

test('an admin can sign in and out', function () {
    $admin = User::factory()->admin()->create();

    $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);

    $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

    $this->assertGuest();
});

test('only active, verified admins can sign in', function (Closure $makeUser, string $message) {
    $user = $makeUser();

    $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => $message]);

    $this->assertGuest();
})->with([
    'customer' => [fn () => User::factory()->create(), 'This account does not have admin access.'],
    'unverified admin' => [fn () => User::factory()->admin()->unverified()->create(), 'Verify your email address before signing in.'],
    'deactivated admin' => [fn () => User::factory()->admin()->inactive()->create(), 'This account has been deactivated.'],
]);

test('a signed-in customer gets a 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('every admin page renders', function (string $route, string $component) {
    signInAdmin();

    $this->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    ['admin.dashboard', 'admin/dashboard'],
    ['admin.products.index', 'admin/products/index'],
    ['admin.products.create', 'admin/products/form'],
    ['admin.categories.index', 'admin/catalog'],
    ['admin.brands.index', 'admin/catalog'],
    ['admin.stores.index', 'admin/catalog'],
    ['admin.banners.index', 'admin/catalog'],
    ['admin.orders.index', 'admin/orders/index'],
    ['admin.users.index', 'admin/users/index'],
    ['admin.settings.show', 'admin/settings'],
]);

test('the dashboard shows store stats', function () {
    signInAdmin();
    Product::factory()->create(['stock' => 2, 'is_active' => true]);

    $this->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.low_stock', 1)
            ->has('low_stock_products', 1)
            ->has('recent_orders', 0)
        );
});

test('an admin can add, edit and delete a product', function () {
    signInAdmin();
    $category = Category::factory()->create();

    $this->post(route('admin.products.store'), [
        'name' => 'Classic Tote',
        'category_id' => $category->id,
        'price' => 2499,
        'stock' => 10,
        'colors' => ['Black', 'Tan'],
        'links' => ['shopee' => 'https://shopee.ph/tote', 'lazada' => null],
    ])->assertInertiaFlash('success', 'Product added.');

    $product = Product::query()->sole();
    expect($product->slug)->toBe('classic-tote')
        ->and($product->links)->toBe(['shopee' => 'https://shopee.ph/tote']);

    $this->get(route('admin.products.edit', $product))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/products/form')
            ->where('product.name', 'Classic Tote')
            ->has('categories', 1)
        );

    $this->put(route('admin.products.update', $product), ['name' => 'Classic Tote', 'price' => 1999, 'stock' => 4])
        ->assertRedirect(route('admin.products.edit', $product));
    expect($product->fresh()->price)->toBe('1999.00');

    $this->delete(route('admin.products.destroy', $product))
        ->assertRedirect(route('admin.products.index'))
        ->assertInertiaFlash('success', 'Product deleted.');
    expect(Product::query()->count())->toBe(0);
});

test('an admin sets stock per colour and the total follows', function () {
    signInAdmin();

    $this->post(route('admin.products.store'), [
        'name' => 'Classic Tote',
        'price' => 2499,
        'colors' => ['Black', 'Tan'],
        'color_stock' => [['color' => 'Black', 'stock' => 7], ['color' => 'Tan', 'stock' => 0]],
    ])->assertSessionHasNoErrors();

    $product = Product::query()->sole();
    expect($product->color_stock)->toBe(['Black' => 7, 'Tan' => 0])
        ->and($product->stock)->toBe(7);

    $this->get(route('admin.products.edit', $product))
        ->assertInertia(fn (Assert $page) => $page->where('product.color_stock', ['Black' => 7, 'Tan' => 0]));

    $this->put(route('admin.products.update', $product), [
        'name' => 'Classic Tote',
        'price' => 2499,
        'colors' => ['Black'],
        'color_stock' => [['color' => 'Black', 'stock' => 2]],
    ])->assertSessionHasNoErrors();

    expect($product->fresh()->color_stock)->toBe(['Black' => 2])
        ->and($product->fresh()->stock)->toBe(2);

    $this->put(route('admin.products.update', $product), ['name' => 'Classic Tote', 'price' => 2499, 'colors' => [], 'color_stock' => [], 'stock' => 9])
        ->assertSessionHasNoErrors();

    expect($product->fresh()->color_stock)->toBe([])
        ->and($product->fresh()->stock)->toBe(9);
});

test('product validation errors come back to the form', function () {
    signInAdmin();

    $this->from(route('admin.products.create'))
        ->post(route('admin.products.store'), ['name' => '', 'price' => -1])
        ->assertRedirect(route('admin.products.create'))
        ->assertSessionHasErrors(['name', 'price', 'stock']);
});

test('a category with products cannot be deleted', function () {
    signInAdmin();
    $category = Category::factory()->create();
    Product::factory()->for($category)->create();

    $this->delete(route('admin.categories.destroy', $category))
        ->assertInertiaFlash('error');

    expect($category->fresh())->not->toBeNull();
});

test('an admin can add and edit a category', function () {
    signInAdmin();

    $this->post(route('admin.categories.store'), ['name' => 'Wallets', 'sort_order' => 2])
        ->assertRedirect(route('admin.categories.index'));

    $category = Category::query()->sole();
    expect($category->slug)->toBe('wallets');

    $this->put(route('admin.categories.update', $category), ['name' => 'Wallets & Cardholders', 'slug' => 'wallets'])
        ->assertInertiaFlash('success', 'Category saved.');
    expect($category->fresh()->name)->toBe('Wallets & Cardholders');
});

test('an admin can move an order along and cancelling restocks it', function () {
    signInAdmin();
    $order = Order::factory()->withItems()->create(['status' => 'pending']);
    $item = $order->items()->first();
    $stockBefore = $item->product->stock;

    $this->get(route('admin.orders.show', $order))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/show')
            ->where('nextStatuses', ['processing', 'cancelled'])
        );

    $this->put(route('admin.orders.update', $order), ['status' => 'delivered'])
        ->assertSessionHasErrors('status');

    $this->put(route('admin.orders.update', $order), ['status' => 'cancelled'])
        ->assertInertiaFlash('success', 'Order marked as Cancelled.');

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($item->product->fresh()->stock)->toBe($stockBefore + $item->quantity);
});

test('an admin can change roles and deactivate accounts, but not their own', function () {
    config(['session.driver' => 'database']);
    $admin = signInAdmin();
    $customer = User::factory()->create();
    $customer->createToken('phone');
    DB::table('sessions')->insert([
        'id' => 'customer-session',
        'user_id' => $customer->id,
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);

    $this->put(route('admin.users.update', $customer), ['role' => 'admin'])
        ->assertInertiaFlash('success', 'Role updated.');
    expect($customer->fresh()->hasRole('admin'))->toBeTrue();

    $this->put(route('admin.users.update', $customer), ['is_active' => false])
        ->assertInertiaFlash('success', 'Account deactivated.');
    expect($customer->fresh()->is_active)->toBeFalse()
        ->and($customer->tokens()->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $customer->id)->exists())->toBeFalse();

    $this->put(route('admin.users.update', $admin), ['role' => 'customer'])
        ->assertSessionHasErrors('user');
});

test('an admin can switch online ordering off', function () {
    signInAdmin();

    $this->put(route('admin.settings.update'), ['cart_enabled' => false])
        ->assertRedirect(route('admin.settings.show'))
        ->assertInertiaFlash('success', 'Ordering disabled.');

    expect(app(SettingService::class)->cartEnabled())->toBeFalse();
});

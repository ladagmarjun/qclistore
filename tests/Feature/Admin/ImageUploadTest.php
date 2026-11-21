<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('an admin can upload a product image and gets its public url back', function () {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->postJson(route('admin.uploads.images'), [
        'image' => UploadedFile::fake()->image('bag.jpg', 800, 800),
    ])->assertCreated();

    $files = Storage::disk('public')->files('products');
    expect($files)->toHaveCount(1);
    $response->assertJson(['url' => asset('storage/'.$files[0])]);
});

test('uploads are converted to webp and scaled down to fit their folder', function (string $folder, int $maxWidth) {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson(route('admin.uploads.images'), [
        'image' => UploadedFile::fake()->image('big.jpg', 4000, 2000),
        'folder' => $folder,
    ])->assertCreated()->assertJsonPath('url', fn (string $url) => str_ends_with($url, '.webp'));

    $files = Storage::disk('public')->files($folder);
    expect($files)->toHaveCount(1)->and($files[0])->toEndWith('.webp');

    $size = getimagesizefromstring(Storage::disk('public')->get($files[0]));
    expect($size['mime'])->toBe('image/webp')
        ->and($size[0])->toBe($maxWidth)
        ->and($size[1])->toBe(intdiv($maxWidth, 2));
})->with([
    'banners' => ['banners', 1920],
    'products' => ['products', 1200],
    'categories' => ['categories', 800],
]);

test('small images keep their size', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson(route('admin.uploads.images'), [
        'image' => UploadedFile::fake()->image('small.png', 300, 200),
    ])->assertCreated();

    $path = Storage::disk('public')->files('products')[0];
    $size = getimagesizefromstring(Storage::disk('public')->get($path));
    expect([$size[0], $size[1]])->toBe([300, 200]);
});

test('png transparency survives the webp conversion', function () {
    $this->actingAs(User::factory()->admin()->create());

    $png = imagecreatetruecolor(1600, 400);
    imagealphablending($png, false);
    imagesavealpha($png, true);
    imagefill($png, 0, 0, imagecolorallocatealpha($png, 0, 0, 0, 127));
    ob_start();
    imagepng($png);
    $file = UploadedFile::fake()->createWithContent('logo.png', (string) ob_get_clean());

    $this->postJson(route('admin.uploads.images'), ['image' => $file])->assertCreated();

    $path = Storage::disk('public')->files('products')[0];
    $stored = imagecreatefromstring(Storage::disk('public')->get($path));
    $alpha = (imagecolorat($stored, 10, 10) >> 24) & 0x7F;
    expect($path)->toEndWith('.webp')->and($alpha)->toBe(127);
});

test('gifs are stored as uploaded so animations keep working', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson(route('admin.uploads.images'), [
        'image' => UploadedFile::fake()->image('spin.gif', 2000, 500),
    ])->assertCreated();

    expect(Storage::disk('public')->files('products')[0])->toEndWith('.gif');
});

test('banner images go in their own folder', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson(route('admin.uploads.images'), [
        'image' => UploadedFile::fake()->image('hero.jpg', 1600, 600),
        'folder' => 'banners',
    ])->assertCreated();

    expect(Storage::disk('public')->files('banners'))->toHaveCount(1);
});

test('category images go in their own folder', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson(route('admin.uploads.images'), [
        'image' => UploadedFile::fake()->image('totes.jpg', 600, 600),
        'folder' => 'categories',
    ])->assertCreated();

    expect(Storage::disk('public')->files('categories'))->toHaveCount(1);
});

test('only known upload folders are allowed', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson(route('admin.uploads.images'), [
        'image' => UploadedFile::fake()->image('x.jpg'),
        'folder' => '../secrets',
    ])->assertUnprocessable()->assertJsonValidationErrors('folder');
});

test('uploads must be images of 5 MB or less', function (Closure $file) {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson(route('admin.uploads.images'), ['image' => $file()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    expect(Storage::disk('public')->files('products'))->toBeEmpty();
})->with([
    'not an image' => [fn () => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')],
    'too large' => [fn () => UploadedFile::fake()->image('huge.jpg')->size(6000)],
]);

test('customers cannot upload images', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.uploads.images'), ['image' => UploadedFile::fake()->image('bag.jpg')])
        ->assertForbidden();
});

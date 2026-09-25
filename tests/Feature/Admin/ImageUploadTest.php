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

test('banner images go in their own folder', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson(route('admin.uploads.images'), [
        'image' => UploadedFile::fake()->image('hero.jpg', 1600, 600),
        'folder' => 'banners',
    ])->assertCreated();

    expect(Storage::disk('public')->files('banners'))->toHaveCount(1);
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

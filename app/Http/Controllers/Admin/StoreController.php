<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRequest;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Physical store locations.
 */
class StoreController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/catalog', [
            'resource' => 'stores',
            'rows' => StoreResource::collection(Store::query()->orderBy('sort_order')->get())->resolve(),
        ]);
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        Store::query()->create($request->validated());

        Inertia::flash('success', __('Store added.'));

        return to_route('admin.stores.index');
    }

    public function update(StoreRequest $request, Store $store): RedirectResponse
    {
        $store->update($request->validated());

        Inertia::flash('success', __('Store saved.'));

        return to_route('admin.stores.index');
    }

    public function destroy(Store $store): RedirectResponse
    {
        $store->delete();

        Inertia::flash('success', __('Store deleted.'));

        return to_route('admin.stores.index');
    }
}

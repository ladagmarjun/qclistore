<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/catalog', [
            'resource' => 'brands',
            'rows' => BrandResource::collection(Brand::query()->orderBy('sort_order')->orderBy('name')->get())->resolve(),
        ]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        Brand::query()->create($request->validated());

        Inertia::flash('success', __('Brand added.'));

        return to_route('admin.brands.index');
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        Inertia::flash('success', __('Brand saved.'));

        return to_route('admin.brands.index');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        Inertia::flash('success', __('Brand deleted.'));

        return to_route('admin.brands.index');
    }
}

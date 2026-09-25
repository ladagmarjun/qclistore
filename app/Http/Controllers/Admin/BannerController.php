<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/catalog', [
            'resource' => 'banners',
            'rows' => BannerResource::collection(Banner::query()->orderBy('sort_order')->get())->resolve(),
        ]);
    }

    public function store(BannerRequest $request): RedirectResponse
    {
        Banner::query()->create($request->validated());

        Inertia::flash('success', __('Banner added.'));

        return to_route('admin.banners.index');
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $banner->update($request->validated());

        Inertia::flash('success', __('Banner saved.'));

        return to_route('admin.banners.index');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        Inertia::flash('success', __('Banner deleted.'));

        return to_route('admin.banners.index');
    }
}

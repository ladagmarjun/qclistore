<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BannerController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BannerResource::collection(Banner::query()->orderBy('sort_order')->get());
    }

    public function store(BannerRequest $request): JsonResponse
    {
        $banner = Banner::query()->create($request->validated());

        return BannerResource::make($banner->refresh())->response()->setStatusCode(201);
    }

    public function show(Banner $banner): BannerResource
    {
        return BannerResource::make($banner);
    }

    public function update(BannerRequest $request, Banner $banner): BannerResource
    {
        $banner->update($request->validated());

        return BannerResource::make($banner);
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return response()->json(null, 204);
    }
}

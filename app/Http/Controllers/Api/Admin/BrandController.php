<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BrandController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BrandResource::collection(Brand::query()->orderBy('sort_order')->orderBy('name')->get());
    }

    public function store(BrandRequest $request): JsonResponse
    {
        $brand = Brand::query()->create($request->validated());

        return BrandResource::make($brand->refresh())->response()->setStatusCode(201);
    }

    public function show(Brand $brand): BrandResource
    {
        return BrandResource::make($brand);
    }

    public function update(BrandRequest $request, Brand $brand): BrandResource
    {
        $brand->update($request->validated());

        return BrandResource::make($brand);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();

        return response()->json(null, 204);
    }
}

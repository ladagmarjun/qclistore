<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRequest;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StoreController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return StoreResource::collection(Store::query()->orderBy('sort_order')->get());
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $store = Store::query()->create($request->validated());

        return StoreResource::make($store->refresh())->response()->setStatusCode(201);
    }

    public function show(Store $store): StoreResource
    {
        return StoreResource::make($store);
    }

    public function update(StoreRequest $request, Store $store): StoreResource
    {
        $store->update($request->validated());

        return StoreResource::make($store);
    }

    public function destroy(Store $store): JsonResponse
    {
        $store->delete();

        return response()->json(null, 204);
    }
}

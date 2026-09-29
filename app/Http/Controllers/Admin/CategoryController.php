<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/catalog', [
            'resource' => 'categories',
            'rows' => CategoryResource::collection(
                Category::tree(fn ($query) => $query->withCount(['products', 'children'])),
            )->resolve(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::query()->create($request->validated());

        Inertia::flash('success', __('Category added.'));

        return to_route('admin.categories.index');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        Inertia::flash('success', __('Category saved.'));

        return to_route('admin.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            Inertia::flash('error', __('Move or delete this category\'s products first.'));
        } elseif ($category->children()->exists()) {
            Inertia::flash('error', __('Move or delete this category\'s subcategories first.'));
        } else {
            $category->delete();

            Inertia::flash('success', __('Category deleted.'));
        }

        return to_route('admin.categories.index');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\CategoryHierarchyService;
use App\Services\Catalog\SeoSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryHierarchyService $hierarchyService,
        protected SeoSyncService $seoSyncService,
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $categories = Category::with('parent')
            ->withCount(['products', 'children'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $parentCategories = $this->hierarchyService->getTree();

        return view('admin.categories.create', compact('parentCategories'));
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $category = DB::transaction(function () use ($data) {
            $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['name']);
            $originalSlug = $slug;
            $count = 1;
            while (Category::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'-'.$count++;
            }

            $category = Category::create([
                'name' => $data['name'],
                'slug' => $slug,
                'parent_id' => $data['parent_id'] ?? null,
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->seoSyncService->syncSeo($category, $data);

            $this->auditLogger->logAdminEvent(
                'catalog.category_created',
                auth('admin')->user(),
                ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug],
                $category
            );

            return $category;
        });

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' created successfully.");
    }

    public function show(Category $category): View
    {
        $category->load(['parent', 'children', 'seoMetadata'])
            ->loadCount('products');

        return view('admin.categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        $category->load('seoMetadata');
        $parentCategories = $this->hierarchyService->getTree($category->id);

        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($category, $data) {
            $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['name']);
            $originalSlug = $slug;
            $count = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $originalSlug.'-'.$count++;
            }

            $category->update([
                'name' => $data['name'],
                'slug' => $slug,
                'parent_id' => $data['parent_id'] ?? null,
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? $category->is_active,
            ]);

            $this->seoSyncService->syncSeo($category, $data);

            $this->auditLogger->logAdminEvent(
                'catalog.category_updated',
                auth('admin')->user(),
                ['id' => $category->id, 'changed' => $category->getChanges()],
                $category
            );
        });

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' updated successfully.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        $check = $this->hierarchyService->canDelete($category);

        if (! $check['allowed']) {
            return redirect()->route('admin.categories.index')
                ->with('error', $check['reason']);
        }

        DB::transaction(function () use ($category) {
            $name = $category->name;
            $id = $category->id;

            $category->seoMetadata()->delete();
            $category->delete();

            $this->auditLogger->logAdminEvent(
                'catalog.category_deleted',
                auth('admin')->user(),
                ['id' => $id, 'name' => $name],
                $category
            );
        });

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' deleted successfully.");
    }

    public function toggleStatus(Category $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        $this->auditLogger->logAdminEvent(
            'catalog.category_updated',
            auth('admin')->user(),
            ['id' => $category->id, 'is_active' => $category->is_active],
            $category
        );

        $statusStr = $category->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Category '{$category->name}' {$statusStr} successfully.");
    }
}

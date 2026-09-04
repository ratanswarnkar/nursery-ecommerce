<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Models\Brand;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\MediaStorageService;
use App\Services\Catalog\SeoSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function __construct(
        protected MediaStorageService $mediaStorageService,
        protected SeoSyncService $seoSyncService,
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $brands = Brand::withCount('products')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.brands.index', compact('brands'));
    }

    public function create(): View
    {
        return view('admin.brands.create');
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $brand = DB::transaction(function () use ($request, $data) {
            $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['name']);
            $originalSlug = $slug;
            $count = 1;
            while (Brand::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'-'.$count++;
            }

            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $this->mediaStorageService->storeBrandLogo($request->file('logo'));
            }

            try {
                $brand = Brand::create([
                    'name' => $data['name'],
                    'slug' => $slug,
                    'website' => $data['website'] ?? null,
                    'description' => $data['description'] ?? null,
                    'logo_path' => $logoPath,
                    'is_active' => $data['is_active'] ?? true,
                ]);

                $this->seoSyncService->syncSeo($brand, $data);

                $this->auditLogger->logAdminEvent(
                    'catalog.brand_created',
                    auth('admin')->user(),
                    ['id' => $brand->id, 'name' => $brand->name, 'slug' => $brand->slug],
                    $brand
                );

                return $brand;
            } catch (\Throwable $e) {
                if ($logoPath) {
                    $this->mediaStorageService->deleteFile($logoPath);
                }
                throw $e;
            }
        });

        return redirect()->route('admin.brands.index')
            ->with('success', "Brand '{$brand->name}' created successfully.");
    }

    public function edit(Brand $brand): View
    {
        $brand->load('seoMetadata');

        return view('admin.brands.edit', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $brand, $data) {
            $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['name']);
            $originalSlug = $slug;
            $count = 1;
            while (Brand::where('slug', $slug)->where('id', '!=', $brand->id)->exists()) {
                $slug = $originalSlug.'-'.$count++;
            }

            $oldLogo = $brand->logo_path;
            $newLogo = $oldLogo;
            $newLogoStored = false;

            if ($request->hasFile('logo')) {
                $newLogo = $this->mediaStorageService->storeBrandLogo($request->file('logo'));
                $newLogoStored = true;
            }

            try {
                $brand->update([
                    'name' => $data['name'],
                    'slug' => $slug,
                    'website' => $data['website'] ?? null,
                    'description' => $data['description'] ?? null,
                    'logo_path' => $newLogo,
                    'is_active' => $data['is_active'] ?? $brand->is_active,
                ]);

                $this->seoSyncService->syncSeo($brand, $data);

                if ($newLogoStored && $oldLogo && $oldLogo !== $newLogo) {
                    $this->mediaStorageService->deleteFile($oldLogo);
                }

                $this->auditLogger->logAdminEvent(
                    'catalog.brand_updated',
                    auth('admin')->user(),
                    ['id' => $brand->id, 'changed' => $brand->getChanges()],
                    $brand
                );
            } catch (\Throwable $e) {
                if ($newLogoStored && $newLogo) {
                    $this->mediaStorageService->deleteFile($newLogo);
                }
                throw $e;
            }
        });

        return redirect()->route('admin.brands.index')
            ->with('success', "Brand '{$brand->name}' updated successfully.");
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return redirect()->route('admin.brands.index')
                ->with('error', 'Cannot delete brand with associated products. Please reassign or remove products first.');
        }

        DB::transaction(function () use ($brand) {
            $id = $brand->id;
            $name = $brand->name;
            $logoPath = $brand->logo_path;

            $brand->seoMetadata()->delete();
            $brand->delete();

            if ($logoPath) {
                $this->mediaStorageService->deleteFile($logoPath);
            }

            $this->auditLogger->logAdminEvent(
                'catalog.brand_deleted',
                auth('admin')->user(),
                ['id' => $id, 'name' => $name],
                $brand
            );
        });

        return redirect()->route('admin.brands.index')
            ->with('success', "Brand '{$brand->name}' deleted successfully.");
    }

    public function toggleStatus(Brand $brand): RedirectResponse
    {
        $brand->update(['is_active' => ! $brand->is_active]);

        $this->auditLogger->logAdminEvent(
            'catalog.brand_updated',
            auth('admin')->user(),
            ['id' => $brand->id, 'is_active' => $brand->is_active],
            $brand
        );

        $statusStr = $brand->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Brand '{$brand->name}' {$statusStr} successfully.");
    }
}

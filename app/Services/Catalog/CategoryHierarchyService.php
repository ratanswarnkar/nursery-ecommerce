<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryHierarchyService
{
    /**
     * Get a formatted list of categories with depth indicator for selection.
     * Excludes a specific category and all its descendants if $excludeId is provided.
     */
    public function getTree(?int $excludeId = null): Collection
    {
        $categories = Category::orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $excludedIds = [];
        if ($excludeId) {
            $excludedCategory = $categories->firstWhere('id', $excludeId);
            if ($excludedCategory) {
                $excludedIds = array_merge([$excludeId], $this->collectDescendantIds($categories, $excludeId));
            }
        }

        $formatted = collect();
        $this->buildTreeList($categories, null, 0, $formatted, $excludedIds);

        return $formatted;
    }

    /**
     * Verify whether candidateParentId is a descendant of currentCategoryId.
     * Returns true if candidateParentId is a child/descendant of currentCategoryId.
     */
    public function isDescendant(int $candidateParentId, int $currentCategoryId): bool
    {
        if ($candidateParentId === $currentCategoryId) {
            return true;
        }

        $visited = [];
        $currentParentId = $candidateParentId;

        while ($currentParentId !== null) {
            if ($currentParentId === $currentCategoryId) {
                return true;
            }

            if (in_array($currentParentId, $visited, true)) {
                // Cycle detected in existing data
                return true;
            }
            $visited[] = $currentParentId;

            $parent = Category::find($currentParentId);
            $currentParentId = $parent?->parent_id;
        }

        return false;
    }

    /**
     * Verify whether a category can be safely deleted.
     */
    public function canDelete(Category $category): array
    {
        if ($category->children()->exists()) {
            return [
                'allowed' => false,
                'reason' => 'Cannot delete category that contains subcategories. Please reassign or delete subcategories first.',
            ];
        }

        if ($category->products()->exists()) {
            return [
                'allowed' => false,
                'reason' => 'Cannot delete category that has products assigned. Please reassign or remove products first.',
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    private function buildTreeList(
        Collection $allCategories,
        ?int $parentId,
        int $depth,
        Collection &$output,
        array $excludedIds
    ): void {
        $children = $allCategories->where('parent_id', $parentId);

        foreach ($children as $category) {
            if (in_array($category->id, $excludedIds, true)) {
                continue;
            }

            $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
            $output->push([
                'id' => $category->id,
                'name' => $prefix.$category->name,
                'raw_name' => $category->name,
                'slug' => $category->slug,
                'parent_id' => $category->parent_id,
                'is_active' => $category->is_active,
                'depth' => $depth,
            ]);

            $this->buildTreeList($allCategories, $category->id, $depth + 1, $output, $excludedIds);
        }
    }

    private function collectDescendantIds(Collection $allCategories, int $parentId): array
    {
        $descendants = [];
        $children = $allCategories->where('parent_id', $parentId);

        foreach ($children as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, $this->collectDescendantIds($allCategories, $child->id));
        }

        return $descendants;
    }

    /**
     * Get all active descendant category IDs for a given parent category.
     */
    public function getActiveDescendantCategoryIds(int $parentId): array
    {
        $allCategories = Category::where('is_active', true)->get(['id', 'parent_id', 'is_active']);

        return $this->collectDescendantIds($allCategories, $parentId);
    }
}

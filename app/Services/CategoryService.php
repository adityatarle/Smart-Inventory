<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->normalize($data);
            $category = Category::create($payload);
            $this->logger->log('category.created', "Created category {$category->name}", 'categories', $category);

            return $category;
        });
    }

    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $payload = $this->normalize($data, $category);

            if (! empty($payload['parent_id']) && (int) $payload['parent_id'] === (int) $category->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A category cannot be its own parent.',
                ]);
            }

            if (! empty($payload['parent_id']) && $this->wouldCreateCycle($category, (int) $payload['parent_id'])) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Selected parent would create a circular hierarchy.',
                ]);
            }

            $category->update($payload);
            $this->logger->log('category.updated', "Updated category {$category->name}", 'categories', $category);

            return $category->refresh();
        });
    }

    public function delete(Category $category): void
    {
        $blockers = $this->deletionBlockers($category);

        if ($blockers !== []) {
            throw ValidationException::withMessages([
                'category' => 'Cannot delete this category: '.implode(' ', $blockers),
            ]);
        }

        DB::transaction(function () use ($category) {
            $name = $category->name;
            $category->delete();
            $this->logger->log('category.deleted', "Deleted category {$name}", 'categories');
        });
    }

    public function deletionBlockers(Category $category): array
    {
        $blockers = [];

        $productCount = $category->products()->count();
        if ($productCount > 0) {
            $blockers[] = "It still has {$productCount} product(s). Reassign or remove them first.";
        }

        $childCount = $category->children()->count();
        if ($childCount > 0) {
            $blockers[] = "It has {$childCount} child categor(y/ies). Reassign or delete children first.";
        }

        return $blockers;
    }

    public function parentOptions(?Category $except = null)
    {
        return Category::query()
            ->when($except, function ($q) use ($except) {
                $q->where('id', '!=', $except->id)
                    ->where(function ($inner) use ($except) {
                        $descendantIds = $this->descendantIds($except);
                        if ($descendantIds !== []) {
                            $inner->whereNotIn('id', $descendantIds);
                        }
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }

    private function normalize(array $data, ?Category $category = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $baseSlug = Str::slug($name) ?: 'category';
        $slug = $baseSlug;
        $i = 1;

        while (
            Category::query()
                ->where('slug', $slug)
                ->when($category, fn ($q) => $q->where('id', '!=', $category->id))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$i;
            $i++;
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'parent_id' => ! empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function wouldCreateCycle(Category $category, int $parentId): bool
    {
        return in_array($parentId, $this->descendantIds($category), true);
    }

    private function descendantIds(Category $category): array
    {
        $ids = [];
        $queue = [$category->id];

        while ($queue !== []) {
            $currentId = array_shift($queue);
            $children = Category::where('parent_id', $currentId)->pluck('id')->all();
            foreach ($children as $childId) {
                if (! in_array($childId, $ids, true)) {
                    $ids[] = $childId;
                    $queue[] = $childId;
                }
            }
        }

        return $ids;
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\JsonStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(private JsonStorage $storage)
    {
    }

    public function index(): JsonResponse
    {
        $categories = $this->storage->read('categories.json');

        usort($categories, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $categories = $this->storage->read('categories.json');

        foreach ($categories as $category) {
            if (strcasecmp($category['name'], $validated['name']) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'A category with this name already exists.',
                ], 422);
            }
        }

        $category = [
            'id' => $this->storage->generateId('cat-'),
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? '',
            'created_at' => $this->storage->now(),
            'updated_at' => $this->storage->now(),
        ];

        $categories[] = $category;
        $this->storage->write('categories.json', $categories);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $categories = $this->storage->read('categories.json');
        $index = $this->findIndex($categories, $id);

        if ($index === null) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        foreach ($categories as $i => $category) {
            if ($i !== $index && strcasecmp($category['name'], $validated['name']) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'A category with this name already exists.',
                ], 422);
            }
        }

        $categories[$index]['name'] = $validated['name'];
        $categories[$index]['slug'] = Str::slug($validated['name']);
        $categories[$index]['description'] = $validated['description'] ?? '';
        $categories[$index]['updated_at'] = $this->storage->now();

        $this->storage->write('categories.json', $categories);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => $categories[$index],
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $categories = $this->storage->read('categories.json');
        $index = $this->findIndex($categories, $id);

        if ($index === null) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        $products = $this->storage->read('products.json');
        $inUse = collect($products)->contains(fn ($p) => ($p['category_id'] ?? '') === $id);

        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category that has products assigned.',
            ], 422);
        }

        array_splice($categories, $index, 1);
        $this->storage->write('categories.json', $categories);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    private function findIndex(array $categories, string $id): ?int
    {
        foreach ($categories as $index => $category) {
            if ($category['id'] === $id) {
                return $index;
            }
        }

        return null;
    }
}

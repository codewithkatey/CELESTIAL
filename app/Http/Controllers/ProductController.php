<?php

namespace App\Http\Controllers;

use App\Services\ImageLibrary;
use App\Services\JsonStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(
        private JsonStorage $storage,
        private ImageLibrary $images
    ) {
    }

    public function images(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->images->list(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $products = $this->storage->read('products.json');
        $categories = collect($this->storage->read('categories.json'))->keyBy('id');

        if ($search = $request->query('search')) {
            $search = strtolower($search);
            $products = array_values(array_filter($products, function ($product) use ($search) {
                return str_contains(strtolower($product['name'] ?? ''), $search)
                    || str_contains(strtolower($product['sku'] ?? ''), $search);
            }));
        }

        if ($categoryId = $request->query('category_id')) {
            $products = array_values(array_filter(
                $products,
                fn ($p) => ($p['category_id'] ?? '') === $categoryId
            ));
        }

        if ($status = $request->query('status')) {
            $products = array_values(array_filter(
                $products,
                fn ($p) => ($p['status'] ?? 'active') === $status
            ));
        }

        usort($products, fn ($a, $b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));

        $products = array_map(
            fn ($product) => $this->formatProduct($product, $categories),
            $products
        );

        return response()->json([
            'success' => true,
            'data' => $products,
            'stats' => $this->buildStats($this->storage->read('products.json')),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $product = $this->findProduct($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $categories = collect($this->storage->read('categories.json'))->keyBy('id');

        return response()->json([
            'success' => true,
            'data' => $this->formatProduct($product, $categories),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateProduct($request);
        $products = $this->storage->read('products.json');

        if ($this->skuExists($products, $validated['sku'])) {
            return response()->json([
                'success' => false,
                'message' => 'SKU already exists.',
            ], 422);
        }

        $product = [
            'id' => $this->storage->generateId('prod-'),
            'name' => $validated['name'],
            'sku' => strtoupper($validated['sku']),
            'category_id' => $validated['category_id'],
            'description' => $validated['description'] ?? '',
            'price' => (float) $validated['price'],
            'sale_price' => isset($validated['sale_price']) && $validated['sale_price'] !== ''
                ? (float) $validated['sale_price']
                : null,
            'sizes' => $validated['sizes'] ?? [],
            'colors' => $validated['colors'] ?? [],
            'stock' => (int) $validated['stock'],
            'status' => $validated['status'] ?? 'active',
            'image' => $this->processImage($request),
            'created_at' => $this->storage->now(),
            'updated_at' => $this->storage->now(),
        ];

        $products[] = $product;
        $this->storage->write('products.json', $products);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => $this->formatProduct($product),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $this->validateProduct($request, true);
        $products = $this->storage->read('products.json');
        $index = $this->findIndex($products, $id);

        if ($index === null) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        if ($this->skuExists($products, $validated['sku'], $id)) {
            return response()->json([
                'success' => false,
                'message' => 'SKU already exists.',
            ], 422);
        }

        $products[$index] = array_merge($products[$index], [
            'name' => $validated['name'],
            'sku' => strtoupper($validated['sku']),
            'category_id' => $validated['category_id'],
            'description' => $validated['description'] ?? '',
            'price' => (float) $validated['price'],
            'sale_price' => isset($validated['sale_price']) && $validated['sale_price'] !== ''
                ? (float) $validated['sale_price']
                : null,
            'sizes' => $validated['sizes'] ?? [],
            'colors' => $validated['colors'] ?? [],
            'stock' => (int) $validated['stock'],
            'status' => $validated['status'] ?? 'active',
            'image' => $this->processImage($request, $products[$index]['image'] ?? null),
            'updated_at' => $this->storage->now(),
        ]);

        $this->storage->write('products.json', $products);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => $this->formatProduct($products[$index]),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $products = $this->storage->read('products.json');
        $index = $this->findIndex($products, $id);

        if ($index === null) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        array_splice($products, $index, 1);
        $this->storage->write('products.json', $products);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    private function validateProduct(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'sku' => 'required|string|max:50',
            'category_id' => 'required|string',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sizes' => 'nullable|array',
            'sizes.*' => 'string|max:10',
            'colors' => 'nullable|array',
            'colors.*' => 'string|max:30',
            'stock' => 'required|integer|min:0',
            'status' => 'nullable|in:active,inactive,draft',
            'image' => ($isUpdate ? 'nullable' : 'nullable').'|image|max:5120',
            'image_path' => 'nullable|string|max:255',
            'remove_image' => 'nullable|boolean',
        ]);
    }

    private function processImage(Request $request, ?string $existing = null): ?string
    {
        if ($request->boolean('remove_image')) {
            return null;
        }

        if ($request->hasFile('image')) {
            return $this->images->storeUpload($request->file('image'));
        }

        if ($request->filled('image_path')) {
            $path = $this->images->normalizePath($request->input('image_path'));

            if (! $this->images->isValidPath($path)) {
                throw ValidationException::withMessages([
                    'image_path' => 'Selected image is not available in the library.',
                ]);
            }

            return $path;
        }

        return $existing;
    }

    private function formatProduct(array $product, $categories = null): array
    {
        if ($categories) {
            $product['category_name'] = $categories->get($product['category_id'] ?? '')['name'] ?? 'Uncategorized';
        }

        $product['image_url'] = $this->images->resolveUrl($product['image'] ?? null);

        return $product;
    }

    private function skuExists(array $products, string $sku, ?string $excludeId = null): bool
    {
        $sku = strtoupper($sku);

        foreach ($products as $product) {
            if ($excludeId && $product['id'] === $excludeId) {
                continue;
            }

            if (strtoupper($product['sku'] ?? '') === $sku) {
                return true;
            }
        }

        return false;
    }

    private function findProduct(string $id): ?array
    {
        $products = $this->storage->read('products.json');

        foreach ($products as $product) {
            if ($product['id'] === $id) {
                return $product;
            }
        }

        return null;
    }

    private function findIndex(array $products, string $id): ?int
    {
        foreach ($products as $index => $product) {
            if ($product['id'] === $id) {
                return $index;
            }
        }

        return null;
    }

    private function buildStats(array $products): array
    {
        $totalValue = 0;
        $lowStock = 0;
        $active = 0;

        foreach ($products as $product) {
            $price = $product['sale_price'] ?? $product['price'] ?? 0;
            $totalValue += $price * ($product['stock'] ?? 0);

            if (($product['stock'] ?? 0) <= 5) {
                $lowStock++;
            }

            if (($product['status'] ?? 'active') === 'active') {
                $active++;
            }
        }

        return [
            'total_products' => count($products),
            'active_products' => $active,
            'low_stock' => $lowStock,
            'inventory_value' => round($totalValue, 2),
        ];
    }
}

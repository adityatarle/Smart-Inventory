<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\QuotationItem;
use App\Models\SalesOrderItem;
use App\Support\DocumentNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductService
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function create(array $data, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($data, $image) {
            $data = $this->normalize($data);

            if ($image) {
                $data['image_path'] = $this->storeImage($image);
            }

            $product = Product::create($data);
            $this->logger->log('product.created', "Created product {$product->sku}", 'products', $product);

            return $product;
        });
    }

    public function update(Product $product, array $data, ?UploadedFile $image = null, bool $removeImage = false): Product
    {
        return DB::transaction(function () use ($product, $data, $image, $removeImage) {
            $data = $this->normalize($data);

            if ($removeImage && $product->image_path) {
                Storage::disk('public')->delete($product->image_path);
                $data['image_path'] = null;
            }

            if ($image) {
                if ($product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $data['image_path'] = $this->storeImage($image);
            }

            $product->update($data);
            $this->logger->log('product.updated', "Updated product {$product->sku}", 'products', $product);

            return $product->refresh();
        });
    }

    public function delete(Product $product): void
    {
        $blockers = $this->deletionBlockers($product);

        if ($blockers !== []) {
            throw ValidationException::withMessages([
                'product' => 'Cannot delete this product: '.implode(' ', $blockers),
            ]);
        }

        DB::transaction(function () use ($product) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $sku = $product->sku;
            $product->delete();
            $this->logger->log('product.deleted', "Deleted product {$sku}", 'products');
        });
    }

    public function deletionBlockers(Product $product): array
    {
        $blockers = [];

        $stockQty = (int) $product->stocks()->sum('quantity');
        if ($stockQty > 0) {
            $blockers[] = "It still has {$stockQty} units in warehouse stock.";
        }

        if (PurchaseOrderItem::where('product_id', $product->id)->exists()) {
            $blockers[] = 'It is referenced by purchase order items.';
        }

        if (SalesOrderItem::where('product_id', $product->id)->exists()) {
            $blockers[] = 'It is referenced by sales order items.';
        }

        if (QuotationItem::where('product_id', $product->id)->exists()) {
            $blockers[] = 'It is referenced by quotation items.';
        }

        return $blockers;
    }

    public function suggestSku(?string $name = null): string
    {
        $prefix = 'PRD';
        if ($name) {
            $clean = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($name)) ?: 'PRD';
            $prefix = substr($clean, 0, 3);
        }

        do {
            $sku = DocumentNumber::make($prefix);
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    public function exportCsv($query): StreamedResponse
    {
        $filename = 'products-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'sku', 'product_code', 'barcode', 'name', 'category', 'unit',
                'cost_price', 'sell_price', 'reorder_level', 'max_stock_level', 'is_active', 'description',
            ]);

            $query->with('category')->orderBy('name')->chunk(200, function ($products) use ($handle) {
                foreach ($products as $product) {
                    fputcsv($handle, [
                        $product->sku,
                        $product->product_code,
                        $product->barcode,
                        $product->name,
                        $product->category?->name,
                        $product->unit,
                        $product->cost_price,
                        $product->sell_price,
                        $product->reorder_level,
                        $product->max_stock_level,
                        $product->is_active ? 1 : 0,
                        $product->description,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function importCsv(UploadedFile $file): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $handle = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle) ?: [];
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $data = array_combine($headers, array_pad($row, count($headers), null));
            if (! is_array($data) || blank($data['sku'] ?? null) || blank($data['name'] ?? null)) {
                $skipped++;
                continue;
            }

            $payload = [
                'sku' => trim((string) $data['sku']),
                'product_code' => filled($data['product_code'] ?? null) ? trim((string) $data['product_code']) : null,
                'barcode' => filled($data['barcode'] ?? null) ? trim((string) $data['barcode']) : null,
                'name' => trim((string) $data['name']),
                'unit' => filled($data['unit'] ?? null) ? trim((string) $data['unit']) : 'pcs',
                'cost_price' => (float) ($data['cost_price'] ?? 0),
                'sell_price' => (float) ($data['sell_price'] ?? 0),
                'reorder_level' => (int) ($data['reorder_level'] ?? 10),
                'max_stock_level' => filled($data['max_stock_level'] ?? null) ? (int) $data['max_stock_level'] : null,
                'description' => $data['description'] ?? null,
                'is_active' => ! isset($data['is_active']) || in_array((string) $data['is_active'], ['1', 'true', 'yes', 'active'], true),
            ];

            if (filled($data['category'] ?? null)) {
                $category = \App\Models\Category::query()
                    ->where('name', trim((string) $data['category']))
                    ->orWhere('slug', \Illuminate\Support\Str::slug((string) $data['category']))
                    ->first();
                $payload['category_id'] = $category?->id;
            }

            $existing = Product::where('sku', $payload['sku'])->first();
            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                Product::create($payload);
                $created++;
            }
        }

        fclose($handle);

        $this->logger->log('product.imported', "Imported products (created {$created}, updated {$updated}, skipped {$skipped})", 'products');

        return compact('created', 'updated', 'skipped');
    }

    private function normalize(array $data): array
    {
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['category_id'] = ! empty($data['category_id']) ? $data['category_id'] : null;
        $data['product_code'] = filled($data['product_code'] ?? null) ? $data['product_code'] : null;
        $data['barcode'] = filled($data['barcode'] ?? null) ? $data['barcode'] : null;
        $data['max_stock_level'] = filled($data['max_stock_level'] ?? null) ? (int) $data['max_stock_level'] : null;
        $data['description'] = $data['description'] ?? null;

        return $data;
    }

    private function storeImage(UploadedFile $image): string
    {
        return $image->store('products', 'public');
    }
}

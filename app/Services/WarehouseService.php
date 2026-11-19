<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseService
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function create(array $data): Warehouse
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->normalize($data);
            if (blank($payload['code'])) {
                $payload['code'] = $this->suggestCode($payload['name']);
            }

            $warehouse = Warehouse::create($payload);
            $this->logger->log('warehouse.created', "Created warehouse {$warehouse->code}", 'warehouses', $warehouse);

            return $warehouse;
        });
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data) {
            $warehouse->update($this->normalize($data));
            $this->logger->log('warehouse.updated', "Updated warehouse {$warehouse->code}", 'warehouses', $warehouse);

            return $warehouse->refresh();
        });
    }

    public function delete(Warehouse $warehouse): void
    {
        $blockers = $this->deletionBlockers($warehouse);
        if ($blockers !== []) {
            throw ValidationException::withMessages([
                'warehouse' => 'Cannot delete this warehouse: '.implode(' ', $blockers),
            ]);
        }

        DB::transaction(function () use ($warehouse) {
            $code = $warehouse->code;
            $warehouse->delete();
            $this->logger->log('warehouse.deleted', "Deleted warehouse {$code}", 'warehouses');
        });
    }

    public function deletionBlockers(Warehouse $warehouse): array
    {
        $blockers = [];
        $qty = (int) $warehouse->stocks()->sum('quantity');
        if ($qty > 0) {
            $blockers[] = "It still holds {$qty} stock units.";
        }
        if ($warehouse->purchaseOrders()->exists()) {
            $blockers[] = 'It is referenced by purchase orders.';
        }
        if ($warehouse->salesOrders()->exists()) {
            $blockers[] = 'It is referenced by sales orders.';
        }

        return $blockers;
    }

    public function suggestCode(?string $name = null): string
    {
        $prefix = 'WH';
        if ($name) {
            $clean = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($name)) ?: 'WH';
            $prefix = 'WH-'.substr($clean, 0, 4);
        }

        do {
            $code = DocumentNumber::make($prefix);
        } while (Warehouse::where('code', $code)->exists());

        return $code;
    }

    private function normalize(array $data): array
    {
        return [
            'name' => trim((string) ($data['name'] ?? '')),
            'code' => strtoupper(trim((string) ($data['code'] ?? ''))),
            'address' => $data['address'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'city' => $data['city'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}

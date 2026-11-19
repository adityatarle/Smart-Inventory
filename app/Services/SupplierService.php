<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierService
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function create(array $data): Supplier
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->normalize($data);
            if (blank($payload['code'])) {
                $payload['code'] = $this->suggestCode();
            }

            $supplier = Supplier::create($payload);
            $this->logger->log('supplier.created', "Created supplier {$supplier->code}", 'suppliers', $supplier);

            return $supplier;
        });
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data) {
            $supplier->update($this->normalize($data, $supplier));
            $this->logger->log('supplier.updated', "Updated supplier {$supplier->code}", 'suppliers', $supplier);

            return $supplier->refresh();
        });
    }

    public function delete(Supplier $supplier): void
    {
        $blockers = $this->deletionBlockers($supplier);
        if ($blockers !== []) {
            throw ValidationException::withMessages([
                'supplier' => 'Cannot delete this supplier: '.implode(' ', $blockers),
            ]);
        }

        DB::transaction(function () use ($supplier) {
            $code = $supplier->code;
            $supplier->delete();
            $this->logger->log('supplier.deleted', "Deleted supplier {$code}", 'suppliers');
        });
    }

    public function deletionBlockers(Supplier $supplier): array
    {
        $blockers = [];

        // Phase 2: POs still use supplier_name text. Match by name/company for soft dependency.
        $poCount = PurchaseOrder::query()
            ->where(function ($q) use ($supplier) {
                $q->where('supplier_name', $supplier->name)
                    ->orWhere('supplier_name', $supplier->company_name)
                    ->orWhere('supplier_name', $supplier->code);
            })
            ->count();

        if ($poCount > 0) {
            $blockers[] = "It matches {$poCount} purchase order supplier name(s).";
        }

        return $blockers;
    }

    public function suggestCode(): string
    {
        do {
            $code = DocumentNumber::make('SUP');
        } while (Supplier::where('code', $code)->exists());

        return $code;
    }

    private function normalize(array $data, ?Supplier $supplier = null): array
    {
        $code = strtoupper(trim((string) ($data['code'] ?? $supplier?->code ?? '')));

        return [
            'code' => $code,
            'name' => trim((string) ($data['name'] ?? '')),
            'company_name' => $data['company_name'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'gst_number' => $data['gst_number'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'notes' => $data['notes'] ?? null,
        ];
    }
}

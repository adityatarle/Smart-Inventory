<?php

namespace App\Services;

use App\Models\Customer;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->normalize($data);
            if (blank($payload['code'])) {
                $payload['code'] = DocumentNumber::make('CUS');
            }

            $customer = Customer::create($payload);
            $this->logger->log('customer.created', "Created customer {$customer->code}", 'customers', $customer);

            return $customer;
        });
    }

    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $payload = $this->normalize($data, $customer);
            unset($payload['code']); // keep immutable code once assigned
            $customer->update($payload);
            $this->logger->log('customer.updated', "Updated customer {$customer->code}", 'customers', $customer);

            return $customer->refresh();
        });
    }

    public function delete(Customer $customer): void
    {
        $blockers = $this->deletionBlockers($customer);
        if ($blockers !== []) {
            throw ValidationException::withMessages([
                'customer' => 'Cannot delete this customer: '.implode(' ', $blockers),
            ]);
        }

        DB::transaction(function () use ($customer) {
            $code = $customer->code;
            $customer->delete();
            $this->logger->log('customer.deleted', "Deleted customer {$code}", 'customers');
        });
    }

    public function deletionBlockers(Customer $customer): array
    {
        $blockers = [];

        if ($customer->salesOrders()->exists()) {
            $blockers[] = 'It has sales orders.';
        }
        if ($customer->quotations()->exists()) {
            $blockers[] = 'It has quotations.';
        }
        if ($customer->invoices()->exists()) {
            $blockers[] = 'It has invoices.';
        }
        if ($customer->blanketOrders()->exists()) {
            $blockers[] = 'It has blanket orders.';
        }

        return $blockers;
    }

    private function normalize(array $data, ?Customer $customer = null): array
    {
        return [
            'code' => strtoupper(trim((string) ($data['code'] ?? $customer?->code ?? ''))),
            'name' => trim((string) ($data['name'] ?? '')),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'gst_number' => $data['gst_number'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'shipping_address' => $data['shipping_address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'user_id' => ! empty($data['user_id']) ? $data['user_id'] : null,
            'sales_partner_id' => ! empty($data['sales_partner_id']) ? $data['sales_partner_id'] : null,
            'sales_person_id' => ! empty($data['sales_person_id']) ? $data['sales_person_id'] : null,
            'credit_limit' => (float) ($data['credit_limit'] ?? 0),
            'payment_terms' => $data['payment_terms'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'notes' => $data['notes'] ?? null,
        ];
    }
}

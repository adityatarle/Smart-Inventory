<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesPartner;
use App\Models\SalesPerson;
use App\Models\User;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;

class SalesPersonController extends Controller
{
    public function index()
    {
        $persons = SalesPerson::query()->with('partner')->latest()->paginate(12);

        return view('sales.persons.index', compact('persons'));
    }

    public function create()
    {
        return view('sales.persons.create', $this->formData());
    }

    public function store(Request $request)
    {
        SalesPerson::create($this->validated($request) + [
            'code' => DocumentNumber::make('SE'),
        ]);

        return redirect()->route('sales-persons.index')->with('success', 'Sales person created.');
    }

    public function edit(SalesPerson $salesPerson)
    {
        return view('sales.persons.edit', array_merge($this->formData(), compact('salesPerson')));
    }

    public function update(Request $request, SalesPerson $salesPerson)
    {
        $salesPerson->update($this->validated($request));

        return redirect()->route('sales-persons.index')->with('success', 'Sales person updated.');
    }

    public function destroy(SalesPerson $salesPerson)
    {
        $salesPerson->delete();

        return redirect()->route('sales-persons.index')->with('success', 'Sales person deleted.');
    }

    private function formData(): array
    {
        return [
            'partners' => SalesPartner::where('is_active', true)->orderBy('name')->get(),
            'users' => User::whereIn('role', [User::ROLE_STAFF, User::ROLE_MANAGER])->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'exists:users,id'],
            'sales_partner_id' => ['nullable', 'exists:sales_partners,id'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}

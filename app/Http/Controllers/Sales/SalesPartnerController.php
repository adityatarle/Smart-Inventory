<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesPartner;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;

class SalesPartnerController extends Controller
{
    public function index()
    {
        $partners = SalesPartner::query()->withCount(['customers', 'salesPersons'])->latest()->paginate(12);

        return view('sales.partners.index', compact('partners'));
    }

    public function create()
    {
        return view('sales.partners.create');
    }

    public function store(Request $request)
    {
        SalesPartner::create($this->validated($request) + [
            'code' => DocumentNumber::make('SP'),
        ]);

        return redirect()->route('sales-partners.index')->with('success', 'Sales partner created.');
    }

    public function edit(SalesPartner $salesPartner)
    {
        return view('sales.partners.edit', compact('salesPartner'));
    }

    public function update(Request $request, SalesPartner $salesPartner)
    {
        $salesPartner->update($this->validated($request));

        return redirect()->route('sales-partners.index')->with('success', 'Sales partner updated.');
    }

    public function destroy(SalesPartner $salesPartner)
    {
        $salesPartner->delete();

        return redirect()->route('sales-partners.index')->with('success', 'Sales partner deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'territory' => ['nullable', 'string', 'max:120'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}

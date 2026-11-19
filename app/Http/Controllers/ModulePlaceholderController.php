<?php

namespace App\Http\Controllers;

class ModulePlaceholderController extends Controller
{
    public function show(?string $module = null)
    {
        $module ??= request()->route('module') ?? basename(request()->path());

        $map = [
            'stock-movements' => ['Stock Movements', 'Phase 3', 'Complete inventory transaction history and filters.'],
            'suppliers' => ['Suppliers', 'Phase 2', 'Supplier master data and purchase history.'],
            'payments' => ['Payments', 'Phase 6', 'Payment tracking against purchase and sales orders.'],
            'reports' => ['Reports & Analytics', 'Phase 6', 'Operational reports with export support.'],
            'settings' => ['Settings', 'Phase 1', 'Organization preferences and system configuration.'],
            'notifications' => ['Notifications', 'Phase 1', 'System alerts and activity notifications.'],
            'roles' => ['Roles & Permissions', 'Phase 1', 'Role matrix management.'],
        ];

        $meta = $map[$module] ?? [ucfirst(str_replace('-', ' ', $module)), 'Upcoming', 'This module is queued in the ERP roadmap.'];

        return view('modules.placeholder', [
            'title' => $meta[0],
            'phase' => $meta[1],
            'description' => $meta[2],
        ]);
    }
}

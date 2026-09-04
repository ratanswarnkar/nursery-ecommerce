<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminRbacSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'admin';

        $permissions = [
            // Core
            'dashboard.view',
            'audit-logs.view',
            'settings.view',
            'settings.manage',

            // Customer Domain
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',

            // Catalog
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'brands.view',
            'brands.create',
            'brands.update',
            'brands.delete',
            'attributes.view',
            'attributes.create',
            'attributes.update',
            'attributes.delete',

            // Orders & Inventory
            'orders.view',
            'orders.create',
            'orders.update',
            'orders.cancel',
            'inventory.view',
            'inventory.manage',

            // Financial & Commercial
            'payments.view',
            'payments.manage',
            'refunds.manage',
            'coupons.view',
            'coupons.manage',

            // CMS & SEO
            'cms.view',
            'cms.manage',
            'seo.view',
            'seo.manage',

            // Tender Domain (Authorization Only - Domain Remains Independent)
            'tenders.view',
            'tenders.create',
            'tenders.update',
            'tenders.delete',
            'tenders.manage',
            'tender-billing.view',
            'tender-billing.create',
            'tender-billing.update',

            // Future-Ready Invoices & Letterheads (Authorization Hook Only - Zero UI/Logic Implemented)
            'invoices.view',
            'invoices.create',
            'invoices.manage',
            'letterheads.view',
            'letterheads.manage',

            // Security & System Access
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
            'roles.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guard,
            ]);
        }

        // 1. Super Admin Role
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guard]);
        $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());

        // 2. Admin Role (Broad operational access, excluding roles management)
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guard]);
        $adminPermissions = [
            'dashboard.view',
            'customers.view', 'customers.create', 'customers.update',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'brands.view', 'brands.create', 'brands.update', 'brands.delete',
            'attributes.view', 'attributes.create', 'attributes.update', 'attributes.delete',
            'orders.view', 'orders.create', 'orders.update', 'orders.cancel',
            'inventory.view', 'inventory.manage',
            'payments.view', 'payments.manage', 'refunds.manage',
            'coupons.view', 'coupons.manage',
            'cms.view', 'cms.manage',
            'seo.view', 'seo.manage',
            'tenders.view', 'tenders.create', 'tenders.update', 'tenders.manage',
            'audit-logs.view',
        ];
        $adminRole->syncPermissions(Permission::where('guard_name', $guard)->whereIn('name', $adminPermissions)->get());

        // 3. Manager Role (Day-to-day operations)
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => $guard]);
        $managerPermissions = [
            'dashboard.view',
            'customers.view',
            'products.view',
            'categories.view',
            'brands.view',
            'attributes.view',
            'orders.view', 'orders.update',
            'inventory.view', 'inventory.manage',
            'coupons.view',
            'tenders.view',
        ];
        $managerRole->syncPermissions(Permission::where('guard_name', $guard)->whereIn('name', $managerPermissions)->get());

        // 4. Staff Role (Limited view access)
        $staffRole = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => $guard]);
        $staffPermissions = [
            'dashboard.view',
            'customers.view',
            'products.view',
            'categories.view',
            'brands.view',
            'attributes.view',
            'orders.view',
            'inventory.view',
        ];
        $staffRole->syncPermissions(Permission::where('guard_name', $guard)->whereIn('name', $staffPermissions)->get());
    }
}

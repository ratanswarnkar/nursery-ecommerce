<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add check constraint on inventories (quantity >= reserved_quantity >= 0)
        try {
            DB::statement('ALTER TABLE inventories ADD CONSTRAINT chk_inventory_quantities_valid CHECK (quantity >= 0 AND reserved_quantity >= 0 AND quantity >= reserved_quantity)');
        } catch (Throwable $e) {
            // In case check constraints are simulated or syntax differences occur
        }

        // 2. Add performance indexes and last_activity_at to carts table
        Schema::table('carts', function (Blueprint $table) {
            if (! Schema::hasColumn('carts', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('is_active');
            }
            $table->index(['customer_id', 'is_active'], 'carts_customer_active_idx');
            $table->index(['session_id', 'is_active'], 'carts_session_active_idx');
        });

        // 3. Foreign key on stock_movements.created_by referencing admins.id
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('admins')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('carts_customer_active_idx');
            $table->dropIndex('carts_session_active_idx');
            if (Schema::hasColumn('carts', 'last_activity_at')) {
                $table->dropColumn('last_activity_at');
            }
        });

        try {
            DB::statement('ALTER TABLE inventories DROP CONSTRAINT chk_inventory_quantities_valid');
        } catch (Throwable $e) {
            // Ignore if constraint dropped
        }
    }
};

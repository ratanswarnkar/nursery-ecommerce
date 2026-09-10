<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('refund_amount');
            $table->timestamp('approved_at')->nullable()->after('admin_notes');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->timestamp('completed_at')->nullable()->after('rejected_at');
        });

        Schema::table('order_refunds', function (Blueprint $table) {
            $table->foreignId('order_return_id')->nullable()->after('order_id')->constrained('order_returns')->nullOnDelete();
            $table->string('idempotency_key')->nullable()->unique()->after('status');
            $table->string('gateway')->nullable()->after('idempotency_key');
            $table->string('gateway_refund_id')->nullable()->index()->after('gateway');
            $table->json('payload')->nullable()->after('gateway_refund_id');
            $table->foreignId('created_by_admin_id')->nullable()->after('payload')->constrained('admins')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_refunds', function (Blueprint $table) {
            $table->dropForeign(['order_return_id']);
            $table->dropForeign(['created_by_admin_id']);
            $table->dropColumn([
                'order_return_id',
                'idempotency_key',
                'gateway',
                'gateway_refund_id',
                'payload',
                'created_by_admin_id',
            ]);
        });

        Schema::table('order_returns', function (Blueprint $table) {
            $table->dropColumn([
                'admin_notes',
                'approved_at',
                'rejected_at',
                'completed_at',
            ]);
        });
    }
};

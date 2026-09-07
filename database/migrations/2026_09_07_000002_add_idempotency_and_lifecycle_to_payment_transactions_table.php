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
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->unique()->after('status');
            $table->string('failure_code')->nullable()->after('idempotency_key');
            $table->text('failure_message')->nullable()->after('failure_code');
            $table->timestamp('paid_at')->nullable()->after('failure_message');
            $table->timestamp('failed_at')->nullable()->after('paid_at');
            $table->timestamp('cancelled_at')->nullable()->after('failed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'idempotency_key',
                'failure_code',
                'failure_message',
                'paid_at',
                'failed_at',
                'cancelled_at',
            ]);
        });
    }
};

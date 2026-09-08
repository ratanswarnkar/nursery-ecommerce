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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->string('invoice_number', 64)->unique()->index();
            $table->dateTime('invoice_date');
            $table->string('currency', 3)->default('INR');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('shipping_amount', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2);
            $table->json('customer_snapshot');
            $table->json('billing_address_snapshot');
            $table->json('shipping_address_snapshot');
            $table->json('items_snapshot');
            $table->string('payment_status_snapshot');
            $table->string('order_status_snapshot');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['invoice_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

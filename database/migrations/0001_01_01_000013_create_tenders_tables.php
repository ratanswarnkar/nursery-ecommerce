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
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('tender_number')->unique();
            $table->string('name');
            $table->string('department_name')->index();
            $table->string('project_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('original_soq_value', 14, 2);
            $table->decimal('awarded_value', 14, 2);
            $table->decimal('below_above_percentage', 8, 4)->nullable();
            $table->string('pricing_mode')->default('item_wise');
            $table->string('status')->default('draft')->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('special_billing_enabled')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tender_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->string('document_type')->default('soq_boq');
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->index('tender_id');
        });

        Schema::create('tender_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('item_code')->nullable();
            $table->text('description');
            $table->string('unit');
            $table->decimal('government_quantity', 12, 2);
            $table->decimal('government_rate', 12, 2);
            $table->decimal('government_amount', 14, 2);
            $table->decimal('quoted_rate', 12, 2)->nullable();
            $table->decimal('quoted_amount', 14, 2)->nullable();
            $table->decimal('calculated_rate', 12, 2)->nullable();
            $table->decimal('final_rate', 12, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tender_id', 'item_code']);
        });

        Schema::create('tender_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->string('requirement_number');
            $table->date('requirement_date');
            $table->foreignId('source_document_id')->nullable()->constrained('tender_documents')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('status')->default('received');
            $table->string('source_type')->default('manual');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tender_id', 'requirement_date']);
        });

        Schema::create('tender_requirement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_requirement_id')->constrained('tender_requirements')->cascadeOnDelete();
            $table->foreignId('tender_item_id')->nullable()->constrained('tender_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('item_code')->nullable();
            $table->text('description');
            $table->string('unit');
            $table->decimal('quantity', 12, 2);
            $table->decimal('matched_rate', 12, 2)->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('matching_status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('tender_requirement_id');
            $table->index('tender_item_id');
        });

        Schema::create('tender_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->foreignId('tender_requirement_id')->nullable()->constrained('tender_requirements')->nullOnDelete();
            $table->string('bill_number')->unique();
            $table->date('bill_date');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('grand_total', 14, 2);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->string('source_type')->default('requirement');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tender_id', 'bill_date']);
        });

        Schema::create('tender_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_bill_id')->constrained('tender_bills')->cascadeOnDelete();
            $table->foreignId('tender_item_id')->nullable()->constrained('tender_items')->nullOnDelete();
            $table->foreignId('requirement_item_id')->nullable()->constrained('tender_requirement_items')->nullOnDelete();
            $table->string('item_code')->nullable();
            $table->text('description');
            $table->string('unit');
            $table->decimal('quantity', 12, 2);
            $table->decimal('rate', 12, 2);
            $table->decimal('amount', 14, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('tender_bill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tender_bill_items');
        Schema::dropIfExists('tender_bills');
        Schema::dropIfExists('tender_requirement_items');
        Schema::dropIfExists('tender_requirements');
        Schema::dropIfExists('tender_items');
        Schema::dropIfExists('tender_documents');
        Schema::dropIfExists('tenders');
    }
};

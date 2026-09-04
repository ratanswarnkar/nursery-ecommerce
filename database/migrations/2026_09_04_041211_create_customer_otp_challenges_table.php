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
        Schema::create('customer_otp_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->string('phone_e164', 32)->index();
            $table->string('otp_hash');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('verified_at')->nullable()->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('resend_available_at')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['phone_e164', 'verified_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_otp_challenges');
    }
};

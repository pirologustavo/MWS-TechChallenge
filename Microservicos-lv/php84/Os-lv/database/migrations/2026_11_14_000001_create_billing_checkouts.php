<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('billing_checkouts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('osid')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('preference_id')->nullable();
            $table->text('checkout_url')->nullable();
            $table->string('payment_id')->nullable()->unique();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        Schema::table('os', fn (Blueprint $table) => $table->string('payment_status')->default('pending'));
    }
    public function down(): void {
        Schema::table('os', fn (Blueprint $table) => $table->dropColumn('payment_status'));
        Schema::dropIfExists('billing_checkouts');
    }
};

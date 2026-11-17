<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('billing_checkouts', function (Blueprint $table): void {
        $table->unsignedInteger('attempts')->default(0);
        $table->text('last_error')->nullable();
        $table->timestamp('available_at')->nullable()->index();
        $table->timestamp('lease_until')->nullable();
    }); }
    public function down(): void { Schema::table('billing_checkouts', fn (Blueprint $table) => $table->dropColumn(['attempts','last_error','available_at','lease_until'])); }
};

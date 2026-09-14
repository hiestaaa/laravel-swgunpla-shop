<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->string('status')->default('pending'); // pending, notified, cancelled
            $table->index(['product_id', 'status']);
            $table->index('email');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_notifications');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('reference');
            $table->timestamps();
        });

        Schema::create('demo_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('demo_order_id')->constrained()->cascadeOnDelete();
            $table->string('product');
            $table->decimal('quantity', 10, 3);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_order_lines');
        Schema::dropIfExists('demo_orders');
    }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('warehouses', function (Blueprint $t) { $t->id(); $t->string('name')->unique(); $t->timestamps(); });
  Schema::create('products', function (Blueprint $t) { $t->id(); $t->string('sku')->unique(); $t->string('name'); $t->timestamps(); });
  Schema::create('batches', function (Blueprint $t) {
   $t->id(); $t->string('code')->unique(); $t->foreignId('warehouse_id')->constrained()->restrictOnDelete(); $t->foreignId('product_id')->constrained()->restrictOnDelete(); $t->unsignedInteger('quantity'); $t->timestamps();
  });
  Schema::create('adjustment_reasons', function (Blueprint $t) {
   $t->id(); $t->string('code')->unique(); $t->string('name'); $t->string('type'); $t->boolean('is_active')->default(true); $t->timestamps(); $t->index(['type', 'is_active']);
  });
  Schema::create('inventory_adjustments', function (Blueprint $t) {
   $t->id(); $t->foreignId('batch_id')->constrained()->restrictOnDelete(); $t->foreignId('reason_id')->constrained('adjustment_reasons')->restrictOnDelete(); $t->unsignedInteger('old_quantity'); $t->unsignedInteger('new_quantity'); $t->integer('quantity_difference'); $t->text('note')->nullable(); $t->timestamps();
  });
 }
 public function down(): void { foreach (['inventory_adjustments', 'adjustment_reasons', 'batches', 'products', 'warehouses'] as $table) Schema::dropIfExists($table); }
};

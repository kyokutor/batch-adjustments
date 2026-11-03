<?php
namespace Database\Seeders;
use App\Models\{AdjustmentReason, Batch, Product, Warehouse};
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
 public function run(): void {
  foreach (['Main Warehouse', 'Overflow Warehouse'] as $i => $name) {
   $warehouse = Warehouse::firstOrCreate(['name' => $name]);
   $product = Product::firstOrCreate(['sku' => 'DEMO-'.($i + 1)], ['name' => ['Widget', 'Gadget'][$i]]);
   Batch::firstOrCreate(['code' => 'BATCH-00'.($i + 1)], ['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'quantity' => [100, 50][$i]]);
  }
  foreach (['physical-count' => 'Physical count correction', 'damaged' => 'Damaged items', 'missing' => 'Missing items', 'data-entry' => 'Data entry correction'] as $code => $name) {
   AdjustmentReason::firstOrCreate(['code' => $code], ['name' => $name, 'type' => AdjustmentReason::INVENTORY, 'is_active' => true]);
  }
  AdjustmentReason::firstOrCreate(['code' => 'retired'], ['name' => 'Retired reason', 'type' => AdjustmentReason::INVENTORY, 'is_active' => false]);
  AdjustmentReason::firstOrCreate(['code' => 'return'], ['name' => 'Customer return', 'type' => 'customer_return', 'is_active' => true]);
 }
}

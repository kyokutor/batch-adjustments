<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Batch extends Model
{
 protected $fillable = ['code', 'warehouse_id', 'product_id', 'quantity'];
 public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
 public function product(): BelongsTo { return $this->belongsTo(Product::class); }
 public function adjustments(): HasMany { return $this->hasMany(InventoryAdjustment::class); }
 protected function casts(): array { return ['quantity' => 'integer']; }
}

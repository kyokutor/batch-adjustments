<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class AdjustmentReason extends Model
{
 protected $fillable = ['code', 'name', 'type', 'is_active'];
 public const INVENTORY = 'inventory_adjustment';
 public function adjustments(): HasMany { return $this->hasMany(InventoryAdjustment::class, 'reason_id'); }
 protected function casts(): array { return ['is_active' => 'boolean']; }
}

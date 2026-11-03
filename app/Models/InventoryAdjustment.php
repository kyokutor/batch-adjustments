<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class InventoryAdjustment extends Model
{
 protected $fillable = ['batch_id', 'reason_id', 'old_quantity', 'new_quantity', 'quantity_difference', 'note'];
 public function batch(): BelongsTo { return $this->belongsTo(Batch::class); }
 public function reason(): BelongsTo { return $this->belongsTo(AdjustmentReason::class, 'reason_id'); }
 protected function casts(): array { return ['old_quantity' => 'integer', 'new_quantity' => 'integer', 'quantity_difference' => 'integer']; }
}

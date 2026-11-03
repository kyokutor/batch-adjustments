<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class InventoryAdjustmentResource extends JsonResource {
 public function toArray(Request $request): array {
  return [
   'id' => $this->id, 'old_quantity' => $this->old_quantity, 'new_quantity' => $this->new_quantity, 'quantity_difference' => $this->quantity_difference, 'note' => $this->note, 'created_at' => $this->created_at->toISOString(),
   'reason' => new AdjustmentReasonResource($this->reason),
   'batch' => ['id' => $this->batch->id, 'code' => $this->batch->code, 'quantity' => $this->batch->quantity,
    'product' => ['id' => $this->batch->product->id, 'sku' => $this->batch->product->sku, 'name' => $this->batch->product->name],
    'warehouse' => ['id' => $this->batch->warehouse->id, 'name' => $this->batch->warehouse->name]],
  ];
 }
}

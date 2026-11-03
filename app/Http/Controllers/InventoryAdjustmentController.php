<?php
namespace App\Http\Controllers;
use App\Http\Requests\StoreInventoryAdjustmentRequest;
use App\Http\Resources\InventoryAdjustmentResource;
use App\Models\{AdjustmentReason, Batch, InventoryAdjustment};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class InventoryAdjustmentController extends Controller {
 public function store(StoreInventoryAdjustmentRequest $request) {
  $data = $request->validated();
  $adjustment = DB::transaction(function () use ($data) {
   $batch = Batch::lockForUpdate()->find($data['batch_id']);
   if (! $batch) throw ValidationException::withMessages(['batch_id' => 'The selected batch no longer exists.']);
   // Recheck under a lock: the reason may change after request validation.
   $reason = AdjustmentReason::lockForUpdate()->find($data['reason_id']);
   if (! $reason || ! $reason->is_active || $reason->type !== AdjustmentReason::INVENTORY) {
    throw ValidationException::withMessages(['reason_id' => 'Choose an active inventory adjustment reason.']);
   }
   $newQuantity = (int) $data['new_quantity'];
   $adjustment = $batch->adjustments()->create([
    'reason_id' => $reason->id, 'old_quantity' => $batch->quantity,
    'new_quantity' => $newQuantity, 'quantity_difference' => $newQuantity - $batch->quantity,
    'note' => $data['note'] ?? null,
   ]);
   $batch->update(['quantity' => $newQuantity]);
   return $adjustment->load(['batch.product', 'batch.warehouse', 'reason']);
  }, 3);
  return (new InventoryAdjustmentResource($adjustment))->response()->setStatusCode(201);
 }
 public function show(InventoryAdjustment $inventoryAdjustment): InventoryAdjustmentResource {
  return new InventoryAdjustmentResource($inventoryAdjustment->load(['batch.product', 'batch.warehouse', 'reason']));
 }
}

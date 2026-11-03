<?php
use App\Http\Controllers\{AdjustmentReasonController, InventoryAdjustmentController};
use Illuminate\Support\Facades\Route;
Route::get('adjustment-reasons', [AdjustmentReasonController::class, 'index']);
Route::post('inventory-adjustments', [InventoryAdjustmentController::class, 'store']);
Route::get('inventory-adjustments/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'show']);

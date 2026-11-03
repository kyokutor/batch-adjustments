<?php
namespace App\Http\Controllers;
use App\Http\Resources\AdjustmentReasonResource;
use App\Models\AdjustmentReason;
class AdjustmentReasonController extends Controller {
 public function index() { return AdjustmentReasonResource::collection(AdjustmentReason::where('is_active', true)->where('type', AdjustmentReason::INVENTORY)->orderBy('id')->get()); }
}

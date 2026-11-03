<?php
namespace App\Http\Requests;
use App\Models\AdjustmentReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreInventoryAdjustmentRequest extends FormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array {
  return [
   'batch_id' => ['required', 'integer', 'exists:batches,id'],
   'reason_id' => ['required', 'integer', Rule::exists('adjustment_reasons', 'id')->where('is_active', true)->where('type', AdjustmentReason::INVENTORY)],
   'new_quantity' => ['required', 'integer', 'min:0', 'max:2147483647'],
   'note' => ['nullable', 'string', 'max:2000'],
  ];
 }
 public function messages(): array { return ['reason_id.exists' => 'Choose an active inventory adjustment reason.', 'batch_id.exists' => 'The selected batch does not exist.']; }
}

<?php

namespace Tests\Feature;

use App\Models\{AdjustmentReason, Batch, InventoryAdjustment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'batch_id' => Batch::where('code', 'BATCH-001')->value('id'),
            'reason_id' => AdjustmentReason::where('code', 'physical-count')->value('id'),
            'new_quantity' => 92,
            'note' => 'Counted on shelf A.',
        ], $overrides);
    }

    public function test_lists_only_active_inventory_reasons(): void
    {
        $this->getJson('/api/adjustment-reasons')->assertOk()->assertJsonCount(4, 'data')
            ->assertJsonMissing(['code' => 'retired'])->assertJsonMissing(['code' => 'return']);
    }

    public function test_records_and_shows_adjustment_and_related_data(): void
    {
        $response = $this->postJson('/api/inventory-adjustments', $this->payload())
            ->assertCreated()->assertJsonPath('data.old_quantity', 100)
            ->assertJsonPath('data.new_quantity', 92)->assertJsonPath('data.quantity_difference', -8)
            ->assertJsonPath('data.batch.quantity', 92)->assertJsonPath('data.note', 'Counted on shelf A.');
        $this->assertDatabaseHas('batches', ['code' => 'BATCH-001', 'quantity' => 92]);
        $this->getJson('/api/inventory-adjustments/'.$response->json('data.id'))->assertOk()
            ->assertJsonPath('data.batch.product.sku', 'DEMO-1')
            ->assertJsonPath('data.batch.warehouse.name', 'Main Warehouse')
            ->assertJsonPath('data.reason.code', 'physical-count');
    }

    public function test_invalid_input_does_not_change_stock_or_create_history(): void
    {
        $cases = [
            ['batch_id', 999999], ['reason_id', 999999],
            ['reason_id', AdjustmentReason::where('code', 'retired')->value('id')],
            ['reason_id', AdjustmentReason::where('code', 'return')->value('id')],
            ['new_quantity', -1], ['new_quantity', 1.5], ['new_quantity', null],
            ['new_quantity', 2147483648], ['note', str_repeat('x', 2001)],
        ];
        foreach ($cases as [$field, $value]) {
            $this->postJson('/api/inventory-adjustments', $this->payload([$field => $value]))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertDatabaseCount('inventory_adjustments', 0);
        $this->assertDatabaseHas('batches', ['code' => 'BATCH-001', 'quantity' => 100]);
    }

    public function test_zero_increase_and_unchanged_counts_use_latest_stock(): void
    {
        foreach ([[0, 100, -100], [10, 0, 10], [10, 10, 0]] as [$new, $old, $difference]) {
            $this->postJson('/api/inventory-adjustments', $this->payload(['new_quantity' => $new, 'note' => null]))
                ->assertCreated()->assertJsonPath('data.old_quantity', $old)
                ->assertJsonPath('data.quantity_difference', $difference);
        }
        $this->assertDatabaseCount('inventory_adjustments', 3);
    }

    public function test_failure_rolls_back_stock_and_history(): void
    {
        // Fail after the audit INSERT, while the stock UPDATE is being saved.
        Batch::updating(function () { throw new \RuntimeException('Simulated storage failure'); });
        try {
            $this->postJson('/api/inventory-adjustments', $this->payload())->assertStatus(500);
        } finally {
            Batch::flushEventListeners();
        }
        $this->assertDatabaseCount('inventory_adjustments', 0);
        $this->assertDatabaseHas('batches', ['code' => 'BATCH-001', 'quantity' => 100]);
    }

    public function test_missing_adjustment_returns_json_404(): void
    {
        $this->getJson('/api/inventory-adjustments/999999')->assertNotFound();
    }

    public function test_reseeding_preserves_adjusted_stock(): void
    {
        $this->postJson('/api/inventory-adjustments', $this->payload())->assertCreated();
        $this->seed();
        $this->assertDatabaseHas('batches', ['code' => 'BATCH-001', 'quantity' => 92]);
        $this->assertDatabaseCount('batches', 2);
    }
}

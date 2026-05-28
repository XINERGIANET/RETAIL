<?php

namespace App\Services;

use App\Models\ProductBranch;
use App\Models\StockAlert;

class StockAlertService
{
    /**
     * Checks stock for a product+branch after a stock-reducing event.
     * Creates or updates an alert if stock <= stock_minimum.
     * Resolves any existing alert if stock > stock_minimum.
     */
    public function evaluate(int $productId, int $branchId): void
    {
        $pb = ProductBranch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        if (! $pb) {
            return;
        }

        $stock   = (int) $pb->stock;
        $minimum = (int) $pb->stock_minimum;

        if ($stock <= $minimum) {
            StockAlert::updateOrCreate(
                ['product_id' => $productId, 'branch_id' => $branchId],
                ['current_stock' => $stock, 'stock_minimum' => $minimum, 'resolved_at' => null]
            );
        } else {
            StockAlert::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->whereNull('resolved_at')
                ->update(['resolved_at' => now()]);
        }
    }

    /**
     * Evaluates multiple product+branch pairs at once.
     *
     * @param array<array{product_id: int, branch_id: int}> $pairs
     */
    public function evaluateMany(array $pairs): void
    {
        foreach ($pairs as $pair) {
            $this->evaluate((int) $pair['product_id'], (int) $pair['branch_id']);
        }
    }
}

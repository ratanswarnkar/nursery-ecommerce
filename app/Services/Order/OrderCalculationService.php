<?php

namespace App\Services\Order;

use App\Models\ProductVariant;
use App\Models\TaxRule;

class OrderCalculationService
{
    /**
     * Calculate line item breakdown (unit price, subtotal, tax, total) using BCMath.
     */
    public function calculateLine(
        ProductVariant $variant,
        int $quantity,
        ?string $destinationCountry = 'IN',
        ?string $destinationState = null
    ): array {
        $variant->loadMissing('product.taxClass.taxRules.taxRate');
        $product = $variant->product;

        $unitPrice = (string) $variant->price;
        $lineSubtotal = bcmul($unitPrice, (string) $quantity, 2);

        // Resolve line tax via product TaxClass and matching active TaxRules
        $taxRatePercentage = $this->resolveTaxRate($product?->tax_class_id, $destinationCountry, $destinationState);
        $lineTax = '0.00';

        if (bccomp($taxRatePercentage, '0.00', 2) > 0) {
            $taxMultiplier = bcdiv($taxRatePercentage, '100.00', 4);
            $lineTax = bcmul($lineSubtotal, $taxMultiplier, 2);
        }

        $lineDiscount = '0.00';
        $lineTotal = bcsub(bcadd($lineSubtotal, $lineTax, 2), $lineDiscount, 2);

        return [
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $lineSubtotal,
            'tax_rate' => $taxRatePercentage,
            'tax_amount' => $lineTax,
            'discount_amount' => $lineDiscount,
            'total' => $lineTotal,
        ];
    }

    /**
     * Resolve effective tax rate percentage for a given tax class and destination.
     */
    public function resolveTaxRate(?int $taxClassId, ?string $country = 'IN', ?string $state = null): string
    {
        if (! $taxClassId) {
            return '0.00';
        }

        $ruleQuery = TaxRule::where('tax_class_id', $taxClassId)
            ->where('is_active', true)
            ->whereHas('taxRate', fn ($q) => $q->where('is_active', true));

        if ($state) {
            $stateRule = (clone $ruleQuery)
                ->where('state', $state)
                ->orderByDesc('priority')
                ->first();

            if ($stateRule && $stateRule->taxRate) {
                return (string) $stateRule->taxRate->rate;
            }
        }

        $normalizedCountries = match (strtoupper((string) $country)) {
            'IN', 'INDIA', '' => ['IN', 'India'],
            default => array_filter([$country]),
        };

        $countryRule = (clone $ruleQuery)
            ->where(function ($q) use ($normalizedCountries) {
                if (! empty($normalizedCountries)) {
                    $q->whereIn('country', $normalizedCountries);
                }
                $q->orWhereNull('country');
            })
            ->orderByDesc('priority')
            ->first();

        if ($countryRule && $countryRule->taxRate) {
            return (string) $countryRule->taxRate->rate;
        }

        return '0.00';
    }

    /**
     * Explicit calculation boundary for shipping charges.
     * In Phase 6.1, defaults to 0.00 without hardcoding business assumptions.
     */
    public function calculateShipping(string $subtotal, ?array $shippingAddress = null): string
    {
        // Phase 6.1 boundary: Default ₹0.00 shipping until business rules are finalized
        return config('ecommerce.shipping.flat_rate', '0.00');
    }

    /**
     * Explicit calculation boundary for discounts.
     * In Phase 6.1, defaults to 0.00 without inventing marketing rules.
     */
    public function calculateDiscount(string $subtotal, ?string $couponCode = null): string
    {
        // Phase 6.1 boundary: Default ₹0.00 discount
        return '0.00';
    }

    /**
     * Calculate entire order pricing breakdown deterministically.
     *
     * @param  array<array{variant: ProductVariant, quantity: int}>  $items
     */
    public function calculateOrder(array $items, ?array $shippingAddress = null, ?string $couponCode = null): array
    {
        $country = $shippingAddress['country'] ?? 'IN';
        $state = $shippingAddress['state'] ?? null;

        $orderSubtotal = '0.00';
        $orderTax = '0.00';
        $calculatedLines = [];

        foreach ($items as $item) {
            /** @var ProductVariant $variant */
            $variant = $item['variant'];
            $quantity = (int) $item['quantity'];

            $lineCalc = $this->calculateLine($variant, $quantity, $country, $state);

            $orderSubtotal = bcadd($orderSubtotal, $lineCalc['subtotal'], 2);
            $orderTax = bcadd($orderTax, $lineCalc['tax_amount'], 2);

            $calculatedLines[] = array_merge($lineCalc, [
                'variant' => $variant,
            ]);
        }

        $shippingAmount = $this->calculateShipping($orderSubtotal, $shippingAddress);
        $discountAmount = $this->calculateDiscount($orderSubtotal, $couponCode);

        // grand_total = subtotal + tax + shipping - discount
        $grandTotal = bcadd($orderSubtotal, $orderTax, 2);
        $grandTotal = bcadd($grandTotal, $shippingAmount, 2);
        $grandTotal = bcsub($grandTotal, $discountAmount, 2);

        if (bccomp($grandTotal, '0.00', 2) < 0) {
            $grandTotal = '0.00';
        }

        return [
            'lines' => $calculatedLines,
            'subtotal' => $orderSubtotal,
            'tax_amount' => $orderTax,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'grand_total' => $grandTotal,
        ];
    }
}

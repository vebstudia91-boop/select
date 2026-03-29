<?php

namespace App\Services;

class CalculatorService
{
    /**
     * Calculate rental cost for equipment
     */
    public function calculateEquipmentCost(
        float $basePrice,
        int $quantity,
        int $period,
        string $periodUnit,
        float $discount = 0.0
    ): array {
        $baseTotal = $basePrice * $quantity * $period;
        
        // Apply period-based discounts
        $periodDiscount = $this->getPeriodDiscount($period, $periodUnit);
        
        // Calculate total discount
        $totalDiscountPercent = min($discount + $periodDiscount, 50); // Max 50% discount
        
        $discountAmount = $baseTotal * ($totalDiscountPercent / 100);
        $finalAmount = $baseTotal - $discountAmount;
        
        return [
            'base_price' => $basePrice,
            'quantity' => $quantity,
            'period' => $period,
            'period_unit' => $periodUnit,
            'base_total' => round($baseTotal, 2),
            'period_discount_percent' => round($periodDiscount, 2),
            'custom_discount_percent' => round($discount, 2),
            'total_discount_percent' => round($totalDiscountPercent, 2),
            'discount_amount' => round($discountAmount, 2),
            'final_amount' => round($finalAmount, 2)
        ];
    }
    
    /**
     * Get automatic discount based on rental period
     */
    private function getPeriodDiscount(int $period, string $unit): float
    {
        // Convert everything to days for calculation
        $days = match($unit) {
            'week' => $period * 7,
            'month' => $period * 30,
            default => $period
        };
        
        if ($days >= 90) { // 3+ months
            return 20.0;
        } elseif ($days >= 30) { // 1+ month
            return 15.0;
        } elseif ($days >= 14) { // 2+ weeks
            return 10.0;
        } elseif ($days >= 7) { // 1+ week
            return 5.0;
        }
        
        return 0.0;
    }
    
    /**
     * Calculate order total with additional services
     */
    public function calculateOrderTotal(
        array $equipmentItems,
        array $additionalServices,
        float $globalDiscount = 0.0
    ): array {
        $equipmentSubtotal = 0.0;
        $servicesSubtotal = 0.0;
        $breakdown = [];
        
        // Calculate equipment costs
        foreach ($equipmentItems as $item) {
            $calculation = $this->calculateEquipmentCost(
                $item['price'],
                $item['quantity'] ?? 1,
                $item['period'],
                $item['period_unit'] ?? 'day',
                $item['discount'] ?? 0.0
            );
            
            $equipmentSubtotal += $calculation['final_amount'];
            $breakdown['equipment'][] = [
                'name' => $item['name'],
                'calculation' => $calculation
            ];
        }
        
        // Calculate additional services
        foreach ($additionalServices as $service) {
            $serviceTotal = $service['price'] * ($service['quantity'] ?? 1);
            $servicesSubtotal += $serviceTotal;
            $breakdown['services'][] = [
                'name' => $service['name'],
                'price' => $service['price'],
                'quantity' => $service['quantity'] ?? 1,
                'subtotal' => round($serviceTotal, 2)
            ];
        }
        
        $subtotal = $equipmentSubtotal + $servicesSubtotal;
        $discountAmount = $subtotal * ($globalDiscount / 100);
        $total = $subtotal - $discountAmount;
        
        return [
            'equipment_subtotal' => round($equipmentSubtotal, 2),
            'services_subtotal' => round($servicesSubtotal, 2),
            'subtotal' => round($subtotal, 2),
            'global_discount_percent' => round($globalDiscount, 2),
            'global_discount_amount' => round($discountAmount, 2),
            'total' => round($total, 2),
            'breakdown' => $breakdown
        ];
    }
    
    /**
     * Compare rental periods and suggest optimal pricing
     */
    public function suggestOptimalPeriod(float $basePrice, int $requestedDays): array
    {
        $dailyRate = $basePrice * $requestedDays;
        $weeklyRate = ceil($requestedDays / 7) * ($basePrice * 7 * 0.95); // 5% weekly discount
        $monthlyRate = ceil($requestedDays / 30) * ($basePrice * 30 * 0.85); // 15% monthly discount
        
        $options = [
            ['type' => 'daily', 'days' => $requestedDays, 'price' => round($dailyRate, 2)],
            ['type' => 'weekly', 'weeks' => ceil($requestedDays / 7), 'price' => round($weeklyRate, 2)],
            ['type' => 'monthly', 'months' => ceil($requestedDays / 30), 'price' => round($monthlyRate, 2)]
        ];
        
        $cheapest = min(array_column($options, 'price'));
        $bestOption = null;
        
        foreach ($options as &$option) {
            $option['is_best'] = ($option['price'] == $cheapest);
            $option['savings'] = round($dailyRate - $option['price'], 2);
            if ($option['is_best']) {
                $bestOption = $option['type'];
            }
        }
        
        return [
            'requested_days' => $requestedDays,
            'options' => $options,
            'best_option' => $bestOption,
            'max_savings' => round($dailyRate - $cheapest, 2)
        ];
    }
    
    /**
     * Calculate delivery cost based on distance
     */
    public function calculateDeliveryCost(
        float $basePrice,
        float $distance,
        float $pricePerKm = 50.0
    ): float {
        $deliveryCost = $basePrice + ($distance * $pricePerKm);
        return round($deliveryCost, 2);
    }
    
    /**
     * Generate quote summary
     */
    public function generateQuoteSummary(array $orderData): string
    {
        $summary = "Коммерческое предложение\n";
        $summary .= str_repeat("=", 50) . "\n\n";
        
        if (isset($orderData['client'])) {
            $summary .= "Клиент: {$orderData['client']}\n";
        }
        
        if (isset($orderData['period'])) {
            $summary .= "Период аренды: {$orderData['period']} дн.\n";
        }
        
        $summary .= "\nОборудование:\n";
        $summary .= str_repeat("-", 30) . "\n";
        
        if (isset($orderData['equipment'])) {
            foreach ($orderData['equipment'] as $item) {
                $summary .= "- {$item['name']}: " . number_format($item['price'], 2, ',', ' ') . " руб.\n";
            }
        }
        
        if (isset($orderData['total'])) {
            $summary .= "\n" . str_repeat("=", 50) . "\n";
            $summary .= "Итого: " . number_format($orderData['total'], 2, ',', ' ') . " руб.\n";
        }
        
        return $summary;
    }
}

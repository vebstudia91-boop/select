<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CalculatorService;

/**
 * Unit tests for CalculatorService
 */
class CalculatorServiceTest extends TestCase
{
    private CalculatorService $calculator;
    
    protected function setUp(): void
    {
        $this->calculator = new CalculatorService();
    }
    
    /**
     * Test basic equipment cost calculation
     */
    public function testCalculateEquipmentCostBasic(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 1,
            period: 5,
            periodUnit: 'day',
            discount: 0.0
        );
        
        $this->assertEquals(1000.00, $result['base_price']);
        $this->assertEquals(1, $result['quantity']);
        $this->assertEquals(5, $result['period']);
        $this->assertEquals('day', $result['period_unit']);
        $this->assertEquals(5000.00, $result['base_total']);
        $this->assertEquals(5000.00, $result['final_amount']);
    }
    
    /**
     * Test equipment cost with quantity
     */
    public function testCalculateEquipmentCostWithQuantity(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 3,
            period: 5,
            periodUnit: 'day',
            discount: 0.0
        );
        
        $this->assertEquals(15000.00, $result['base_total']);
        $this->assertEquals(15000.00, $result['final_amount']);
    }
    
    /**
     * Test weekly period discount (5% for 7+ days)
     */
    public function testCalculateEquipmentCostWeeklyDiscount(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 1,
            period: 7,
            periodUnit: 'day',
            discount: 0.0
        );
        
        $this->assertEquals(5.0, $result['period_discount_percent']);
        $this->assertEquals(7000.00, $result['base_total']);
        $this->assertEquals(350.00, $result['discount_amount']);
        $this->assertEquals(6650.00, $result['final_amount']);
    }
    
    /**
     * Test bi-weekly period discount (10% for 14+ days)
     */
    public function testCalculateEquipmentCostBiWeeklyDiscount(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 1,
            period: 14,
            periodUnit: 'day',
            discount: 0.0
        );
        
        $this->assertEquals(10.0, $result['period_discount_percent']);
        $this->assertEquals(14000.00, $result['base_total']);
        $this->assertEquals(1400.00, $result['discount_amount']);
        $this->assertEquals(12600.00, $result['final_amount']);
    }
    
    /**
     * Test monthly period discount (15% for 30+ days)
     */
    public function testCalculateEquipmentCostMonthlyDiscount(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 1,
            period: 30,
            periodUnit: 'day',
            discount: 0.0
        );
        
        $this->assertEquals(15.0, $result['period_discount_percent']);
        $this->assertEquals(30000.00, $result['base_total']);
        $this->assertEquals(4500.00, $result['discount_amount']);
        $this->assertEquals(25500.00, $result['final_amount']);
    }
    
    /**
     * Test long-term period discount (20% for 90+ days)
     */
    public function testCalculateEquipmentCostLongTermDiscount(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 1,
            period: 90,
            periodUnit: 'day',
            discount: 0.0
        );
        
        $this->assertEquals(20.0, $result['period_discount_percent']);
        $this->assertEquals(90000.00, $result['base_total']);
        $this->assertEquals(18000.00, $result['discount_amount']);
        $this->assertEquals(72000.00, $result['final_amount']);
    }
    
    /**
     * Test custom discount application
     */
    public function testCalculateEquipmentCostWithCustomDiscount(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 1,
            period: 5,
            periodUnit: 'day',
            discount: 10.0
        );
        
        $this->assertEquals(10.0, $result['custom_discount_percent']);
        $this->assertEquals(5000.00, $result['base_total']);
        $this->assertEquals(500.00, $result['discount_amount']);
        $this->assertEquals(4500.00, $result['final_amount']);
    }
    
    /**
     * Test combined period and custom discount
     */
    public function testCalculateEquipmentCostCombinedDiscounts(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 1,
            period: 7,
            periodUnit: 'day',
            discount: 5.0
        );
        
        // 5% period discount + 5% custom discount = 10% total
        $this->assertEquals(10.0, $result['total_discount_percent']);
        $this->assertEquals(7000.00, $result['base_total']);
        $this->assertEquals(700.00, $result['discount_amount']);
        $this->assertEquals(6300.00, $result['final_amount']);
    }
    
    /**
     * Test maximum discount cap (50%)
     */
    public function testCalculateEquipmentCostMaxDiscountCap(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 1,
            period: 90,
            periodUnit: 'day',
            discount: 40.0
        );
        
        // 20% period + 40% custom = 60%, but capped at 50%
        $this->assertEquals(50.0, $result['total_discount_percent']);
        $this->assertEquals(90000.00, $result['base_total']);
        $this->assertEquals(45000.00, $result['discount_amount']);
        $this->assertEquals(45000.00, $result['final_amount']);
    }
    
    /**
     * Test weekly unit conversion
     */
    public function testCalculateEquipmentCostWeeklyUnit(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 1,
            period: 2,
            periodUnit: 'week',
            discount: 0.0
        );
        
        // 2 weeks = 14 days, should get 10% discount
        $this->assertEquals(10.0, $result['period_discount_percent']);
        $this->assertEquals(14000.00, $result['base_total']);
    }
    
    /**
     * Test monthly unit conversion
     */
    public function testCalculateEquipmentCostMonthlyUnit(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 1,
            period: 1,
            periodUnit: 'month',
            discount: 0.0
        );
        
        // 1 month = 30 days, should get 15% discount
        $this->assertEquals(15.0, $result['period_discount_percent']);
        $this->assertEquals(30000.00, $result['base_total']);
    }
    
    /**
     * Test order total calculation
     */
    public function testCalculateOrderTotal(): void
    {
        $equipmentItems = [
            ['name' => 'Кран 25т', 'price' => 15000.00, 'quantity' => 1, 'period' => 5, 'period_unit' => 'day'],
            ['name' => 'Вышка 18м', 'price' => 8000.00, 'quantity' => 2, 'period' => 5, 'period_unit' => 'day']
        ];
        
        $additionalServices = [
            ['name' => 'Доставка', 'price' => 5000.00, 'quantity' => 1]
        ];
        
        $result = $this->calculator->calculateOrderTotal(
            equipmentItems: $equipmentItems,
            additionalServices: $additionalServices,
            globalDiscount: 0.0
        );
        
        // Equipment: (15000 * 5) + (8000 * 2 * 5) = 75000 + 80000 = 155000
        // Services: 5000
        // Total: 160000
        $this->assertEquals(155000.00, $result['equipment_subtotal']);
        $this->assertEquals(5000.00, $result['services_subtotal']);
        $this->assertEquals(160000.00, $result['subtotal']);
        $this->assertEquals(160000.00, $result['total']);
    }
    
    /**
     * Test order total with global discount
     */
    public function testCalculateOrderTotalWithGlobalDiscount(): void
    {
        $equipmentItems = [
            ['name' => 'Кран 25т', 'price' => 10000.00, 'quantity' => 1, 'period' => 10, 'period_unit' => 'day']
        ];
        
        $additionalServices = [];
        
        $result = $this->calculator->calculateOrderTotal(
            equipmentItems: $equipmentItems,
            additionalServices: $additionalServices,
            globalDiscount: 10.0
        );
        
        // Equipment: 10000 * 10 = 100000 (with 5% period discount = 95000)
        // Global discount: 10% of 95000 = 9500
        // Total: 85500
        $this->assertLessThan(100000.00, $result['total']);
    }
    
    /**
     * Test optimal period suggestion
     */
    public function testSuggestOptimalPeriod(): void
    {
        $result = $this->calculator->suggestOptimalPeriod(
            basePrice: 1000.00,
            requestedDays: 20
        );
        
        $this->assertEquals(20, $result['requested_days']);
        $this->assertCount(3, $result['options']);
        $this->assertNotNull($result['best_option']);
        $this->assertGreaterThan(0, $result['max_savings']);
        
        // Monthly should be cheapest for 20 days
        $this->assertEquals('monthly', $result['best_option']);
    }
    
    /**
     * Test delivery cost calculation
     */
    public function testCalculateDeliveryCost(): void
    {
        $result = $this->calculator->calculateDeliveryCost(
            basePrice: 5000.00,
            distance: 50.0,
            pricePerKm: 50.0
        );
        
        // 5000 + (50 * 50) = 7500
        $this->assertEquals(7500.00, $result);
    }
    
    /**
     * Test delivery cost with zero distance
     */
    public function testCalculateDeliveryCostZeroDistance(): void
    {
        $result = $this->calculator->calculateDeliveryCost(
            basePrice: 5000.00,
            distance: 0.0,
            pricePerKm: 50.0
        );
        
        $this->assertEquals(5000.00, $result);
    }
    
    /**
     * Test quote summary generation
     */
    public function testGenerateQuoteSummary(): void
    {
        $orderData = [
            'client' => 'ООО СтройМонтаж',
            'period' => 10,
            'equipment' => [
                ['name' => 'Кран 25т', 'price' => 15000.00],
                ['name' => 'Вышка 18м', 'price' => 8000.00]
            ],
            'total' => 230000.00
        ];
        
        $summary = $this->calculator->generateQuoteSummary($orderData);
        
        $this->assertStringContainsString('Коммерческое предложение', $summary);
        $this->assertStringContainsString('ООО СтройМонтаж', $summary);
        $this->assertStringContainsString('Кран 25т', $summary);
        $this->assertStringContainsString('Вышка 18м', $summary);
        $this->assertStringContainsString('230 000,00', $summary);
    }
    
    /**
     * Test edge case: zero period
     */
    public function testCalculateEquipmentCostZeroPeriod(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 1,
            period: 0,
            periodUnit: 'day',
            discount: 0.0
        );
        
        $this->assertEquals(0.00, $result['base_total']);
        $this->assertEquals(0.00, $result['final_amount']);
    }
    
    /**
     * Test edge case: very large period
     */
    public function testCalculateEquipmentCostLargePeriod(): void
    {
        $result = $this->calculator->calculateEquipmentCost(
            basePrice: 1000.00,
            quantity: 1,
            period: 365,
            periodUnit: 'day',
            discount: 0.0
        );
        
        // Should still cap at 20% period discount
        $this->assertEquals(20.0, $result['period_discount_percent']);
        $this->assertEquals(365000.00, $result['base_total']);
        $this->assertEquals(292000.00, $result['final_amount']);
    }
}

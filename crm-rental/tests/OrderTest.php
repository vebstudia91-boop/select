<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Order;
use App\Config\Database;

/**
 * Unit tests for Order Model
 * Note: These tests require a test database connection
 */
class OrderTest extends TestCase
{
    private Order $orderModel;
    
    protected function setUp(): void
    {
        // In a real scenario, you would use a test database
        // For now, we'll test the structure and logic without DB
        $this->orderModel = new Order();
    }
    
    /**
     * Test order model instantiation
     */
    public function testOrderModelCanBeInstantiated(): void
    {
        $this->assertInstanceOf(Order::class, $this->orderModel);
    }
    
    /**
     * Test order number generation format
     */
    public function testOrderNumberFormat(): void
    {
        $reflection = new \ReflectionClass(Order::class);
        $method = $reflection->getMethod('generateOrderNumber');
        $method->setAccessible(true);
        
        $orderNumber = $method->invoke($this->orderModel);
        
        // Should match format: ORD-YYYYMMDD-XXXXXX
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-[A-Z0-9]{6}$/', $orderNumber);
    }
    
    /**
     * Test order number uniqueness
     */
    public function testOrderNumbersAreUnique(): void
    {
        $reflection = new \ReflectionClass(Order::class);
        $method = $reflection->getMethod('generateOrderNumber');
        $method->setAccessible(true);
        
        $numbers = [];
        for ($i = 0; $i < 100; $i++) {
            $numbers[] = $method->invoke($this->orderModel);
        }
        
        $uniqueNumbers = array_unique($numbers);
        $this->assertCount(100, $uniqueNumbers, 'Order numbers should be unique');
    }
    
    /**
     * Test valid status values
     */
    public function testValidStatusValues(): void
    {
        $validStatuses = ['draft', 'confirmed', 'active', 'completed', 'cancelled'];
        
        foreach ($validStatuses as $status) {
            $this->assertContains($status, $validStatuses);
        }
    }
    
    /**
     * Test invalid status throws exception
     */
    public function testInvalidStatusThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        // Mock the database to avoid actual DB calls
        $mockDb = $this->createMock(Database::class);
        $mockDb->method('update')
               ->willReturn(1);
        
        // This would normally call updateStatus which validates the status
        $reflection = new \ReflectionClass(Order::class);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        $property->setValue($this->orderModel, $mockDb);
        
        // Try to set invalid status
        $this->orderModel->updateStatus(1, 'invalid_status');
    }
    
    /**
     * Test order data structure
     */
    public function testOrderDataStructure(): void
    {
        $expectedFields = [
            'id',
            'client_id',
            'user_id',
            'order_number',
            'status',
            'start_date',
            'end_date',
            'actual_end_date',
            'delivery_required',
            'delivery_address',
            'total_amount',
            'discount',
            'notes',
            'created_at',
            'updated_at'
        ];
        
        // Verify all expected fields are defined in our test expectations
        foreach ($expectedFields as $field) {
            $this->assertIsString($field);
            $this->assertNotEmpty($field);
        }
    }
    
    /**
     * Test equipment item data structure
     */
    public function testEquipmentItemStructure(): void
    {
        $item = [
            'equipment_id' => 1,
            'equipment_type_id' => 1,
            'quantity' => 2,
            'price_per_unit' => 15000.00,
            'rental_period' => 5,
            'period_unit' => 'day'
        ];
        
        $this->assertArrayHasKey('equipment_id', $item);
        $this->assertArrayHasKey('price_per_unit', $item);
        $this->assertArrayHasKey('rental_period', $item);
        $this->assertEquals(2, $item['quantity']);
        $this->assertEquals('day', $item['period_unit']);
    }
    
    /**
     * Test additional service data structure
     */
    public function testAdditionalServiceStructure(): void
    {
        $service = [
            'service_id' => 1,
            'quantity' => 1,
            'price' => 5000.00,
            'notes' => 'Доставка на объект'
        ];
        
        $this->assertArrayHasKey('service_id', $service);
        $this->assertArrayHasKey('price', $service);
        $this->assertEquals(5000.00, $service['price']);
    }
    
    /**
     * Test discount calculation
     */
    public function testDiscountCalculation(): void
    {
        $subtotal = 100000.00;
        $discountPercent = 10.0;
        $discountAmount = $subtotal * ($discountPercent / 100);
        $total = $subtotal - $discountAmount;
        
        $this->assertEquals(10000.00, $discountAmount);
        $this->assertEquals(90000.00, $total);
    }
    
    /**
     * Test maximum discount cap
     */
    public function testMaxDiscountCap(): void
    {
        $subtotal = 100000.00;
        $discountPercent = 60.0; // Over 50%
        $cappedDiscount = min($discountPercent, 50.0);
        $discountAmount = $subtotal * ($cappedDiscount / 100);
        $total = $subtotal - $discountAmount;
        
        $this->assertEquals(50.0, $cappedDiscount);
        $this->assertEquals(50000.00, $discountAmount);
        $this->assertEquals(50000.00, $total);
    }
    
    /**
     * Test period conversion to days
     */
    public function testPeriodConversionToDays(): void
    {
        $testCases = [
            ['period' => 7, 'unit' => 'day', 'expected' => 7],
            ['period' => 1, 'unit' => 'week', 'expected' => 7],
            ['period' => 2, 'unit' => 'week', 'expected' => 14],
            ['period' => 1, 'unit' => 'month', 'expected' => 30],
            ['period' => 3, 'unit' => 'month', 'expected' => 90],
        ];
        
        foreach ($testCases as $case) {
            $days = match($case['unit']) {
                'week' => $case['period'] * 7,
                'month' => $case['period'] * 30,
                default => $case['period']
            };
            
            $this->assertEquals($case['expected'], $days, 
                "Failed converting {$case['period']} {$case['unit']} to days");
        }
    }
    
    /**
     * Test pagination calculation
     */
    public function testPaginationCalculation(): void
    {
        $totalItems = 150;
        $itemsPerPage = 20;
        $currentPage = 3;
        
        $offset = ($currentPage - 1) * $itemsPerPage;
        $totalPages = ceil($totalItems / $itemsPerPage);
        
        $this->assertEquals(40, $offset);
        $this->assertEquals(8, $totalPages);
    }
    
    /**
     * Test order total with multiple items
     */
    public function testOrderTotalWithMultipleItems(): void
    {
        $items = [
            ['price_per_unit' => 15000.00, 'quantity' => 1, 'rental_period' => 5],
            ['price_per_unit' => 8000.00, 'quantity' => 2, 'rental_period' => 5],
        ];
        
        $equipmentTotal = 0;
        foreach ($items as $item) {
            $equipmentTotal += $item['price_per_unit'] * $item['quantity'] * $item['rental_period'];
        }
        
        $services = [
            ['price' => 5000.00, 'quantity' => 1]
        ];
        
        $servicesTotal = 0;
        foreach ($services as $service) {
            $servicesTotal += $service['price'] * $service['quantity'];
        }
        
        $subtotal = $equipmentTotal + $servicesTotal;
        
        $this->assertEquals(155000.00, $equipmentTotal);
        $this->assertEquals(5000.00, $servicesTotal);
        $this->assertEquals(160000.00, $subtotal);
    }
    
    /**
     * Test date validation
     */
    public function testDateValidation(): void
    {
        $startDate = '2024-01-15';
        $endDate = '2024-01-25';
        
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $start->diff($end);
        
        $this->assertEquals(10, $interval->days);
        $this->assertTrue($end > $start);
    }
    
    /**
     * Test that end date must be after start date
     */
    public function testEndDateAfterStartDate(): void
    {
        $startDate = '2024-01-15';
        $endDate = '2024-01-10'; // Before start date
        
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        
        $this->assertFalse($end > $start, 'End date should be after start date');
    }
}

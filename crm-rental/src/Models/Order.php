<?php

namespace App\Models;

use App\Config\Database;

class Order
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create new order
     */
    public function create(array $data): int
    {
        $orderData = [
            'client_id' => $data['client_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'order_number' => $this->generateOrderNumber(),
            'status' => 'draft',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'delivery_required' => $data['delivery_required'] ?? false,
            'delivery_address' => $data['delivery_address'] ?? null,
            'total_amount' => 0.00,
            'discount' => $data['discount'] ?? 0.00,
            'notes' => $data['notes'] ?? null
        ];
        
        $orderId = $this->db->insert('orders', $orderData);
        
        // Add order items
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $this->addItem($orderId, $item);
            }
        }
        
        // Update total amount
        $this->updateTotal($orderId);
        
        return $orderId;
    }
    
    /**
     * Add item to order
     */
    public function addItem(int $orderId, array $item): int
    {
        $itemData = [
            'order_id' => $orderId,
            'equipment_id' => $item['equipment_id'] ?? null,
            'equipment_type_id' => $item['equipment_type_id'] ?? null,
            'quantity' => $item['quantity'] ?? 1,
            'price_per_unit' => $item['price_per_unit'],
            'rental_period' => $item['rental_period'],
            'period_unit' => $item['period_unit'] ?? 'day',
            'subtotal' => ($item['price_per_unit'] * $item['rental_period'] * ($item['quantity'] ?? 1))
        ];
        
        return $this->db->insert('order_items', $itemData);
    }
    
    /**
     * Add additional service to order
     */
    public function addService(int $orderId, array $service): int
    {
        $serviceData = [
            'order_id' => $orderId,
            'service_id' => $service['service_id'],
            'quantity' => $service['quantity'] ?? 1,
            'price' => $service['price'],
            'subtotal' => ($service['price'] * ($service['quantity'] ?? 1)),
            'notes' => $service['notes'] ?? null
        ];
        
        return $this->db->insert('order_additional_services', $serviceData);
    }
    
    /**
     * Update order total
     */
    public function updateTotal(int $orderId): void
    {
        $this->db->beginTransaction();
        
        try {
            // Calculate equipment total
            $equipmentSql = "SELECT COALESCE(SUM(subtotal), 0) as total FROM order_items WHERE order_id = ?";
            $equipmentTotal = (float) $this->db->fetchOne($equipmentSql, [$orderId])['total'];
            
            // Calculate services total
            $servicesSql = "SELECT COALESCE(SUM(subtotal), 0) as total FROM order_additional_services WHERE order_id = ?";
            $servicesTotal = (float) $this->db->fetchOne($servicesSql, [$orderId])['total'];
            
            $subtotal = $equipmentTotal + $servicesTotal;
            
            // Get discount
            $order = $this->getById($orderId);
            $discountAmount = $subtotal * ($order['discount'] / 100);
            $finalTotal = $subtotal - $discountAmount;
            
            // Update order
            $this->db->update('orders', ['total_amount' => $finalTotal], 'id = ?', [$orderId]);
            
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get order by ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM orders WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Get order with items and services
     */
    public function getOrderDetails(int $id): ?array
    {
        $order = $this->getById($id);
        if (!$order) {
            return null;
        }
        
        // Get items
        $itemsSql = "SELECT oi.*, et.name as equipment_name 
                     FROM order_items oi 
                     LEFT JOIN equipment_types et ON oi.equipment_type_id = et.id 
                     WHERE oi.order_id = ?";
        $order['items'] = $this->db->fetchAll($itemsSql, [$id]);
        
        // Get services
        $servicesSql = "SELECT oas.*, asrv.name as service_name 
                        FROM order_additional_services oas 
                        LEFT JOIN additional_services asrv ON oas.service_id = asrv.id 
                        WHERE oas.order_id = ?";
        $order['services'] = $this->db->fetchAll($servicesSql, [$id]);
        
        // Get client info
        if ($order['client_id']) {
            $clientSql = "SELECT * FROM clients WHERE id = ?";
            $order['client'] = $this->db->fetchOne($clientSql, [$order['client_id']]);
        }
        
        return $order;
    }
    
    /**
     * Update order status
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        $validStatuses = ['draft', 'confirmed', 'active', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }
        
        return $this->db->update('orders', ['status' => $status], 'id = ?', [$orderId]) > 0;
    }
    
    /**
     * Get all orders with pagination
     */
    public function getAll(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];
        
        if (isset($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['client_id'])) {
            $where[] = "client_id = ?";
            $params[] = $filters['client_id'];
        }
        
        if (isset($filters['date_from'])) {
            $where[] = "start_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $where[] = "start_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT o.*, c.company_name as client_name 
                FROM orders o 
                LEFT JOIN clients c ON o.client_id = c.id 
                {$whereClause}
                ORDER BY o.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $orders = $this->db->fetchAll($sql, $params);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM orders {$whereClause}";
        $total = (int) $this->db->fetchOne($countSql, array_slice($params, 0, count($params) - 2))['total'];
        
        return [
            'data' => $orders,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }
    
    /**
     * Generate unique order number
     */
    private function generateOrderNumber(): string
    {
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        return "ORD-{$date}-{$random}";
    }
    
    /**
     * Cancel order
     */
    public function cancel(int $orderId, string $reason = null): bool
    {
        $this->db->beginTransaction();
        
        try {
            // Update order status
            $this->updateStatus($orderId, 'cancelled');
            
            // Free up equipment
            $itemsSql = "SELECT equipment_id FROM order_items WHERE order_id = ? AND equipment_id IS NOT NULL";
            $items = $this->db->fetchAll($itemsSql, [$orderId]);
            
            foreach ($items as $item) {
                $this->db->update('equipment', ['status' => 'available'], 'id = ?', [$item['equipment_id']]);
            }
            
            // Log cancellation
            if ($reason) {
                $this->db->update('orders', ['notes' => "Cancelled: {$reason}"], 'id = ?', [$orderId]);
            }
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

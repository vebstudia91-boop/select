-- Database configuration for Construction Equipment Rental CRM

CREATE DATABASE IF NOT EXISTS construction_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE construction_rental;

-- Users table (employees)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'accountant') DEFAULT 'manager',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Equipment categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Equipment types (cranes, towers, cabins, etc.)
CREATE TABLE equipment_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    unit ENUM('day', 'week', 'month') DEFAULT 'day',
    specifications JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Equipment inventory
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_type_id INT,
    inventory_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('available', 'rented', 'maintenance', 'reserved') DEFAULT 'available',
    location VARCHAR(255),
    purchase_date DATE,
    last_maintenance DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(id) ON DELETE CASCADE
);

-- Clients
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(200) NOT NULL,
    inn VARCHAR(20),
    kpp VARCHAR(20),
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders/Rentals
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    user_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('draft', 'confirmed', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    start_date DATE NOT NULL,
    end_date DATE,
    actual_end_date DATE,
    delivery_required BOOLEAN DEFAULT FALSE,
    delivery_address TEXT,
    total_amount DECIMAL(12, 2) DEFAULT 0.00,
    discount DECIMAL(5, 2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items (equipment in order)
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    equipment_id INT,
    equipment_type_id INT,
    quantity INT DEFAULT 1,
    price_per_unit DECIMAL(10, 2) NOT NULL,
    rental_period INT NOT NULL,
    period_unit ENUM('day', 'week', 'month') DEFAULT 'day',
    subtotal DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE SET NULL,
    FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(id) ON DELETE SET NULL
);

-- Additional services (delivery, assembly, etc.)
CREATE TABLE additional_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    unit ENUM('once', 'day', 'km') DEFAULT 'once',
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Order additional services
CREATE TABLE order_additional_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    service_id INT,
    quantity INT DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES additional_services(id) ON DELETE SET NULL
);

-- Payments
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    amount DECIMAL(12, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'card') DEFAULT 'bank_transfer',
    document_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Calculator presets (for quick calculations)
CREATE TABLE calculator_presets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    preset_data JSON NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Activity log
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default data
INSERT INTO categories (name, description) VALUES
('Краны', 'Аренда автомобильных кранов'),
('Вышки', 'Аренда подъемных вышек'),
('Бытовки', 'Аренда строительных бытовок'),
('Спецтехника', 'Другая спецтехника');

INSERT INTO equipment_types (category_id, name, base_price, unit, specifications) VALUES
(1, 'Кран авто 25т', 15000.00, 'day', '{"load_capacity": "25t", "boom_length": "30m"}'),
(1, 'Кран авто 50т', 25000.00, 'day', '{"load_capacity": "50t", "boom_length": "45m"}'),
(2, 'Вышка 18м', 8000.00, 'day', '{"height": "18m", "type": "автовышка"}'),
(2, 'Вышка 22м', 10000.00, 'day', '{"height": "22m", "type": "автовышка"}'),
(3, 'Бытовка строительная', 500.00, 'day', '{"size": "6x2.3m", "type": "стандарт"}'),
(3, 'Бытовка офисная', 700.00, 'day', '{"size": "6x2.3m", "type": "офис"}'),
(4, 'Экскаватор', 12000.00, 'day', '{"weight": "20t", "bucket": "0.8m³"}');

INSERT INTO additional_services (name, price, unit, description) VALUES
('Доставка техники', 5000.00, 'once', 'Доставка техники на объект'),
('Монтаж/Демонтаж', 3000.00, 'once', 'Услуги по монтажу и демонтажу'),
('Работа оператора', 5000.00, 'day', 'Услуги оператора техники'),
('Топливо', 2000.00, 'day', 'Расход топлива');

INSERT INTO users (name, email, password, role) VALUES
('Администратор', 'admin@crm.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

# CRM для аренды строительной техники

Система управления арендой кранов, вышек, бытовок и другой спецтехники.

## 📋 Функционал

- **Управление оборудованием**: каталог техники с категориями
- **Заказы и аренда**: оформление заказов, отслеживание статусов
- **Калькулятор стоимости**: автоматический расчет с учетом скидок
- **Клиенты**: база данных клиентов и контрагентов
- **Дополнительные услуги**: доставка, монтаж, операторы
- **Отчетность**: история заказов и платежей

## 🏗️ Структура проекта

```
crm-rental/
├── public/                 # Публичная директория
│   ├── index.php          # Точка входа
│   └── calculator.html    # Калькулятор (HTML+JS)
├── src/
│   ├── Config/
│   │   └── Database.php   # Подключение к БД
│   ├── Models/
│   │   └── Order.php      # Модель заказов
│   └── Services/
│       └── CalculatorService.php  # Сервис расчетов
├── tests/
│   ├── CalculatorServiceTest.php  # Тесты калькулятора
│   └── OrderTest.php              # Тесты модели заказов
├── database/
│   └── migrations/
│       └── 001_initial_schema.sql # Схема БД
├── nginx.conf             # Конфигурация Nginx
└── phpunit.xml           # Конфигурация PHPUnit
```

## 🚀 Быстрый старт

### 1. Требования

- PHP 8.1+
- MySQL 8.0+
- Nginx
- Composer (для зависимостей)
- PHPUnit (для тестов)

### 2. Установка базы данных

```bash
mysql -u root -p < database/migrations/001_initial_schema.sql
```

### 3. Настройка веб-сервера

Скопируйте конфигурацию Nginx:

```bash
sudo cp nginx.conf /etc/nginx/sites-available/crm-rental
sudo ln -s /etc/nginx/sites-available/crm-rental /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 4. Запуск тестов

```bash
# Установка зависимостей
composer install

# Запуск всех тестов
vendor/bin/phpunit

# Запуск конкретных тестов
vendor/bin/phpunit tests/CalculatorServiceTest.php
vendor/bin/phpunit tests/OrderTest.php
```

## 🧮 Калькулятор

Калькулятор поддерживает:

- **Периоды аренды**: дни, недели, месяцы
- **Автоматические скидки**:
  - 7+ дней: 5%
  - 14+ дней: 10%
  - 30+ дней: 15%
  - 90+ дней: 20%
- **Дополнительные услуги**: доставка, монтаж, оператор
- **Рекомендации**: подбор выгодного периода

### Пример использования API калькулятора

```php
use App\Services\CalculatorService;

$calculator = new CalculatorService();

// Расчет стоимости аренды
$result = $calculator->calculateEquipmentCost(
    basePrice: 15000.00,
    quantity: 1,
    period: 30,
    periodUnit: 'day',
    discount: 5.0
);

// Результат:
// [
//     'base_total' => 450000.00,
//     'period_discount_percent' => 15.0,
//     'total_discount_percent' => 20.0,
//     'discount_amount' => 90000.00,
//     'final_amount' => 360000.00
// ]
```

## 📊 Модель данных

### Основные таблицы

- `users` - сотрудники компании
- `clients` - клиенты
- `categories` - категории техники
- `equipment_types` - типы оборудования
- `equipment` - инвентарный учет
- `orders` - заказы
- `order_items` - позиции заказа
- `additional_services` - доп. услуги
- `payments` - платежи

## 🧪 Тестирование

### Покрытие тестами

- **CalculatorServiceTest**: 20+ тестов
  - Расчет базовой стоимости
  - Применение скидок по периодам
  - Комбинированные скидки
  - Оптимальный выбор периода
  - Расчет доставки

- **OrderTest**: 15+ тестов
  - Генерация номеров заказов
  - Валидация статусов
  - Расчет итоговых сумм
  - Пагинация
  - Валидация дат

### Запуск тестов с покрытием

```bash
vendor/bin/phpunit --coverage-html coverage/
```

## 🔐 Безопасность

- Prepared statements для всех SQL-запросов
- Валидация входных данных
- Защита от XSS и CSRF
- Ролевая модель доступа

## 📝 Лицензия

Проект создан для демонстрации архитектуры CRM системы.

## 🤝 Поддержка

Для вопросов и предложений создавайте Issues в репозитории.

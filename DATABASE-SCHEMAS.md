# Database Schemas Reference

## Core Tables Structure

### Users Table
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'vendor', 'customer') NOT NULL DEFAULT 'customer',
    is_active BOOLEAN DEFAULT true,
    phone VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    postal_code VARCHAR(20),
    avatar VARCHAR(255),
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_role_active (role, is_active),
    INDEX idx_email_verified (email, email_verified_at)
);
```

### Vehicle Categories Table
```sql
CREATE TABLE vehicle_categories (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_active_sort (is_active, sort_order)
);
```

### Vehicles Table
```sql
CREATE TABLE vehicles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    vendor_id BIGINT NOT NULL,
    category_id BIGINT NOT NULL,
    brand VARCHAR(255) NOT NULL,
    model VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    color VARCHAR(100),
    license_plate VARCHAR(50) UNIQUE,
    vin VARCHAR(17) UNIQUE,
    mileage INT DEFAULT 0,
    seating_capacity INT DEFAULT 5,
    transmission ENUM('manual', 'automatic') DEFAULT 'manual',
    fuel_type ENUM('petrol', 'diesel', 'electric', 'hybrid') DEFAULT 'petrol',
    price_per_day DECIMAL(10,2) NOT NULL,
    weekly_discount DECIMAL(5,2) DEFAULT 0.00,
    monthly_discount DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('available', 'rented', 'maintenance', 'inactive') DEFAULT 'available',
    featured BOOLEAN DEFAULT false,
    description TEXT,
    features JSON,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES vehicle_categories(id) ON DELETE CASCADE,

    INDEX idx_vendor_status (vendor_id, status),
    INDEX idx_category_featured (category_id, featured),
    INDEX idx_status_featured (status, featured),
    INDEX idx_price_range (price_per_day),
    INDEX idx_location (latitude, longitude),
    INDEX idx_year_mileage (year, mileage)
);
```

### Vehicle Images Table
```sql
CREATE TABLE vehicle_images (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id BIGINT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    image_type ENUM('primary', 'gallery', 'interior', 'exterior', 'damage_before', 'damage_after') DEFAULT 'gallery',
    sort_order INT DEFAULT 0,
    alt_text VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,

    INDEX idx_vehicle_type_sort (vehicle_id, image_type, sort_order),
    INDEX idx_vehicle_primary (vehicle_id, image_type) WHERE image_type = 'primary'
);
```

### Bookings Table
```sql
CREATE TABLE bookings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    vendor_id BIGINT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_intent_id VARCHAR(255),
    pickup_location VARCHAR(255),
    dropoff_location VARCHAR(255),
    pickup_time TIME,
    dropoff_time TIME,
    driver_name VARCHAR(255),
    driver_license VARCHAR(255),
    driver_phone VARCHAR(50),
    notes TEXT,
    addons JSON,
    damage_protection BOOLEAN DEFAULT false,
    damage_deposit DECIMAL(10,2) DEFAULT 0.00,
    damage_notes_before TEXT,
    damage_notes_after TEXT,
    damage_images_before JSON,
    damage_images_after JSON,
    confirmed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE,

    CHECK (end_date > start_date),
    CHECK (total_price >= 0),
    UNIQUE KEY unique_vehicle_booking_dates (vehicle_id, start_date, end_date),

    INDEX idx_user_status (user_id, status),
    INDEX idx_vehicle_dates (vehicle_id, start_date, end_date),
    INDEX idx_vendor_status (vendor_id, status),
    INDEX idx_dates_range (start_date, end_date),
    INDEX idx_payment_status (payment_status),
    INDEX idx_status_dates (status, created_at)
);
```

### Messages Table
```sql
CREATE TABLE messages (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    booking_id BIGINT NOT NULL,
    sender_id BIGINT NOT NULL,
    receiver_id BIGINT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT false,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_booking_created (booking_id, created_at),
    INDEX idx_receiver_unread (receiver_id, is_read),
    INDEX idx_sender_created (sender_id, created_at)
);
```

### Documents Table
```sql
CREATE TABLE documents (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    document_type ENUM('license', 'passport', 'id_card', 'insurance', 'registration', 'other') NOT NULL,
    document_path VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    expires_at DATE NULL,
    verified_at TIMESTAMP NULL,
    verified_by BIGINT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_user_type_status (user_id, document_type, status),
    INDEX idx_status_created (status, created_at),
    INDEX idx_expires_at (expires_at)
);
```

### Reviews Table
```sql
CREATE TABLE reviews (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    booking_id BIGINT NOT NULL,
    vehicle_id BIGINT NOT NULL,
    reviewer_id BIGINT NOT NULL,
    reviewee_id BIGINT NOT NULL,
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT,
    response TEXT NULL,
    responded_at TIMESTAMP NULL,
    is_public BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,

    UNIQUE KEY unique_booking_review (booking_id),
    INDEX idx_vehicle_rating (vehicle_id, rating),
    INDEX idx_reviewee_rating (reviewee_id, rating),
    INDEX idx_public_created (is_public, created_at)
);
```

### Settings Table
```sql
CREATE TABLE settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) UNIQUE NOT NULL,
    value JSON,
    description TEXT,
    is_public BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_key_public (key, is_public)
);
```

## Relationships Summary

### User Relationships
- `hasMany` `Vehicle` (as vendor)
- `hasMany` `Booking` (as customer)
- `hasMany` `Message` (as sender and receiver)
- `hasMany` `Document`
- `hasMany` `Review` (as reviewer and reviewee)

### Vehicle Relationships
- `belongsTo` `User` (vendor)
- `belongsTo` `VehicleCategory`
- `hasMany` `VehicleImage`
- `hasMany` `Booking`
- `hasMany` `Review`
- `hasMany` `Document` (polymorphic)

### Booking Relationships
- `belongsTo` `Vehicle`
- `belongsTo` `User` (customer)
- `belongsTo` `User` (vendor)
- `hasMany` `Message`
- `hasOne` `Review`
- `hasMany` `Document` (polymorphic)

## Performance Indexes Strategy

### Composite Indexes for Common Queries
```sql
-- Vehicle search filters
CREATE INDEX idx_vehicles_search ON vehicles(status, featured, category_id, price_per_day);

-- Booking availability checks
CREATE INDEX idx_bookings_availability ON bookings(vehicle_id, status, start_date, end_date);

-- User dashboard queries
CREATE INDEX idx_bookings_user_dashboard ON bookings(user_id, status, created_at DESC);

-- Vendor management queries
CREATE INDEX idx_bookings_vendor_management ON bookings(vendor_id, status, start_date);

-- Message threading
CREATE INDEX idx_messages_conversation ON messages(booking_id, created_at);
```

### Partial Indexes for Specific Conditions
```sql
-- Active vendors only
CREATE INDEX idx_active_vendors ON users(id, created_at) WHERE role = 'vendor' AND is_active = 1;

-- Available vehicles
CREATE INDEX idx_available_vehicles ON vehicles(id, price_per_day) WHERE status = 'available';

-- Unread messages
CREATE INDEX idx_unread_messages ON messages(receiver_id, created_at) WHERE is_read = 0;
```

---

*This file contains detailed database schemas. For essential database patterns, see the main CLAUDE.md file.*
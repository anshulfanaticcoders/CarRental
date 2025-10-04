# API Reference Documentation

## Authentication Endpoints

### POST /api/auth/login
Login user and return access token.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password",
  "remember_me": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "role": "customer"
    },
    "token": "1|abc123...",
    "expires_at": "2024-01-01T12:00:00Z"
  }
}
```

### POST /api/auth/register
Register new user account.

**Request:**
```json
{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "password",
  "password_confirmation": "password",
  "role": "customer"
}
```

### POST /api/auth/logout
Logout user and invalidate token.

### POST /api/auth/refresh
Refresh access token.

## Vehicle Endpoints

### GET /api/vehicles
Get paginated list of vehicles with optional filters.

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 15, max: 50)
- `category_id` (int): Filter by category
- `min_price` (decimal): Minimum daily price
- `max_price` (decimal): Maximum daily price
- `brand` (string): Filter by brand
- `featured` (boolean): Filter featured vehicles only
- `available_from` (date): Available from date
- `available_to` (date): Available to date
- `latitude` (decimal): Search latitude
- `longitude` (decimal): Search longitude
- `radius` (int): Search radius in km (default: 50)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "brand": "Toyota",
      "model": "Camry",
      "year": 2022,
      "price_per_day": 45.00,
      "category": {
        "id": 1,
        "name": "Sedan"
      },
      "images": [
        {
          "id": 1,
          "image_path": "vehicles/image1.jpg",
          "image_type": "primary"
        }
      ],
      "vendor": {
        "id": 2,
        "name": "Car Rental Co",
        "rating": 4.5
      },
      "features": ["GPS", "Bluetooth", "USB"],
      "location": {
        "latitude": 40.7128,
        "longitude": -74.0060
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150,
    "from": 1,
    "to": 15
  }
}
```

### GET /api/vehicles/{id}
Get detailed information about a specific vehicle.

### POST /api/vehicles
Create new vehicle (vendor only).

**Request:**
```json
{
  "category_id": 1,
  "brand": "Toyota",
  "model": "Camry",
  "year": 2022,
  "color": "Silver",
  "license_plate": "ABC123",
  "vin": "1HGBH41JXMN109186",
  "mileage": 15000,
  "seating_capacity": 5,
  "transmission": "automatic",
  "fuel_type": "petrol",
  "price_per_day": 45.00,
  "weekly_discount": 10.00,
  "monthly_discount": 20.00,
  "description": "Well-maintained sedan",
  "features": ["GPS", "Bluetooth", "USB"],
  "latitude": 40.7128,
  "longitude": -74.0060,
  "images": [
    {
      "image": "base64_encoded_image_data",
      "image_type": "primary"
    }
  ]
}
```

### PUT /api/vehicles/{id}
Update vehicle information (vendor only).

### DELETE /api/vehicles/{id}
Delete vehicle (vendor only).

## Booking Endpoints

### GET /api/bookings
Get user's bookings with pagination and filtering.

**Query Parameters:**
- `status` (string): Filter by status (pending, confirmed, completed, cancelled)
- `page` (int): Page number
- `per_page` (int): Items per page
- `start_date_from` (date): Filter by start date
- `start_date_to` (date): Filter by start date

### POST /api/bookings
Create new booking.

**Request:**
```json
{
  "vehicle_id": 1,
  "start_date": "2024-01-15",
  "end_date": "2024-01-20",
  "pickup_location": "Airport Terminal 1",
  "dropoff_location": "Airport Terminal 1",
  "pickup_time": "10:00",
  "dropoff_time": "10:00",
  "driver_name": "John Doe",
  "driver_license": "D123456789",
  "driver_phone": "+1234567890",
  "notes": "Early arrival preferred",
  "addons": [
    {
      "id": 1,
      "quantity": 1
    }
  ],
  "damage_protection": true,
  "payment_method_id": "pm_1234567890"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "booking_reference": "BK-2024-00123",
    "vehicle": {
      "id": 1,
      "brand": "Toyota",
      "model": "Camry"
    },
    "start_date": "2024-01-15",
    "end_date": "2024-01-20",
    "total_price": 275.00,
    "status": "pending",
    "payment_status": "pending",
    "client_secret": "pi_1234567890_secret_abc123"
  }
}
```

### GET /api/bookings/{id}
Get detailed booking information.

### PUT /api/bookings/{id}
Update booking information.

### POST /api/bookings/{id}/confirm
Confirm booking and process payment.

### POST /api/bookings/{id}/cancel
Cancel booking with optional reason.

**Request:**
```json
{
  "reason": "Change of plans",
  "refund_requested": true
}
```

## Payment Endpoints

### POST /api/payments/process
Process payment for booking.

**Request:**
```json
{
  "booking_id": 123,
  "payment_method_id": "pm_1234567890",
  "save_payment_method": false
}
```

### POST /api/payments/refund
Request refund for booking.

**Request:**
```json
{
  "booking_id": 123,
  "reason": "Service not as described",
  "amount": 275.00
}
```

### GET /api/payments/methods
Get saved payment methods for user.

### POST /api/payments/methods
Save new payment method.

## Message Endpoints

### GET /api/bookings/{booking_id}/messages
Get messages for a booking.

**Query Parameters:**
- `page` (int): Page number
- `per_page` (int): Items per page

### POST /api/bookings/{booking_id}/messages
Send new message.

**Request:**
```json
{
  "message": "Is early pickup possible?",
  "attachments": [
    {
      "file": "base64_encoded_file_data",
      "name": "document.pdf",
      "type": "application/pdf"
    }
  ]
}
```

### PUT /api/messages/{id}/read
Mark message as read.

## Review Endpoints

### GET /api/vehicles/{vehicle_id}/reviews
Get reviews for a vehicle.

### POST /api/bookings/{booking_id}/review
Create review for completed booking.

**Request:**
```json
{
  "rating": 4.5,
  "review": "Great experience, vehicle was clean and well-maintained",
  "is_public": true
}
```

### PUT /api/reviews/{id}/response
Add response to review (vendor only).

## Document Endpoints

### GET /api/documents
Get user's documents.

### POST /api/documents
Upload new document.

**Request:**
```json
{
  "document_type": "license",
  "document": "base64_encoded_file_data",
  "original_name": "license.jpg",
  "expires_at": "2025-12-31"
}
```

### PUT /api/documents/{id}
Update document information.

### DELETE /api/documents/{id}
Delete document.

## Admin Endpoints

### GET /api/admin/dashboard
Get dashboard statistics.

### GET /api/admin/users
Get all users with filtering and pagination.

### PUT /api/admin/users/{id}/verify
Verify user documents.

### GET /api/admin/bookings
Get all bookings with admin-level filtering.

### PUT /api/admin/bookings/{id}/status
Update booking status (admin override).

## External API Integration

### GreenMotion API Integration

#### GET /api/external/greenmotion/vehicles
Get available vehicles from GreenMotion.

**Query Parameters:**
- `pickup_location` (string): Pickup location code
- `dropoff_location` (string): Dropoff location code
- `pickup_date` (date): Pickup date
- `dropoff_date` (date): Dropoff date
- `pickup_time` (time): Pickup time
- `driver_age` (int): Driver age

### U-Save API Integration

#### GET /api/external/usave/vehicles
Get available vehicles from U-Save.

#### POST /api/external/usave/bookings
Create booking with U-Save.

## Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  },
  "timestamp": "2024-01-01T12:00:00Z"
}
```

### HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Internal Server Error

## Rate Limiting

API endpoints are rate-limited:
- **Authentication endpoints**: 5 requests per minute
- **Search endpoints**: 60 requests per minute
- **Booking endpoints**: 30 requests per minute
- **Other endpoints**: 100 requests per minute

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
```

---

*This file contains detailed API documentation. For essential API patterns, see the main CLAUDE.md file.*
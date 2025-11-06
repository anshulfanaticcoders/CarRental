# eSIM Access Integration Guide

## Overview
This guide explains the eSIM Access integration implemented in the car rental platform.

## Architecture

### Backend Components

1. **EsimAccessService** (`app/Services/EsimAccessService.php`)
   - Handles all communication with eSIM Access API
   - Methods:
     - `getCountries()`: Fetches available countries
     - `getPlansByCountry($countryCode)`: Fetches plans for a specific country
     - `createOrder($orderData)`: Creates eSIM order
     - `getOrderDetails($orderId)`: Retrieves order details

2. **EsimController** (`app/Http/Controllers/EsimController.php`)
   - Handles HTTP requests for eSIM functionality
   - Endpoints:
     - `GET /{locale}/api/esim/countries`: Get available countries
     - `GET /{locale}/api/esim/plans/{countryCode}`: Get plans for country
     - `POST /{locale}/api/esim/order`: Create order and redirect to Stripe
     - `GET /{locale}/esim/success`: Payment success callback
     - `GET /{locale}/esim/cancel`: Payment cancellation

### Frontend Components

1. **EsimSection** (`resources/js/Components/EsimSection.vue`)
   - Vue component for eSIM purchase interface
   - Features:
     - Country and plan selection dropdowns
     - Customer information form
     - Real-time validation
     - Stripe checkout integration

## Configuration

### Environment Variables
Add these to your `.env` file:
```
ESIM_ACCESS_API_KEY=your_api_key_here
ESIM_ACCESS_API_URL=https://api.esimaccess.com/v1
ESIM_ACCESS_MARKUP_PERCENTAGE=20
```

### Services Configuration
The eSIM Access service is configured in `config/services.php`:
```php
'esim_access' => [
    'api_key' => env('ESIM_ACCESS_API_KEY'),
    'base_url' => env('ESIM_ACCESS_API_URL'),
    'markup_percentage' => env('ESIM_ACCESS_MARKUP_PERCENTAGE', 20),
],
```

## User Flow

1. **Country Selection**: User selects destination country
2. **Plan Selection**: User selects data plan based on country
3. **Customer Info**: User enters name and email
4. **Payment**: User is redirected to Stripe for secure payment
5. **Success**: After payment, eSIM order is created and user receives email
6. **eSIM Delivery**: eSIM details are sent via email

## API Response Format

### Countries Response
```json
{
    "success": true,
    "data": [
        {
            "code": "US",
            "name": "United States"
        }
    ]
}
```

### Plans Response
```json
{
    "success": true,
    "data": [
        {
            "id": "plan123",
            "name": "USA Traveler",
            "data_amount": "5GB",
            "validity": "30 days",
            "price": 29.99,
            "currency": "USD"
        }
    ]
}
```

## Testing

### Manual Testing Steps:

1. **Start Development Server**:
   ```bash
   php artisan serve
   npm run dev
   ```

2. **Test API Endpoints**:
   ```bash
   # Test countries endpoint
   curl http://localhost:8000/en/api/esim/countries

   # Test plans endpoint (replace US with valid country code)
   curl http://localhost:8000/en/api/esim/plans/US
   ```

3. **Test Frontend**:
   - Visit homepage (`http://localhost:8000/en`)
   - Scroll to "Stay Connected" section
   - Select country and plan
   - Fill in customer details
   - Click "Get eSIM Now" button

### Unit Tests:
Run the test suite:
```bash
php artisan test tests/Feature/EsimAccessTest.php
```

## Troubleshooting

### Common Issues:

1. **API Errors**:
   - Check eSIM Access API key in `.env`
   - Verify API endpoint URL is correct
   - Check API response format

2. **Payment Issues**:
   - Verify Stripe keys are configured
   - Check webhook endpoints
   - Ensure currency codes are valid

3. **Frontend Issues**:
   - Check Vue component imports
   - Verify API endpoints are accessible
   - Check browser console for JavaScript errors

### Debug Mode:
Enable debug mode in `.env`:
```
APP_DEBUG=true
```

This will provide detailed error messages.

## Revenue Model

- **Platform Fee**: $19/month (paid to eSIM Access)
- **Markup**: Default 20% markup on eSIM prices (configurable)
- **Payment Processing**: Stripe fees apply
- **Profit**: Markup amount minus platform and processing fees

## Future Enhancements

1. **Customer Dashboard**: View purchased eSIMs
2. **Order History**: Track eSIM purchases
3. **Package Deals**: Bundle eSIM with car rentals
4. **Analytics**: Sales tracking and reporting
5. **Multi-currency**: Support for different currencies
6. **Mobile App**: Native app for eSIM management

## Support

For eSIM Access API issues, contact: [eSIM Access Support](https://esimaccess.com)
For platform issues, check Laravel logs and Stripe dashboard.
# Invoice Payment System Documentation

## Overview

This Laravel-based invoice payment system integrates with MyFatoorah payment gateway to process payments and automatically generates ZATCA-compliant QR codes for paid invoices. The system handles the complete payment lifecycle from invoice display to payment verification and compliance requirements.

## Components

### Controllers

#### InvoiceController
Main controller handling invoice payment workflows.

**Key Methods:**
- `index($id)` - Display invoice payment page with MyFatoorah session initialization
- `paymentProcess(Request $request)` - Process payment requests and generate payment URLs
- `callBack(Request $request)` - Handle payment gateway callbacks and verify payments
- `success(Request $request)` - Display payment success page
- `failed(Request $request)` - Display payment failure page

### Services

#### MyFatoorahPaymentService
Service class implementing the PaymentGatewayInterface for MyFatoorah payment gateway integration.

**Key Methods:**
- `sendPayment(Request $request)` - Initialize payment with MyFatoorah
- `executePayment(array $data)` - Execute payment transaction
- `callBack(Request $request)` - Verify payment status from gateway callback
- `updateInvoiceFromCallback()` - Update invoice status after successful payment

### Events & Listeners

#### InvoicePaidEvent
Event dispatched when an invoice payment is confirmed.

**Properties:**
- `$invoiceId` - ID of the paid invoice

#### GenerateZATCAQR Listener
Handles the InvoicePaidEvent to generate ZATCA-compliant QR codes for Saudi Arabia tax compliance.

**Process:**
- Retrieves invoice and tenant information
- Generates QR code with required ZATCA elements
- Updates invoice with generated QR code

## Payment Flow

1. **Invoice Display** - User accesses invoice via `InvoiceController@index`
2. **Session Initialization** - MyFatoorah session is created
3. **Payment Processing** - User submits payment via `InvoiceController@paymentProcess`
4. **Gateway Redirect** - User is redirected to MyFatoorah payment page
5. **Payment Callback** - Gateway sends callback to `InvoiceController@callBack`
6. **Payment Verification** - System verifies payment status with MyFatoorah
7. **Invoice Update** - Invoice is marked as paid if verification succeeds
8. **Event Dispatch** - `InvoicePaidEvent` is fired
9. **QR Generation** - ZATCA QR code is generated and stored

## Configuration Requirements

### Environment Variables
```env
# MyFatoorah Configuration
MY_FATOORAH_BASE_URL=your_myfatoorah_api_url
MY_FATOORAH_API_KEY=your_api_key
```

### Service Configuration
Add to `config/services.php`:
```php
'my_fatoorah' => [
    'base_url' => env('MY_FATOORAH_BASE_URL'),
    'api_key' => env('MY_FATOORAH_API_KEY'),
],
```

## Dependencies

- **Laravel Framework** - Core framework
- **MyFatoorah API** - Payment gateway integration
- **Salla/ZATCA** - QR code generation for Saudi tax compliance
- **Carbon** - Date manipulation

## Error Handling

The system includes comprehensive error handling:
- **Payment Failures** - Users are redirected to failure page with error messages
- **Invalid Invoices** - 404 errors for non-existent or unpayable invoices
- **Gateway Errors** - Logged and handled gracefully
- **Callback Verification** - Failed verifications are logged and handled

## Logging

All payment operations are logged for audit purposes:
- Payment initialization attempts
- Gateway responses
- Callback processing
- QR code generation
- Error conditions

## Security Considerations

- Payment callbacks include verification with the gateway
- Invoice payment status is checked before processing
- Sensitive payment data is logged securely
- CSRF protection on payment forms
- Proper validation of callback data

## Installation & Setup

### Prerequisites
- PHP 8.3 or higher
- Composer
- MySQL/PostgreSQL database
- Docker (optional)

### Method 1: Using Laravel Sail (Docker)

1. **Clone the repository**
   ```bash
   git clone https://github.com/ahmadjabalid4ds/invoice-system.git
   cd invoice-system
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

4. **Configure environment variables**
   ```bash
   # Database Configuration
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=invoice_system
   DB_USERNAME=sail
   DB_PASSWORD=password

   # MyFatoorah Configuration
   MY_FATOORAH_BASE_URL=https://apitest.myfatoorah.com
   MY_FATOORAH_API_KEY=your_test_api_key

   # Application
   APP_URL=http://localhost
   ```

5. **Start Laravel Sail**
   ```bash
   ./vendor/bin/sail up -d
   ```

6. **Generate application key**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```

7. **Run database migrations**
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

8. **Access the application**
    - Open browser and navigate to `http://localhost`
    - Application will be running on port 80

### Method 2: Without Docker (Local Development)

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd invoice-payment-system
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   ```

4. **Configure database**
   ```bash
   # Update .env file with your local database credentials
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=invoice_system
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   # MyFatoorah Configuration
   MY_FATOORAH_BASE_URL=https://apitest.myfatoorah.com
   MY_FATOORAH_API_KEY=your_test_api_key
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Create database and run migrations**
   ```bash
   # Create database manually in MySQL/PostgreSQL
   php artisan migrate
   ```

7. **Start the development server**
   ```bash
   php artisan serve
   ```

8. **Access the application**
    - Open browser and navigate to `http://localhost:8000`

## Additional Setup Commands

### Queue Workers (Required for Event Processing)
```bash
# With Sail
./vendor/bin/sail artisan queue:work

# Without Docker
php artisan queue:work
```

### Storage Link (for file uploads)
```bash
# With Sail
./vendor/bin/sail artisan storage:link

# Without Docker
php artisan storage:link
```

### Seeding Test Data
```bash
# With Sail
./vendor/bin/sail artisan db:seed

# Without Docker
php artisan db:seed
```

## Development Workflow

### Using Laravel Sail
```bash
# Start services
./vendor/bin/sail up -d

# Run artisan commands
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan queue:work

# Run tests
./vendor/bin/sail test

# Stop services
./vendor/bin/sail down
```

### Without Docker
```bash
# Start development server
php artisan serve

# Run queue worker
php artisan queue:work

# Run tests
php artisan test

```

## Testing

When testing, ensure:
- MyFatoorah sandbox environment is configured
- Test payment methods are available
- Callback URLs are accessible from MyFatoorah servers
- Database has proper test data structure
- Queue workers are running for event processing

### Running Tests
```bash
# With Sail
./vendor/bin/sail test

# Without Docker
php artisan test
```

## Compliance

The system generates ZATCA-compliant QR codes including:
- Seller name
- Tax registration number
- Invoice date (ISO 8601 format)
- Total amount including VAT
- VAT amount

This ensures compliance with Saudi Arabia's ZATCA e-invoicing requirements.

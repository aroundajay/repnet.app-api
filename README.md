# repnet.app API

Laravel 12 API backend for repnet.app. Uses Laravel Actions for single-purpose handlers, Sanctum for auth, and OTP (email/SMS) for login and verification flows.

## Requirements

- PHP 8.2+
- PostgreSQL
- Composer
- Node.js (for frontend assets)

## Setup

1. **Install dependencies:**

    ```bash
    composer install
    npm install
    ```

2. **Configure environment:**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3. **Update `.env` with your settings:**

    ```env
    # Database (PostgreSQL)
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=your_database
    DB_USERNAME=your_username
    DB_PASSWORD=your_password

    # MinIO / S3 Storage
    AWS_ACCESS_KEY_ID=your_access_key
    AWS_SECRET_ACCESS_KEY=your_secret_key
    AWS_DEFAULT_REGION=us-east-1
    AWS_BUCKET=your_bucket
    AWS_ENDPOINT=http://localhost:9000
    AWS_URL=http://localhost:9000/your_bucket
    AWS_USE_PATH_STYLE_ENDPOINT=true
    ```

4. **Run migrations:**

    ```bash
    php artisan migrate
    ```

5. **Start the development server:**

    ```bash
    composer dev
    ```

## API Routes

All API routes are defined in `routes/api.php` and are prefixed with `/api` by Laravel.

### Health

| Method | URI   | Description  | Auth |
| ------ | ----- | ------------ | ---- |
| GET    | `/up` | Health check | No   |

### Public (no auth)

| Method | URI               | Description                      | Auth |
| ------ | ----------------- | -------------------------------- | ---- |
| POST   | `/api/otp/send`   | Send OTP to email or mobile      | No   |
| POST   | `/api/otp/verify` | Verify OTP code                  | No   |
| POST   | `/api/auth/login` | Login with identifier + password | No   |

### Protected (Sanctum)

| Method | URI                           | Description                     | Auth |
| ------ | ----------------------------- | ------------------------------- | ---- |
| POST   | `/api/otp/send-authenticated` | Send OTP for authenticated user | Yes  |
| GET    | `/api/user`                   | Get authenticated user          | Yes  |
| PUT    | `/api/user`                   | Update authenticated user       | Yes  |

### Authentication

This API uses [Laravel Sanctum](https://laravel.com/docs/sanctum) for token-based authentication.

**Token-based authentication:**

```php
// Create a token for a user
$token = $user->createToken('api-token')->plainTextToken;

// Use in requests
Authorization: Bearer {token}
```

**Typical flow:** Use OTP (`/api/otp/send` then `/api/otp/verify`) or password (`/api/auth/login`) to obtain a user/token; then send `Authorization: Bearer {token}` on protected routes.

## Actions

Single-purpose action classes (Laravel Actions) used by the API:

| Action             | Route(s) / usage                                         | Purpose                                               |
| ------------------ | -------------------------------------------------------- | ----------------------------------------------------- |
| `SendOtpAction`    | `POST /api/otp/send`, `POST /api/otp/send-authenticated` | Send OTP via email or SMS; rate-limited, cooldown.    |
| `VerifyOtpAction`  | `POST /api/otp/verify`                                   | Verify OTP code; can create user or run callback.     |
| `LoginAction`      | `POST /api/auth/login`                                   | Login with identifier (email/mobile) and password.    |
| `UpdateUserAction` | `PUT /api/user`                                          | Update name, password (with current_password), email. |
| `CreateUserAction` | Internal only                                            | Create user (e.g. used after OTP verification).       |

Actions live under `app/Actions/` (Auth, Otp, User). They use `OtpService`, `UserService`, and form requests in `app/Http/Requests/Otp/`.

## Packages

- **[Laravel Sanctum](https://laravel.com/docs/sanctum)** – API authentication
- **[Laravel Actions](https://laravelactions.com/)** (lorisleiva/laravel-actions) – Single-purpose action classes
- **[Flysystem S3](https://laravel.com/docs/filesystem#s3-driver-configuration)** (league/flysystem-aws-s3-v3) – S3/MinIO file storage

## Directory Structure

```
app/
├── Actions/              # Single-purpose action classes
│   ├── Auth/             # LoginAction
│   ├── Otp/              # SendOtpAction, VerifyOtpAction
│   └── User/             # CreateUserAction, UpdateUserAction
├── Http/
│   ├── Controllers/
│   └── Requests/
│       └── Otp/          # SendOtpRequest, VerifyOtpRequest
├── Jobs/                 # CleanupExpiredOtpsJob, etc.
├── Models/               # User, Otp, Gym, WorkoutVideo, etc.
├── Notifications/        # OtpNotification
├── Repositories/         # OtpRepository, UserRepository
├── Services/             # OtpService, UserService, SmsService
└── Providers/
config/
├── otp.php               # OTP length, expiry, rate limits
└── ...
routes/
└── api.php               # All API routes (prefix /api)
```

## OTP Configuration

OTP behavior is configured in `config/otp.php`: code length, expiry, max verification attempts, resend cooldown, and lockout. Optional env vars (e.g. `OTP_CODE_LENGTH`, `OTP_EXPIRY_MINUTES`) can override defaults.

## Development Commands

```bash
# Start development server with queue, logs, and vite
composer dev

# Run tests
composer test

# Full setup (install, migrate, build)
composer setup
```

## License

MIT

# repnet.app API

Laravel 12 API backend for repnet.app. Uses Laravel Actions for single-purpose handlers, Sanctum for auth, OTP (email/SMS) for login and verification, and gym management (create gyms, invite users, join requests).

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

| Method | URI                                              | Description                               | Auth |
| ------ | ------------------------------------------------ | ----------------------------------------- | ---- |
| POST   | `/api/otp/send-authenticated`                    | Send OTP for authenticated user           | Yes  |
| GET    | `/api/user`                                      | Get authenticated user                    | Yes  |
| PUT    | `/api/user`                                      | Update authenticated user                 | Yes  |
| POST   | `/api/gyms`                                      | Create gym (caller becomes owner)         | Yes  |
| POST   | `/api/gyms/{gymId}/invite`                       | Invite user to gym (owner/admin)          | Yes  |
| PUT    | `/api/gyms/{gymId}/invite/{userId}/status`       | Accept/reject gym invite                  | Yes  |
| POST   | `/api/gyms/{gymId}/request-join`                 | Request to join gym                       | Yes  |
| PUT    | `/api/gyms/{gymId}/request-join/{userId}/status` | Approve/reject join request (owner/admin) | Yes  |

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

## Gym

Gyms have owners and admins who can invite users or approve join requests. Invitees use `PUT .../invite/{userId}/status` to accept or reject. Join requests use `POST .../request-join`; owners/admins use `PUT .../request-join/{userId}/status` to approve or reject. Roles and validation live in `GymService`, `GymUser` model, and the Gym actions.

## Actions

Single-purpose action classes (Laravel Actions) used by the API:

| Action                        | Route(s) / usage                                         | Purpose                                               |
| ----------------------------- | -------------------------------------------------------- | ----------------------------------------------------- |
| `SendOtpAction`               | `POST /api/otp/send`, `POST /api/otp/send-authenticated` | Send OTP via email or SMS; rate-limited, cooldown.    |
| `VerifyOtpAction`             | `POST /api/otp/verify`                                   | Verify OTP code; can create user or run callback.     |
| `LoginAction`                 | `POST /api/auth/login`                                   | Login with identifier (email/mobile) and password.    |
| `UpdateUserAction`            | `PUT /api/user`                                          | Update name, password (with current_password), email. |
| `CreateUserAction`            | Internal only                                            | Create user (e.g. used after OTP verification).       |
| `CreateGymAction`             | `POST /api/gyms`                                         | Create gym; authenticated user becomes owner.         |
| `InviteGymUserAction`         | `POST /api/gyms/{gymId}/invite`                          | Invite user to gym (owner/admin); optional OTP.       |
| `UpdateGymInviteStatusAction` | `PUT /api/gyms/{gymId}/invite/{userId}/status`           | Invitee accepts or rejects invite.                    |
| `RequestGymJoinAction`        | `POST /api/gyms/{gymId}/request-join`                    | User requests to join gym.                            |
| `UpdateGymJoinAction`         | `PUT /api/gyms/{gymId}/request-join/{userId}/status`     | Owner/admin approves or rejects join request.         |

Actions live under `app/Actions/` (Auth, Gym, Otp, User). They use `OtpService`, `UserService`, `GymService`, and form requests in `app/Http/Requests/Otp/` and `app/Http/Requests/Gym/`.

## Packages

- **[Laravel Sanctum](https://laravel.com/docs/sanctum)** – API authentication
- **[Laravel Actions](https://laravelactions.com/)** (lorisleiva/laravel-actions) – Single-purpose action classes
- **[Flysystem S3](https://laravel.com/docs/filesystem#s3-driver-configuration)** (league/flysystem-aws-s3-v3) – S3/MinIO file storage

## Directory Structure

```
app/
├── Actions/              # Single-purpose action classes
│   ├── Auth/             # LoginAction
│   ├── Gym/              # CreateGymAction, InviteGymUserAction, UpdateGymInviteStatusAction, RequestGymJoinAction, UpdateGymJoinAction
│   ├── Otp/              # SendOtpAction, VerifyOtpAction
│   └── User/             # CreateUserAction, UpdateUserAction
├── Http/
│   ├── Controllers/
│   └── Requests/
│       ├── Gym/          # CreateGymRequest
│       └── Otp/          # SendOtpRequest, VerifyOtpRequest
├── Jobs/                 # CleanupExpiredOtpsJob
├── Models/               # User, Otp, Gym, GymUser, WorkoutType, WorkoutVideo, File, NoticePost, Challenge, ChallengeSubmission, PartnerRequest, MessageThread, Message
├── Notifications/        # OtpNotification
├── Repositories/         # OtpRepository, UserRepository, GymRepository, GymUserRepository
├── Services/             # OtpService, UserService, SmsService, GymService
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

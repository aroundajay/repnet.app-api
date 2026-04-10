# CLAUDE.md — Context for AI assistants and developers

This file gives **working context** for [repnet.app](https://repnet.app) API development. **Read it at the start of substantive work** on this repository. The canonical human-facing overview is [README.md](./README.md); this document extends that with **architecture, conventions, and drift notes**.

---

## Rules for coding agents (required)

Follow these when implementing or changing behavior. If something is ambiguous, **analyze the codebase first** (similar routes, requests, actions, services, repositories) and then implement **only** what was requested.

| Rule | What to do |
| ---- | ---------- |
| **Always validate the request** | Use a dedicated **Form Request** under `app/Http/Requests/` (`rules()`, `authorize()`, messages as needed). Do not skip validation or replace it with loose checks in Actions/Services unless you are matching an **existing** exception in this repo—and prefer fixing toward Form Requests when touching that code. |
| **Follow the layered pipeline** | **Route** (`routes/api.php`) → **Form Request** → **Action** (`App\Actions\...`) → **Service** → **Repository** → **Model**. Keep boundaries clear: HTTP/validation in the request + action entry, business logic in the service, persistence in the repository. |
| **Match existing Action patterns** | Mirror peers in the same domain folder (e.g. `CreateGymAction`, `ListGymShiftAction`): `AsAction`, constructor-injected services, `asController(YourFormRequest $request)` calling `handle(...)` with validated data / auth context, and the same JSON response shape the API already uses. |
| **Modular code** | Small, focused classes and methods; avoid unrelated logic in the same class; reuse existing services/repositories instead of duplicating queries. |
| **Analyze before implementing** | Read related Actions, Requests, Services, Repositories, and provider bindings **before** writing new code. Register new repositories in `RepositoryServiceProvider` and services in `AppServiceProvider` when you add them. |
| **Best practices** | Align with [Laravel](https://laravel.com/docs) conventions (validation, authorization, HTTP verbs, consistent naming) and keep changes readable and testable. |
| **Scope: only what was asked** | Do **not** add extra features, routes, refactors, files, packages, or “while we’re here” changes. **Do not add frontend code** (Vue, React, new Blade views, Vite/NPM changes, etc.) unless the user **explicitly** requested it—this project is the **API**; not a single unsolicited UI component or asset. |

The **Architecture** section below describes the same pipeline in more detail.

---

## Keep this file up to date (required)

**Whenever you change something that would make this document wrong or incomplete, update `CLAUDE.md` in the same change** (same PR / same commit series when possible).

Update `CLAUDE.md` when you:

- Add, remove, or rename **API routes** (`routes/api.php`) or change **auth** (public vs `auth:sanctum`).
- Add **Actions**, **Form Requests**, **Services**, **Repositories**, or **Models** in a way that establishes a **new pattern** or **new bounded area** (e.g. a new `app/Actions/Foo` namespace).
- Register new **container bindings** in `AppServiceProvider` or `RepositoryServiceProvider`.
- Add **migrations** that introduce important domain concepts, or change **env vars** / **config** that developers must know (`config/otp.php`, storage, database, etc.).
- Change **composer scripts**, **PHP/Laravel versions**, or **major dependencies** listed below.
- Change team **conventions** captured in **Rules for coding agents** (update that section in the same change).

If you only touch one small area, update the **smallest relevant section** (e.g. append a row to the actions table or route summary). Do not let this file silently contradict the code.

---

## What this project is

**Laravel 12** API backend for repnet.app. It uses:

- **[Laravel Sanctum](https://laravel.com/docs/sanctum)** — token-based API auth (`Authorization: Bearer {token}`).
- **[Laravel Actions](https://laravelactions.com/)** (`lorisleiva/laravel-actions`) — route targets are often **Action classes** using `AsAction` and `asController()` + `handle()`.
- **OTP** (email/SMS) and **password login** for authentication flows.
- **Gym** domain: create/update gyms, public listing, invites, join requests, members listing, **gym shifts** and **shift plans** (nested under gyms).
- **Supporting features**: files (S3/MinIO), workout types, amenities, notifications, messages (threads, comments, reactions), user feed, user profiles/posts.

Business rules for gyms (roles, invite/join flows) live in **`GymService`**, **`GymUser`**, and Gym-related Actions — follow existing patterns before adding parallel logic.

---

## Requirements and local setup

From [README.md](./README.md) (keep in sync if these change):

| Requirement | Notes |
| ----------- | ----- |
| PHP | **8.2+** (`composer.json`: `^8.2`) |
| Database | **PostgreSQL** |
| Composer | Required |
| Node.js | For Vite / frontend assets |

Typical setup:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# Configure .env: DB_*, AWS_* (S3/MinIO), etc.
php artisan migrate
composer dev    # dev stack (see composer.json "dev" script)
```

**Composer scripts** (see `composer.json`): `setup`, `dev`, `test` (clears config cache then `php artisan test`).

---

## Architecture (how requests flow)

**Mandatory chain for new or changed endpoints:** **Route → Form Request → Action → Service → Repository → Model.**

1. **`routes/api.php`** — defines HTTP surface; many routes point **directly** at an `App\Actions\...` class (Laravel Actions invokable controller).
2. **`app/Http/Requests/...`** — **Form Request** validates and authorizes input; Actions use `asController(SomeRequest $request)` and **`$request->validated()`** (or equivalent) when passing data onward.
3. **`app/Actions/...`** — thin orchestration; call **Services** with validated data and authenticated user context—**no** bypassing services to query models directly when a service/repository exists for that area.
4. **`app/Services/...`** — domain operations; depend on **Repositories** (and sometimes other services).
5. **`app/Repositories/...`** — data access; registered as **singletons** in `RepositoryServiceProvider`.
6. **`app/Models/...`** — Eloquent models.

**Dependency injection:** New repositories must be registered in `app/Providers/RepositoryServiceProvider.php`. New services that wrap repositories (or other deps) are typically registered in `app/Providers/AppServiceProvider.php` — mirror existing singleton closures.

**Helpers:** `app/Helpers/helpers.php` is autoloaded via `composer.json` (e.g. `user_can()`, `get_reaction_emoji()`). Prefer not to add heavy logic there; keep domain logic in services.

---

## Routing and API prefix

- API routes live in **`routes/api.php`** and are loaded via **`bootstrap/app.php`** (`withRouting(api: ...)`).
- Laravel applies the framework **API route prefix** (commonly `/api`); **verify the effective prefix** in your Laravel version if clients depend on exact URLs.
- The header comment in `routes/api.php` mentions `/api/v1`; **there is no separate `v1` segment in `bootstrap/app.php` in this repo** — treat path documentation as **URI segments after the API prefix** (e.g. `otp/send`, `gyms`, `user`). If you introduce versioning, update **this file**, **README.md**, and the route file comment together.

---

## Route map (high level)

Accurate patterns matter for client apps; always confirm in `routes/api.php`.

| Area | Auth | Examples (relative to API prefix) |
| ---- | ---- | --------------------------------- |
| Health | Public | `/up` (from `bootstrap/app.php` health) |
| OTP | Mixed | `POST otp/send`, `POST otp/verify` (public); `POST otp/send-authenticated` (Sanctum) |
| Auth | Public | `POST auth/login` |
| Gyms (public) | Public | `GET gyms`, `GET gyms/{gymId}` (show uses `GymService` inline in route closure) |
| Workout types | Public | `GET workout-types` |
| App permissions | Public | `GET app-permissions` → `config('apppermissions')` |
| Users / current user | Sanctum | `GET users/{userId}`, `GET users/{userId}/posts`, `GET user`, `PUT user` |
| Gyms (mutations, members, shifts) | Sanctum | `POST gyms`, `PATCH gyms/{gymId}`, invite/join flows, `GET gyms/{gymId}/users`, nested **`gyms/{gymId}/shifts`** and **`.../shifts/{shiftId}/plans`** |
| Files | Sanctum | `POST files` |
| Amenities | Sanctum | `GET amenities` |
| Notifications | Sanctum | `GET notifications`, patch read / read-all |
| Messages | Sanctum | thread messages, comments, reactions, delete |
| Feed | Sanctum | `GET feed` |

---

## Actions inventory (by namespace)

Actions live under **`app/Actions/`**. Current groupings (run `ls app/Actions` if this list drifts):

| Namespace | Examples |
| --------- | -------- |
| `Auth` | `LoginAction` |
| `Otp` | `SendOtpAction`, `VerifyOtpAction` |
| `User` | `CreateUserAction`, `UpdateUserAction`, `GetUserAction`, `GetUserPostsAction` |
| `Gym` | `CreateGymAction`, `UpdateGymAction`, `ListGymAction`, `ListGymUsersAction`, invite/join actions |
| `GymShift` | `CreateGymShiftAction`, `ListGymShiftAction`, `UpdateGymShiftAction`, `DeleteGymShiftAction` |
| `GymShiftPlan` | `CreateGymShiftPlanAction`, `ListGymShiftPlanAction`, `DeleteGymShiftPlanAction` |
| `File` | `UploadFileAction` |
| `Workout` | `ListWorkoutTypeAction` |
| `Amenity` | `ListAmenityAction` |
| `Notification` | list + mark read actions |
| `Message` | create/list/get/delete, comments, reactions, reacted users |
| `Feed` | `UserFeedAction` |

**Pattern:** See `CreateGymAction`: constructor-injected service, `asController(FormRequest $request)` → `handle(...)`, returns structured array for JSON responses.

---

## Models and data

Eloquent models under **`app/Models/`** include (non-exhaustive): `User`, `Otp`, `Gym`, `GymUser`, `GymShift`, `GymShiftPlan`, `File`, `WorkoutType`, `WorkoutVideo`, `Amenity`, `NoticePost`, `Notification`, `Message`, `MessageThread`, `Reaction`, `Challenge`, `ChallengeSubmission`, `PartnerRequest`, `MetaData`, …

Migrations live in **`database/migrations/`**. After schema changes, update this doc if new entities are **first-class API concepts**.

---

## Configuration worth knowing

- **`config/otp.php`** — OTP length, expiry, rate limits, lockout; env overrides documented there / in README.
- **`config/apppermissions.php`** (or related) — exposed via `app-permissions` route; `user_can()` helper uses `config('apppermissions.' . $role)`.
- **Filesystems** — S3/MinIO via `league/flysystem-aws-s3-v3` (see README env block).

---

## Testing and quality

- Run tests: **`composer test`** (or `php artisan test`).
- Code style: **Laravel Pint** is a dev dependency (`laravel/pint`).

---

## Packages (runtime)

From `composer.json` (update this list if `require` changes):

- `laravel/framework` ^12
- `laravel/sanctum` ^4
- `laravel/reverb` ^1
- `lorisleiva/laravel-actions` ^2.9
- `league/flysystem-aws-s3-v3` ^3.31
- `predis/predis` ^3.4

---

## README vs code

[README.md](./README.md) is the primary onboarding doc but **may lag** the codebase. Prefer **`routes/api.php`** and **`app/Actions`** as source of truth for endpoints. When you align README and code, **update this file** if any section here duplicated the old behavior.

---

## License

MIT (see [README.md](./README.md)).

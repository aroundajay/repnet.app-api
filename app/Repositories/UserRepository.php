<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Arr;

/**
 * User Repository
 * 
 * Handles all database operations for User model.
 * Encapsulates data access logic and provides clean interface.
 */
class UserRepository
{
    /*
    |--------------------------------------------------------------------------
    | Create Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new user record.
     *
     * @param array $data The user data to store
     * @return User The created user model
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Update a user record.
     *
     * @param string $id The ID of the user
     * @param array $data The data to update
     * @return bool True if the user was updated, false otherwise
     */
    public function update(string $id, array $data): bool
    {
        $user = $this->findById($id);

        if (!$user) {
            return false;
        }


        if (!empty($data['files'])) {
            // Transform [{id, flag}, ...] into {uuid: {flag: ...}, ...} for sync pivot data
            $files = collect($data['files'])->mapWithKeys(fn ($file) => [
                $file['id'] => ['flag' => $file['flag']],
            ])->all();

            $user->files()->sync($files);
        }

        return $user->update(Arr::except($data, ['files'])) ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Read Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Find a user by their ID.
     *
     * @param string $id The user UUID
     * @return User|null
     */
    public function findById(string $id, array $with = []): ?User
    {
        return User::with($with)->find($id);
    }

    /**
     * Find a user by email address.
     *
     * @param string $email The email address
     * @return User|null
     */
    public function findByEmail(string $email, array $with = []): ?User
    {
        return User::with($with)->where('email', $email)->first();
    }

    /**
     * Find a user by mobile number.
     *
     * @param string $mobile The mobile number
     * @return User|null
     */
    public function findByMobile(string $mobile, array $with = []): ?User
    {
        return User::with($with)->where('mobile', $mobile)->first();
    }

    /**
     * Find a user by email or mobile number.
     *
     * @param string $identifier Email or mobile number
     * @return User|null
     */
    public function findByIdentifier(string $identifier, array $with = []): ?User
    {
        return User::with($with)
            ->where('email', $identifier)
            ->orWhere('mobile', $identifier)
            ->first();
    }

    /**
     * Check if identifier (email or mobile) exists in users table.
     * Checks both email and mobile columns.
     *
     * @param string $identifier Email or mobile to check
     * @return bool True if identifier exists
     */
    public function existsByIdentifier(string $identifier): bool
    {
        return User::where('email', $identifier)
            ->orWhere('mobile', $identifier)
            ->exists();
    }

    /**
     * Check if email already exists in users table.
     *
     * @param string $email Email to check
     * @return bool True if email exists
     */
    public function existsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Check if mobile number already exists in users table.
     *
     * @param string $mobile Mobile number to check
     * @return bool True if mobile exists
     */
    public function existsByMobile(string $mobile): bool
    {
        return User::where('mobile', $mobile)->exists();
    }
}

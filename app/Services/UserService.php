<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

/**
 * User Service
 * 
 * Handles user-related business logic including:
 * - User creation with password hashing
 * - Data preparation and transformation
 * - Coordination with UserRepository
 */
class UserService
{
    /**
     * Create a new user service instance.
     */
    public function __construct(
        protected UserRepository $repository
    ) {}

    /**
     * Get the user repository instance.
     *
     * @return UserRepository
     */
    public function getRepository(): UserRepository
    {
        return $this->repository;
    }

    /*
    |--------------------------------------------------------------------------
    | User Creation
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new user.
     * Handles password hashing and data preparation.
     *
     * @param array $data User data (name, email/mobile, password)
     * @return User The created user
     */
    public function create(array $data): User
    {
        if (!empty($data['password'])) {
            $data['password'] = $this->hashPassword($data['password']);
        }

        // Create user via repository
        return $this->repository->create($data);
    }

    /**
     * Update a user.
     *
     * @param string $id The ID of the user
     * @param array $data The data to update
     * @return bool True if the user was updated, false otherwise
     */
    public function update(string $id, array $data): bool
    {
        return $this->repository->update($id, $data);
    }

    /*
    |--------------------------------------------------------------------------
    | Data Preparation
    |--------------------------------------------------------------------------
    */

    /**
     * Hash the password using Laravel's Hash facade.
     *
     * @param string $password Plain text password
     * @return string Hashed password
     */
    protected function hashPassword(string $password): string
    {
        return Hash::make($password);
    }

    /*
    |--------------------------------------------------------------------------
    | User Lookup
    |--------------------------------------------------------------------------
    */

    /**
     * Find user by ID.
     *
     * @param string $id User UUID
     * @return User|null
     */
    public function findById(string $id, array $with = []): ?User
    {
        return $this->repository->findById($id, $with);
    }

    /**
     * Find user by email.
     *
     * @param string $email Email address
     * @return User|null
     */
    public function findByEmail(string $email, array $with = []): ?User
    {
        return $this->repository->findByEmail($email, $with);
    }

    /**
     * Find user by mobile.
     *
     * @param string $mobile Mobile number
     * @return User|null
     */
    public function findByMobile(string $mobile, array $with = []): ?User
    {
        return $this->repository->findByMobile($mobile, $with);
    }

    /**
     * Find user by identifier (email or mobile).
     *
     * @param string $identifier Email or mobile
     * @return User|null
     */
    public function findByIdentifier(string $identifier, array $with = []): ?User
    {
        return $this->repository->findByIdentifier($identifier, $with);
    }

    /*
    |--------------------------------------------------------------------------
    | Existence Checks
    |--------------------------------------------------------------------------
    */

    /**
     * Check if identifier exists in users table.
     *
     * @param string $identifier Email or mobile
     * @return bool
     */
    public function existsByIdentifier(string $identifier): bool
    {
        return $this->repository->existsByIdentifier($identifier);
    }

    /**
     * Check if email exists.
     *
     * @param string $email Email address
     * @return bool
     */
    public function existsByEmail(string $email): bool
    {
        return $this->repository->existsByEmail($email);
    }

    /**
     * Check if mobile exists.
     *
     * @param string $mobile Mobile number
     * @return bool
     */
    public function existsByMobile(string $mobile): bool
    {
        return $this->repository->existsByMobile($mobile);
    }
}

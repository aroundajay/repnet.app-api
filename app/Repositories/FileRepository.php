<?php

namespace App\Repositories;

use App\Models\File;

/**
 * File Repository
 *
 * Handles all database operations for the File model.
 * Encapsulates data access so services stay free of query logic.
 */
class FileRepository
{
    public function create(array $data): File
    {
        return File::create($data);
    }

    public function delete(string $id): bool
    {
        return File::destroy($id);
    }

    public function findById(string $id): ?File
    {
        return File::findOrFail($id);
    }
}
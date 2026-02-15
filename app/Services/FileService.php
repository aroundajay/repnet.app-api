<?php

namespace App\Services;

use App\Models\File;
use App\Repositories\FileRepository;
use Illuminate\Support\Facades\Storage;

class FileService
{
    public function __construct(private FileRepository $fileRepository) {}

    public function upload(array $data): File
    {
        $file = $data['file'];

        $path = 'upload/file/'.time().'_'.str_replace(' ', '-', $file->getClientOriginalName());

        /**
         * Using public disk instead s3
         */
        Storage::disk('s3')->put(
            $path,
            file_get_contents($file),
            'public'
        );

        return $this->fileRepository->create([
            'uploaded_by' => $data['uploaded_by'],
            'type' => $data['type'],
            'path' => $path,
        ]);
    }

    public function delete(string $id): bool
    {
        $file = $this->fileRepository->findById($id);

        // @todo: delete file from storage

        return $this->fileRepository->delete($id);
    }

    public function findById(string $id): ?File {
        return $this->fileRepository->findById($id);
    }
}
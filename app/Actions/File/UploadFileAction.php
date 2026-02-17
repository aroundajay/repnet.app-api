<?php

namespace App\Actions\File;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Upload File Action
 *
 * Uploads a file.
 * Flow: UploadFileRequest -> Action -> FileService -> FileRepository.
 */
class UploadFileAction
{
    use AsAction;

    public function __construct(private \App\Services\FileService $fileService) {}

    public function authorize(ActionRequest $request): bool {
        return auth()->check();
    }

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:png,jpg,jpeg,gif,mp4,avi,mov|max:10240',
        ];
    }

    /**
     * Handle the action as an HTTP controller.
     *
     * @param ActionRequest $request
     * @return array
     */
    public function asController(ActionRequest $request): array
    {
        return $this->handle([
                'file' => $request->validated('file'),
                'uploaded_by' => auth()->user()->id
            ]);
    }

    /**
     * Upload a file.
     *
     * @param array $data Validated data: file, fileable_type, fileable_id
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(array $data): array
    {

        $file = $data['file'];
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            $data['type'] = \App\Models\File::TYPE_IMAGE;
        } elseif (str_starts_with($mimeType, 'video/')) {
            $data['type'] = \App\Models\File::TYPE_VIDEO;
        } else {
            throw new \Exception('Invalid file type');
        }

        $uploadedFile = $this->fileService->upload($data);

        return [
            'success' => true,
            'message' => 'File uploaded successfully',
            'status_code' => 200,
            'data' => [
                'file' => $uploadedFile,
            ],
        ];
    }

    /**
     * Build JSON response from action result.
     */
    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
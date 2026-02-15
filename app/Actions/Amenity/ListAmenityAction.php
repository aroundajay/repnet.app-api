<?php

namespace App\Actions\Amenity;

use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * List amenities Action
 *
 * Lists amenities.
 * Flow: ListAmenityRequest -> Action -> AmenityService -> AmenityRepository.
 */
class ListAmenityAction
{
    use AsAction;

    public function __construct(private \App\Services\AmenityService $amenityService) {}

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
        return [];
    }

    /**
     * Handle the action as an HTTP controller.
     *
     * @param ActionRequest $request
     * @return array
     */
    public function asController(ActionRequest $request): array
    {
        return $this->handle();
    }

    /**
     * List amenities.
     *
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(): array
    {

        return [
            'success' => true,
            'message' => 'Amenities listed successfully',
            'status_code' => 200,
            'data' => [
                'amenities' => $this->amenityService->list(),
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
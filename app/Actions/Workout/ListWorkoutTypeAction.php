<?php

namespace App\Actions\Workout;

use App\Services\WorkoutTypeService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * List Workout Type Action
 *
 * Lists all workout types.
 * Flow: ListWorkoutTypeRequest -> Action -> WorkoutTypeService -> WorkoutTypeRepository.
 */
class ListWorkoutTypeAction
{
    use AsAction;

    public function __construct(
        protected WorkoutTypeService $service
    ) {}

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
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
        return $this->handle($request->validated());
    }

    /**
     * List all workout types.
     *
     * @param array $data Validated data: with?
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(array $data): array
    {
        $with = $data['with'] ?? [];

        $workoutTypes = $this->service->listAll($with);

        return [
            'success' => true,
            'message' => 'Workout types fetched successfully',
            'status_code' => 200,
            'data' => [
                'workout_types' => $workoutTypes,
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

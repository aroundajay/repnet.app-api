<?php

namespace App\Actions\Gym;

use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use App\Services\GymService;
use Illuminate\Http\JsonResponse;

class UpdateGymAction
{
    use AsAction;

    public function __construct(
        protected GymService $gymService
    ) {}

    public function authorize(ActionRequest $request): bool
    {
        $gymUser = $this->gymService->findGymUserByGymIdAndUserId($request->route('gymId'), auth()->user()->id);

        if (!$gymUser) {
            return false;
        }

        // only OWNER and ADMIN can update the gym
        if ($gymUser->role !== \App\Models\GymUser::ROLE_OWNER && $gymUser->role !== \App\Models\GymUser::ROLE_ADMIN) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            // Gym name - required, max 255 to match typical string column
            'name' => ['sometimes', 'string', 'max:255'],

            // Optional description
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],

            // Latitude: -90 to 90 (decimal 10,8 in migration)
            'location_lat' => ['sometimes', 'numeric', 'between:-90,90'],

            // Longitude: -180 to 180 (decimal 11,8 in migration)
            'location_lng' => ['sometimes', 'numeric', 'between:-180,180'],

            // Public visibility - optional, defaults to false in model
            'is_public' => ['sometimes', 'boolean'],

            'metadata' => ['array'],
            'metadata.*.key' => ['required', 'in:address,operating_hours,offers_personal_training'],
            'metadata.*.value' => ['nullable'],

            // files
            'files' => ['array'],
            'files.*' => ['uuid', 'exists:files,id'],

            // amenities
            'amenities' => ['array'],
            'amenities.*' => ['uuid', 'exists:amenities,id'],

            // workout types
            'workout_types' => ['array'],
            'workout_types.*' => ['uuid', 'exists:workout_types,id'],
        ];
    }

    public function handle(string $gymId, array $data): array
    {
        $gym = $this->gymService->update($gymId, $data);

        return [
            'success' => true,
            'status_code' => 200,
            'message' => 'Gym updated successfully',
            'data' => [
                'gym' => $gym,
            ],
        ];
    }

    public function asController(ActionRequest $request): array
    {
        return $this->handle($request->route('gymId'), $request->validated());
    }

    /**
     * Build JSON response from action result.
     */
    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}

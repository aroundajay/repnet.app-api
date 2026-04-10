<?php

namespace App\Actions\GymShift;

use Lorisleiva\Actions\Concerns\AsAction;
use App\Http\Requests\GymShift\CreateGymShiftRequest;
use App\Services\GymService;
use App\Services\GymShiftService;
use Illuminate\Http\JsonResponse;

class CreateGymShiftAction
{
    use AsAction;

    public function __construct(
        protected GymService $gymService,
        protected GymShiftService $gymShiftService
    ) {}

    public function authorize(CreateGymShiftRequest $request): bool
    {
        $gymUser = $this->gymService->findGymUserByGymIdAndUserId($request->route('gymId'), $request->user()->id);

        if (!$gymUser) {
            return false;
        }

        if ($gymUser->role !== \App\Models\GymUser::ROLE_OWNER && $gymUser->role !== \App\Models\GymUser::ROLE_ADMIN) {
            return false;
        }

        if ($gymUser->status !== \App\Models\GymUser::STATUS_ACTIVE) {
            return false;
        }

        return true;
    }

    public function handle(string $gymId, array $data): array
    {
        $shift = $this->gymShiftService->create($gymId, $data);

        return [
            'success' => true,
            'message' => 'Gym shift created successfully',
            'status_code' => 201,
            'data' => [
                'gym_shift' => $shift,
            ],
        ];
    }

    public function asController(CreateGymShiftRequest $request): array
    {
        return $this->handle($request->route('gymId'), $request->validated());
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}

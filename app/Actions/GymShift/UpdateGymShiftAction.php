<?php

namespace App\Actions\GymShift;

use Lorisleiva\Actions\Concerns\AsAction;
use App\Http\Requests\GymShift\UpdateGymShiftRequest;
use App\Services\GymService;
use App\Services\GymShiftService;
use Illuminate\Http\JsonResponse;

class UpdateGymShiftAction
{
    use AsAction;

    public function __construct(
        protected GymService $gymService,
        protected GymShiftService $gymShiftService
    ) {}

    public function authorize(UpdateGymShiftRequest $request): bool
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

    public function handle(string $shiftId, array $data): array
    {
        $shift = $this->gymShiftService->update($shiftId, $data);

        return [
            'success' => true,
            'message' => 'Gym shift updated successfully',
            'status_code' => 200,
            'data' => [
                'gym_shift' => $shift,
            ],
        ];
    }

    public function asController(UpdateGymShiftRequest $request): array
    {
        return $this->handle($request->route('shiftId'), $request->validated());
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}

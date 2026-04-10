<?php

namespace App\Actions\GymShift;

use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use App\Services\GymService;
use App\Services\GymShiftService;
use Illuminate\Http\JsonResponse;

class DeleteGymShiftAction
{
    use AsAction;

    public function __construct(
        protected GymService $gymService,
        protected GymShiftService $gymShiftService
    ) {}

    public function authorize(ActionRequest $request): bool
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

    public function handle(string $shiftId): array
    {
        $this->gymShiftService->delete($shiftId);

        return [
            'success' => true,
            'message' => 'Gym shift deleted successfully',
            'status_code' => 200,
            'data' => null,
        ];
    }

    public function asController(ActionRequest $request): array
    {
        return $this->handle($request->route('shiftId'));
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}

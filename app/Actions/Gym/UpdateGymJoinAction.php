<?php

namespace App\Actions\Gym;

use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use App\Services\GymService;
use Illuminate\Http\JsonResponse;

class UpdateGymJoinAction
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

        if ($gymUser->role !== \App\Models\GymUser::ROLE_OWNER && $gymUser->role !== \App\Models\GymUser::ROLE_ADMIN) {
            return false;
        }

        if ($gymUser->status !== \App\Models\GymUser::STATUS_ACTIVE) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:' . implode(',', [\App\Models\GymUser::STATUS_ACTIVE, \App\Models\GymUser::STATUS_REJECTED]),
        ];
    }

    public function handle(array $data): array
    {
        $user_id = $data['user_id'];
        $gymId = $data['gym_id'];
        $status = $data['status'];

        $gymUser = $this->gymService->findGymUserByGymIdAndUserId($gymId, $user_id);

        if (!$gymUser) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'Gym user not found',
            ];
        }

        $gymUser = $this->gymService->updateGymUser($gymId, $user_id, [
            'status' => $status,
        ]);

        // @TODO: Send notification to the user

        return [
            'success' => true,
            'status_code' => 200,
            'message' => 'Gym user updated successfully',
            'data' => [
                'gymUser' => $gymUser,
            ],
        ];
    }

    public function asController(ActionRequest $request): array
    {
        return $this->handle([
            'user_id' => $request->route('userId'),
            'gym_id' => $request->route('gymId'),
            'status' => $request->validated('status'),
        ]);
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}

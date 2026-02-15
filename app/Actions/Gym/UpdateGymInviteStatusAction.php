<?php

namespace App\Actions\Gym;

use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use App\Services\GymService;
use Illuminate\Http\JsonResponse;

class UpdateGymInviteStatusAction
{
    use AsAction;

    public function __construct(
        protected GymService $gymService
    ) {}

    public function authorize(ActionRequest $request): bool
    {
        $gymUser = $this->gymService->findGymUserByGymIdAndUserId($request->route('gymId'), $request->route('userId'));

        if (!$gymUser) {
            return false;
        }

        if ($gymUser->user_id !== auth()->user()->id) {
            return false;
        }

        // self invited users can't update their status
        if ($gymUser->invited_by !== 'GYM') {
            return false;
        }

        // add status to the request
        $request->merge(['role' => $gymUser->role]);

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
        $role = $data['role'];
        $status = $data['status'] ?? \App\Models\GymUser::STATUS_ACTIVE;
        
        $gymUser = $this->gymService->findGymUserByGymIdAndUserId($gymId, $user_id);
        if (!$gymUser) {
            $gymUser = $this->gymService->createGymUser($gymId, $user_id, [
                'role' => $role,
                'status' => $status,
            ]);
        } else {
            $gymUser = $this->gymService->updateGymUser($gymId, $user_id, [
                'role' => $role,
                'status' => $status,
            ]);
        }

        return [
            'success' => true,
            'status_code' => 200,
            'message' => 'Gym user created successfully',
            'data' => [
                'gymUser' => $gymUser,
            ],
        ];
    }

    public function asController(ActionRequest $request): array
    {
        $data = $request->validated();

        return $this->handle([
            'user_id' => $request->route('userId'),
            'gym_id' => $request->route('gymId'),
            'status' => $data['status'],
            'role' => $request->input('role'),
        ]);
    }

    /**
     * Build JSON response from action result.
     */
    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
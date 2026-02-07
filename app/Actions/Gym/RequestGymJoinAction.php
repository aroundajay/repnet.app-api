<?php

namespace App\Actions\Gym;

use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use App\Services\GymService;
use Illuminate\Http\JsonResponse;

class RequestGymJoinAction
{
    use AsAction;

    public function __construct(
        protected GymService $gymService
    ) {}

    public function authorize(ActionRequest $request): bool
    {
        $gymUser = $this->gymService->findGymUserByGymIdAndUserId($request->route('gymId'), auth()->user()->id);

        if ($gymUser) {
            return false;
        }

        return true;
    }

    public function handle(array $data): array
    {
        $gymId = $data['gym_id'];
        $userId = $data['user_id'];

        $gym = $this->gymService->findGym($gymId);
        
        if (!$gym) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'Gym not found',
            ];
        }

        $gymUser = $this->gymService->createGymUser($gymId, $userId, [
            'role' => \App\Models\GymUser::ROLE_MEMBER,
            'status' => \App\Models\GymUser::STATUS_PENDING,
            'invited_by' => 'SELF',
        ]);

        // @TODO: Send notification to the gym owner

        return [
            'success' => true,
            'status_code' => 200,
            'message' => 'Gym join request sent successfully',
            'data' => [
                'gymUser' => $gymUser,
            ]
        ];
    }

    public function asController(ActionRequest $request): array
    {
        return $this->handle([
            'gym_id' => $request->route('gymId'),
            'user_id' => auth()->user()->id,
        ]);
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
<?php

namespace App\Actions\Gym;

use App\Actions\Message\CreateMessageAction;
use App\Models\Gym;
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

        if (!user_can('update member join request', $gymUser->role)) {
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

        if ($status === \App\Models\GymUser::STATUS_ACTIVE) {
            $gym     = $this->gymService->findGym($gymId, ['messageThread']);
            // Re-fetch after the update (updateGymUser returns bool, not a model)
            $gymUser = $this->gymService->findGymUserByGymIdAndUserId($gymId, $user_id, ['user']);
            $newUser = $gymUser->user;

            CreateMessageAction::dispatch(
                $gym->messageThread->id,
                Gym::class,
                $gym->id,
                [
                    'message'   => "🎉 Welcome {$newUser->name} to {$gym->name}! We're thrilled to have you here. Everyone, please join us in giving them a warm welcome! 👋",
                    'card_type' => 'NEW_MEMBER',
                    'files'     => [],
                ],
            );
        }

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

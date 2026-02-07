<?php

namespace App\Actions\Gym;

use App\Actions\Otp\SendOtpAction;
use App\Services\GymService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;

class InviteGymUserAction
{
    use AsAction;

    public function __construct(
        protected GymService $gymService,
        protected UserService $userService
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

        if ($gymUser->role === \App\Models\GymUser::ROLE_ADMIN && in_array($request->validated('role'), [\App\Models\GymUser::ROLE_OWNER, \App\Models\GymUser::ROLE_ADMIN])) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'identifiers' => 'required|array',
            'identifiers.*' => [
                'required',
                'string',
                'max:255',
                // Each identifier must be either a valid email or a valid mobile number (E.164-style).
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $trimmed = trim($value);
                    $isEmail = filter_var($trimmed, FILTER_VALIDATE_EMAIL) !== false;
                    // Mobile: optional +, then digits only; 7–15 digits total (E.164). Allow spaces/dashes in input.
                    $digitsOnly = preg_replace('/[\s\-]/', '', $trimmed);
                    $isMobile = preg_match('/^\+?[1-9]\d{6,14}$/', $digitsOnly) === 1;
                    if (! $isEmail && ! $isMobile) {
                        $fail('Each identifier must be a valid email address or mobile number.');
                    }
                },
            ],
            'role' => 'required|string|in:' . implode(',', [\App\Models\GymUser::ROLE_OWNER, \App\Models\GymUser::ROLE_ADMIN, \App\Models\GymUser::ROLE_TRAINER, \App\Models\GymUser::ROLE_MEMBER]),
        ];
    }

    public function handle(array $data): array
    {
        $identifier = $data['identifier'];
        $role = $data['role'];
        $gymId = $data['gym_id'];

        $user = $this->userService->findByIdentifier($identifier);

        if (!$user) {
            // send otp with joining url to the user
            SendOtpAction::dispatch(
                type: 'invite_to_gym',
                identifier: $identifier,
                userProvidedRequestData: [
                    'name' => 'Unknown User',
                    'gym_id' => $gymId,
                    'role' => $role,
                    'status' => \App\Models\GymUser::STATUS_ACTIVE,
                ],
            );

            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'User not found, sending otp with joining url to the user',
                'data' => [
                    'otp' => '',
                ],
            ];
        }

        // check if the user is already a member of the gym
        $gymUser = $this->gymService->findGymUserByGymIdAndUserId($gymId, $user->id);

        if (!$gymUser) {
            $gymUser = $this->gymService->createGymUser($gymId, $user->id, [
                'role' => $role,
            ]);

            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'User not a member of the gym, creating new membership',
                'data' => [
                    'gymUser' => $gymUser,
                ],
            ];
        }

        if ($gymUser->role !== $role) {
            $this->gymService->updateGymUser($gymId, $user->id, [
                'role' => $role,
            ]);

            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'User already a member of the gym, updating role',
                'data' => [
                    'gymUser' => $gymUser,
                ],
            ];
        }

        return [
            'success' => true,
            'status_code' => 200,
            'message' => 'User already a member of the gym, no action needed',
            'data' => [
                'gymUser' => $gymUser,
            ],
        ];
    }

    public function asController(ActionRequest $request): array
    {
        $data = $request->validated();

        foreach ($data['identifiers'] as $identifier) {
            $this->handle([
                'identifier' => $identifier,
                'role' => $data['role'],
                'gym_id' => $request->route('gymId'),
            ]);
        }

        return [
            'success' => true,
            'status_code' => 200,
            'message' => 'Users invited successfully'
        ];
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
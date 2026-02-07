<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Http\JsonResponse;
use App\Services\UserService;


class LoginAction
{
    use AsAction;

    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'identifier' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!$this->userService->findByIdentifier($value)) {
                        $fail('The identifier is invalid.');
                    }
                },
            ],
            'password' => 'required|string',
        ];
    }

    public function handle(array $data): array
    {
        $user = $this->userService->findByIdentifier($data['identifier']);

        if (!$user) {
            return [
                'success' => false,
                'status_code' => 401,
                'message' => 'User not found',
            ];
        }

        if (!empty($data['password']) && !Hash::check($data['password'], $user->password)) {
            return [
                'success' => false,
                'status_code' => 401,
                'message' => 'Invalid password',
            ];
        }
        
        return [
            'success' => true,
            'status_code' => 200,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $user->createToken('auth_token')->plainTextToken,
            ],
        ];
    }

    /**
     * Handle the action as an HTTP controller.
     * This method is called when the action is used as a route.
     *
     * @param ActionRequest $request The validated request
     * @return JsonResponse
     */
    public function asController(ActionRequest $request): array
    {
        return $this->handle($request->validated());
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
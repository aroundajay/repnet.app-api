<?php

namespace App\Actions\User;

use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;

class UpdateUserAction
{
    use AsAction;

    public function __construct(
        protected UserService $userService
    ) {}

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:6|confirmed',
            'current_password' => 'string|required_with:password',

            // Optional file attachments - array of existing file UUIDs
            'files'   => ['sometimes', 'array'],
            'files.*.id' => ['required', 'uuid', 'exists:files,id'],
            'files.*.flag' => ['required', 'in:PROFILE,COVER'],
        ];
    }

    public function handle(array $data): array
    {
        $user = $this->userService->findById($data['id']);

        if (!$user) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'User not found',
            ];
        }

        if (!empty($data['password'])) {
            if (!empty($data['current_password']) && !Hash::check($data['current_password'], $user->password)) {
                return [
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'Invalid current password',
                ];
            }

            $data['password'] = Hash::make($data['password']);
        }

        if (!empty($data['email'])) {
            if ($this->userService->existsByEmail($data['email']) && $data['email'] !== $user->email) {
                return [
                    'success' => false,
                    'status_code' => 400,
                    'message' => 'Email already exists',
                ];
            }
        }

        if (!empty($data['mobile'])) {
            if ($this->userService->existsByMobile($data['mobile']) && $data['mobile'] !== $user->mobile) {
                return [
                    'success' => false,
                    'status_code' => 400,
                    'message' => 'Mobile already exists',
                ];
            }
        }

        $this->userService->update($data['id'], $data);

        return [
            'success' => true,
            'message' => 'User updated successfully',
            'status_code' => 200,
            'data' => $user->fresh(['files']),
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
        return $this->handle(array_merge(
            Arr::except($request->validated(), ['password_confirmation']),
            ['id' => $request->user()->id]
        ));
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
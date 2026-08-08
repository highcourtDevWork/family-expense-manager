<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * The authenticated API user and their current family.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $family = $user->currentFamily();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'family' => $family ? [
                    'id' => $family->id,
                    'name' => $family->name,
                    'role' => $user->isAdminOf($family) ? 'admin' : 'member',
                ] : null,
            ],
        ]);
    }
}

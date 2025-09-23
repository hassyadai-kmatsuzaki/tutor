<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * ユーザー一覧取得（管理者のみ）
     */
    public function index(Request $request): JsonResponse
    {
        // 管理者権限チェック
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です'
            ], 403);
        }

        $query = User::query();

        // 検索フィルター
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        // ロールフィルター
        if ($request->filled('role')) {
            $query->where('role', $request->get('role'));
        }

        // アクティブ状態フィルター
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * ユーザー詳細取得
     */
    public function show(User $user): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * ユーザー作成
     */
    public function store(Request $request): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:admin,manager,sales',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'ユーザーを作成しました'
        ], 201);
    }

    /**
     * ユーザー更新
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:admin,manager,sales',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'ユーザー情報を更新しました'
        ]);
    }

    /**
     * ユーザー削除（論理削除）
     */
    public function destroy(User $user): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です'
            ], 403);
        }

        // 自分自身は削除できない
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => '自分自身は削除できません'
            ], 400);
        }

        // 論理削除（is_activeをfalseに）
        $user->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'ユーザーを無効化しました'
        ]);
    }

    /**
     * ユーザー統計情報
     */
    public function statistics(): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '管理者権限が必要です'
            ], 403);
        }

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'by_role' => User::selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->get()
                ->pluck('count', 'role')
                ->toArray(),
            'by_department' => User::whereNotNull('department')
                ->selectRaw('department, COUNT(*) as count')
                ->groupBy('department')
                ->get()
                ->pluck('count', 'department')
                ->toArray(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
} 
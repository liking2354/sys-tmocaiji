<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * 显示个人资料页面
     */
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    /**
     * 更新个人资料
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'email' => 'required|email|max:100|unique:users,email,' . $user->id,
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:6|confirmed',
        ], [
            'username.required' => '用户名不能为空',
            'username.unique' => '用户名已存在',
            'email.required' => '邮箱不能为空',
            'email.email' => '邮箱格式不正确',
            'email.unique' => '邮箱已存在',
            'new_password.min' => '新密码至少6位',
            'new_password.confirmed' => '两次密码输入不一致',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // 如果要修改密码，验证当前密码
        if ($request->filled('new_password')) {
            if (!$request->filled('current_password')) {
                return redirect()->back()
                    ->withErrors(['current_password' => '请输入当前密码'])
                    ->withInput();
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return redirect()->back()
                    ->withErrors(['current_password' => '当前密码不正确'])
                    ->withInput();
            }

            $user->password = Hash::make($request->new_password);
        }

        $user->username = $request->username;
        $user->email = $request->email;
        $user->save();

        return redirect()->route('profile.index')
            ->with('success', '个人资料更新成功');
    }

    /**
     * 上传头像
     */
    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'avatar.required' => '请选择头像文件',
            'avatar.image' => '头像必须是图片',
            'avatar.mimes' => '头像仅支持 jpeg、png、jpg、gif 格式',
            'avatar.max' => '头像大小不能超过 2MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = Auth::user();

        try {
            // 删除旧头像
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // 存储新头像
            $file = $request->file('avatar');
            $filename = 'avatars/' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('', $filename, 'public');

            // 更新用户头像路径
            $user->avatar = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => '头像上传成功',
                'avatar_url' => url('storage/' . $path),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '头像上传失败：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 删除头像
     */
    public function deleteAvatar()
    {
        $user = Auth::user();

        try {
            // 删除头像文件
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // 清空用户头像路径
            $user->avatar = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => '头像已删除',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '头像删除失败：' . $e->getMessage(),
            ], 500);
        }
    }
}

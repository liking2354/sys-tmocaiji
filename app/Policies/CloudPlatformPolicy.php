<?php

namespace App\Policies;

use App\Models\CloudPlatform;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CloudPlatformPolicy
{
    use HandlesAuthorization;

    /**
     * 查看云平台列表
     */
    public function viewAny(User $user)
    {
        return true; // 所有认证用户都可以查看自己的云平台
    }

    /**
     * 查看特定云平台
     */
    public function view(User $user, CloudPlatform $cloudPlatform)
    {
        // 管理员可以查看所有云平台，普通用户只能查看自己的
        return $this->isAdmin($user) || $cloudPlatform->user_id === $user->id;
    }

    /**
     * 创建云平台
     */
    public function create(User $user)
    {
        return true; // 所有认证用户都可以创建云平台
    }

    /**
     * 更新云平台
     */
    public function update(User $user, CloudPlatform $cloudPlatform)
    {
        // 管理员可以更新所有云平台，普通用户只能更新自己的
        return $this->isAdmin($user) || $cloudPlatform->user_id === $user->id;
    }

    /**
     * 删除云平台
     */
    public function delete(User $user, CloudPlatform $cloudPlatform)
    {
        // 管理员可以删除所有云平台，普通用户只能删除自己的
        return $this->isAdmin($user) || $cloudPlatform->user_id === $user->id;
    }

    /**
     * 恢复云平台
     */
    public function restore(User $user, CloudPlatform $cloudPlatform)
    {
        return $this->isAdmin($user) || $cloudPlatform->user_id === $user->id;
    }

    /**
     * 永久删除云平台
     */
    public function forceDelete(User $user, CloudPlatform $cloudPlatform)
    {
        return $this->isAdmin($user);
    }

    /**
     * 检查用户是否为管理员
     */
    private function isAdmin(User $user): bool
    {
        // 简单的管理员检查，可以根据实际需求调整
        // 这里假设用户名为 admin 的是管理员，或者可以检查其他字段
        return $user->username === 'admin' || $user->id === 1;
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    /**
     * 显示权限列表
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $permissions = Permission::paginate(15);
        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * 显示创建权限表单
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.permissions.create');
    }

    /**
     * 保存新权限
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions',
            'module' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        Permission::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'module' => $request->module,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', '权限创建成功');
    }

    /**
     * 显示编辑权限表单
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * 更新权限信息
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,'.$permission->id,
            'module' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        $permission->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'module' => $request->module,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', '权限信息更新成功');
    }

    /**
     * 删除权限
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Permission $permission)
    {
        // 先解除与角色的关联
        $permission->roles()->detach();
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', '权限删除成功');
    }
}

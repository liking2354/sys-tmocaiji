<?php

namespace App\Http\Controllers;

use App\Models\ServerGroup;
use App\Helpers\PaginationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServerGroupController extends Controller
{
    /**
     * 显示服务器分组列表
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ServerGroup::withCount('servers');
        
        // 按名称查询
        if ($request->has('name') && !empty($request->input('name'))) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        
        $perPage = PaginationHelper::getPerPage($request, 10);
        $groups = $query->paginate($perPage)->appends(PaginationHelper::getQueryParams($request));
        
        return view('server-groups.index', compact('groups'));
    }

    /**
     * 显示创建服务器分组表单
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('server-groups.create');
    }

    /**
     * 存储新创建的服务器分组
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:server_groups',
            'description' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        ServerGroup::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);
        
        return redirect()->route('server-groups.index')
            ->with('success', '服务器分组创建成功！');
    }

    /**
     * 显示指定的服务器分组
     *
     * @param  \App\Models\ServerGroup  $serverGroup
     * @return \Illuminate\Http\Response
     */
    public function show(ServerGroup $serverGroup)
    {
        $serverGroup->load('servers');
        
        return view('server-groups.show', compact('serverGroup'));
    }

    /**
     * 显示编辑服务器分组表单
     *
     * @param  \App\Models\ServerGroup  $serverGroup
     * @return \Illuminate\Http\Response
     */
    public function edit(ServerGroup $serverGroup)
    {
        return view('server-groups.edit', compact('serverGroup'));
    }

    /**
     * 更新指定的服务器分组
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ServerGroup  $serverGroup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ServerGroup $serverGroup)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:server_groups,name,' . $serverGroup->id,
            'description' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $serverGroup->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);
        
        return redirect()->route('server-groups.index')
            ->with('success', '服务器分组更新成功！');
    }

    /**
     * 删除指定的服务器分组
     *
     * @param  \App\Models\ServerGroup  $serverGroup
     * @return \Illuminate\Http\Response
     */
    public function destroy(ServerGroup $serverGroup)
    {
        // 检查是否有关联的服务器
        if ($serverGroup->servers()->count() > 0) {
            return redirect()->back()
                ->with('error', '无法删除，该分组下还有服务器！');
        }
        
        $serverGroup->delete();
        
        return redirect()->route('server-groups.index')
            ->with('success', '服务器分组删除成功！');
    }

    /**
     * 批量删除服务器分组
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function batchDelete(Request $request)
    {
        if (!$request->has('group_ids') || empty($request->group_ids)) {
            return redirect()->route('server-groups.index')
                ->with('error', '未选择任何分组');
        }

        $groupIds = $request->group_ids;
        $cannotDeleteGroups = [];
        $deletedCount = 0;

        foreach ($groupIds as $groupId) {
            $group = ServerGroup::find($groupId);
            
            if (!$group) {
                continue;
            }

            // 检查是否有关联的服务器
            if ($group->servers()->count() > 0) {
                $cannotDeleteGroups[] = $group->name;
                continue;
            }

            $group->delete();
            $deletedCount++;
        }

        if (count($cannotDeleteGroups) > 0) {
            $message = "成功删除 {$deletedCount} 个分组，但以下分组因有关联服务器无法删除：" . implode(', ', $cannotDeleteGroups);
            return redirect()->route('server-groups.index')
                ->with('warning', $message);
        }

        return redirect()->route('server-groups.index')
            ->with('success', "成功删除 {$deletedCount} 个分组");
    }
}
<?php

namespace App\Imports;

use App\Models\Server;
use App\Models\ServerGroup;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use phpseclib3\Net\SSH2;

class ServersImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * 从Excel导入服务器数据
     *
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // 检查必填字段
        if (empty($row['name']) || empty($row['ip']) || empty($row['username']) || empty($row['password'])) {
            return null;
        }
        
        // 获取或创建服务器分组
        $groupName = $row['group'] ?? '默认分组';
        $group = ServerGroup::firstOrCreate(
            ['name' => $groupName],
            ['description' => '通过导入创建的分组']
        );
        
        // 验证SSH连接
        $port = $row['port'] ?? 22;
        $verifyConnection = $row['verify_connection'] ?? true;
        
        if ($verifyConnection) {
            try {
                $ssh = new SSH2($row['ip'], $port);
                $connected = $ssh->login($row['username'], $row['password']);
                $status = $connected ? 1 : 0;
            } catch (\Exception $e) {
                $status = 0;
            }
        } else {
            $status = 0;
        }
        
        // 创建服务器记录
        return new Server([
            'name' => $row['name'],
            'group_id' => $group->id,
            'ip' => $row['ip'],
            'port' => $port,
            'username' => $row['username'],
            'password' => $row['password'], // 实际应用中应加密存储
            'status' => $status,
        ]);
    }
    
    /**
     * 验证规则
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'ip' => 'required|ip',
            'port' => 'nullable|integer|between:1,65535',
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:255',
        ];
    }
}
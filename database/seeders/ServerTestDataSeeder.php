<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServerGroup;
use App\Models\Server;

class ServerTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 创建测试分组
        $groups = [
            [
                'name' => '生产环境',
                'description' => '生产环境服务器分组'
            ],
            [
                'name' => '测试环境',
                'description' => '测试环境服务器分组'
            ],
            [
                'name' => '开发环境',
                'description' => '开发环境服务器分组'
            ]
        ];

        foreach ($groups as $groupData) {
            $group = ServerGroup::firstOrCreate(
                ['name' => $groupData['name']],
                $groupData
            );

            // 为每个分组创建一些测试服务器
            $servers = [];
            switch ($group->name) {
                case '生产环境':
                    $servers = [
                        ['name' => 'prod-web-01', 'ip' => '192.168.1.10'],
                        ['name' => 'prod-web-02', 'ip' => '192.168.1.11'],
                        ['name' => 'prod-db-01', 'ip' => '192.168.1.20'],
                    ];
                    break;
                case '测试环境':
                    $servers = [
                        ['name' => 'test-web-01', 'ip' => '192.168.2.10'],
                        ['name' => 'test-db-01', 'ip' => '192.168.2.20'],
                    ];
                    break;
                case '开发环境':
                    $servers = [
                        ['name' => 'dev-web-01', 'ip' => '192.168.3.10'],
                    ];
                    break;
            }

            foreach ($servers as $serverData) {
                Server::firstOrCreate([
                    'name' => $serverData['name'],
                    'group_id' => $group->id
                ], array_merge($serverData, [
                    'group_id' => $group->id,
                    'port' => 22,
                    'username' => 'root',
                    'password' => '',
                    'status' => 1, // 1表示在线
                ]));
            }
        }

        $this->command->info('服务器测试数据创建完成！');
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RedesignDictStructure extends Command
{
    protected $signature = 'dict:redesign';
    protected $description = '重新设计字典结构，支持三级层次管理';

    public function handle()
    {
        $this->info('开始重新设计字典结构...');
        
        // 运行迁移
        $this->info('1. 执行数据库迁移...');
        Artisan::call('migrate', ['--path' => 'database/migrations/2025_10_16_030000_redesign_dict_structure.php']);
        
        // 运行数据填充
        $this->info('2. 填充新的字典数据...');
        Artisan::call('db:seed', ['--class' => 'NewDictStructureSeeder']);
        
        $this->info('字典结构重新设计完成！');
        $this->info('新的结构支持：');
        $this->info('- 三级层次管理');
        $this->info('- 平台类型关联');
        $this->info('- 动态筛选显示');
        
        return 0;
    }
}
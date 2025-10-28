<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CloudRegion;

class CloudRegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            // 华为云区域
            'huawei' => [
                ['region_code' => 'cn-north-1', 'region_name' => '华北-北京一'],
                ['region_code' => 'cn-north-4', 'region_name' => '华北-北京四'],
                ['region_code' => 'cn-north-9', 'region_name' => '华北-乌兰察布一'],
                ['region_code' => 'cn-east-2', 'region_name' => '华东-上海二'],
                ['region_code' => 'cn-east-3', 'region_name' => '华东-上海一'],
                ['region_code' => 'cn-south-1', 'region_name' => '华南-广州'],
                ['region_code' => 'cn-south-4', 'region_name' => '华南-广州-友好用户环境'],
                ['region_code' => 'cn-southwest-2', 'region_name' => '西南-贵阳一'],
                ['region_code' => 'ap-southeast-1', 'region_name' => '亚太-香港'],
                ['region_code' => 'ap-southeast-2', 'region_name' => '亚太-曼谷'],
                ['region_code' => 'ap-southeast-3', 'region_name' => '亚太-新加坡'],
                ['region_code' => 'af-south-1', 'region_name' => '非洲-约翰内斯堡'],
                ['region_code' => 'na-mexico-1', 'region_name' => '拉美-墨西哥城一'],
                ['region_code' => 'la-north-2', 'region_name' => '拉美-墨西哥城二'],
                ['region_code' => 'la-south-2', 'region_name' => '拉美-圣地亚哥'],
                ['region_code' => 'ap-southeast-4', 'region_name' => '亚太-雅加达'],
                ['region_code' => 'tr-west-1', 'region_name' => '土耳其-伊斯坦布尔'],
                ['region_code' => 'eu-west-101', 'region_name' => '欧洲-爱尔兰'],
                ['region_code' => 'me-east-1', 'region_name' => '中东-利雅得'],
                ['region_code' => 'my-kualalumpur-1', 'region_name' => '亚太-吉隆坡'],
                ['region_code' => 'ru-moscow-1', 'region_name' => '俄罗斯-莫斯科二'],
            ],
            
            // 阿里云区域
            'alibaba' => [
                ['region_code' => 'cn-hangzhou', 'region_name' => '华东1（杭州）'],
                ['region_code' => 'cn-shanghai', 'region_name' => '华东2（上海）'],
                ['region_code' => 'cn-qingdao', 'region_name' => '华北1（青岛）'],
                ['region_code' => 'cn-beijing', 'region_name' => '华北2（北京）'],
                ['region_code' => 'cn-zhangjiakou', 'region_name' => '华北3（张家口）'],
                ['region_code' => 'cn-huhehaote', 'region_name' => '华北5（呼和浩特）'],
                ['region_code' => 'cn-wulanchabu', 'region_name' => '华北6（乌兰察布）'],
                ['region_code' => 'cn-shenzhen', 'region_name' => '华南1（深圳）'],
                ['region_code' => 'cn-heyuan', 'region_name' => '华南2（河源）'],
                ['region_code' => 'cn-guangzhou', 'region_name' => '华南3（广州）'],
                ['region_code' => 'cn-chengdu', 'region_name' => '西南1（成都）'],
                ['region_code' => 'cn-hongkong', 'region_name' => '中国香港'],
                ['region_code' => 'ap-southeast-1', 'region_name' => '新加坡'],
                ['region_code' => 'ap-southeast-2', 'region_name' => '澳大利亚（悉尼）'],
                ['region_code' => 'ap-southeast-3', 'region_name' => '马来西亚（吉隆坡）'],
                ['region_code' => 'ap-southeast-5', 'region_name' => '印度尼西亚（雅加达）'],
                ['region_code' => 'ap-northeast-1', 'region_name' => '日本（东京）'],
                ['region_code' => 'ap-south-1', 'region_name' => '印度（孟买）'],
                ['region_code' => 'us-east-1', 'region_name' => '美国（弗吉尼亚）'],
                ['region_code' => 'us-west-1', 'region_name' => '美国（硅谷）'],
                ['region_code' => 'eu-west-1', 'region_name' => '英国（伦敦）'],
                ['region_code' => 'eu-central-1', 'region_name' => '德国（法兰克福）'],
            ],
            
            // 腾讯云区域
            'tencent' => [
                ['region_code' => 'ap-beijing', 'region_name' => '华北地区（北京）'],
                ['region_code' => 'ap-shanghai', 'region_name' => '华东地区（上海）'],
                ['region_code' => 'ap-guangzhou', 'region_name' => '华南地区（广州）'],
                ['region_code' => 'ap-shenzhen-fsi', 'region_name' => '华南地区（深圳金融）'],
                ['region_code' => 'ap-shanghai-fsi', 'region_name' => '华东地区（上海金融）'],
                ['region_code' => 'ap-beijing-fsi', 'region_name' => '华北地区（北京金融）'],
                ['region_code' => 'ap-chengdu', 'region_name' => '西南地区（成都）'],
                ['region_code' => 'ap-chongqing', 'region_name' => '西南地区（重庆）'],
                ['region_code' => 'ap-nanjing', 'region_name' => '华东地区（南京）'],
                ['region_code' => 'ap-tianjin', 'region_name' => '华北地区（天津）'],
                ['region_code' => 'ap-hongkong', 'region_name' => '港澳台地区（中国香港）'],
                ['region_code' => 'ap-singapore', 'region_name' => '亚太东南（新加坡）'],
                ['region_code' => 'ap-bangkok', 'region_name' => '亚太东南（曼谷）'],
                ['region_code' => 'ap-jakarta', 'region_name' => '亚太东南（雅加达）'],
                ['region_code' => 'ap-tokyo', 'region_name' => '亚太东北（东京）'],
                ['region_code' => 'ap-seoul', 'region_name' => '亚太东北（首尔）'],
                ['region_code' => 'ap-mumbai', 'region_name' => '亚太南部（孟买）'],
                ['region_code' => 'na-siliconvalley', 'region_name' => '美国西部（硅谷）'],
                ['region_code' => 'na-ashburn', 'region_name' => '美国东部（弗吉尼亚）'],
                ['region_code' => 'na-toronto', 'region_name' => '北美地区（多伦多）'],
                ['region_code' => 'sa-saopaulo', 'region_name' => '南美地区（圣保罗）'],
                ['region_code' => 'eu-frankfurt', 'region_name' => '欧洲地区（法兰克福）'],
                ['region_code' => 'eu-moscow', 'region_name' => '欧洲地区（莫斯科）'],
            ],
        ];

        foreach ($regions as $platformType => $platformRegions) {
            foreach ($platformRegions as $regionData) {
                CloudRegion::updateOrCreate(
                    [
                        'platform_type' => $platformType,
                        'region_code' => $regionData['region_code']
                    ],
                    [
                        'region_name' => $regionData['region_name'],
                        'is_active' => true,
                        'description' => "系统预设的{$regionData['region_name']}区域"
                    ]
                );
            }
        }

        $this->command->info('云区域数据初始化完成！');
    }
}
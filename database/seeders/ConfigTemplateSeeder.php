<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConfigTemplate;

class ConfigTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Laravel .env 配置模板
        ConfigTemplate::create([
            'name' => 'Laravel环境配置',
            'description' => '用于修改Laravel应用的.env配置文件，包括数据库连接、应用URL等基础配置',
            'config_items' => [
                [
                    'name' => '应用基础配置',
                    'file_path' => '/var/www/html/.env',
                    'modifications' => [
                        [
                            'type' => 'replace',
                            'pattern' => 'APP_NAME=.*',
                            'replacement' => 'APP_NAME={{app_name}}',
                            'description' => '应用名称'
                        ],
                        [
                            'type' => 'replace',
                            'pattern' => 'APP_URL=.*',
                            'replacement' => 'APP_URL={{app_url}}',
                            'description' => '应用URL'
                        ],
                        [
                            'type' => 'replace',
                            'pattern' => 'DB_HOST=.*',
                            'replacement' => 'DB_HOST={{db_host}}',
                            'description' => '数据库主机'
                        ],
                        [
                            'type' => 'replace',
                            'pattern' => 'DB_DATABASE=.*',
                            'replacement' => 'DB_DATABASE={{db_name}}',
                            'description' => '数据库名称'
                        ],
                        [
                            'type' => 'replace',
                            'pattern' => 'DB_USERNAME=.*',
                            'replacement' => 'DB_USERNAME={{db_user}}',
                            'description' => '数据库用户名'
                        ],
                        [
                            'type' => 'replace',
                            'pattern' => 'DB_PASSWORD=.*',
                            'replacement' => 'DB_PASSWORD={{db_password}}',
                            'description' => '数据库密码'
                        ]
                    ]
                ]
            ],
            'variables' => [
                'app_name' => [
                    'label' => '应用名称',
                    'type' => 'text',
                    'default' => 'Laravel',
                    'required' => true
                ],
                'app_url' => [
                    'label' => '应用URL',
                    'type' => 'url',
                    'default' => 'http://localhost',
                    'required' => true
                ],
                'db_host' => [
                    'label' => '数据库主机',
                    'type' => 'text',
                    'default' => '127.0.0.1',
                    'required' => true
                ],
                'db_name' => [
                    'label' => '数据库名称',
                    'type' => 'text',
                    'default' => 'laravel',
                    'required' => true
                ],
                'db_user' => [
                    'label' => '数据库用户名',
                    'type' => 'text',
                    'default' => 'root',
                    'required' => true
                ],
                'db_password' => [
                    'label' => '数据库密码',
                    'type' => 'password',
                    'default' => '',
                    'required' => false
                ]
            ],
            'is_active' => true
        ]);

        // Nginx 配置模板
        ConfigTemplate::create([
            'name' => 'Nginx虚拟主机配置',
            'description' => '用于修改Nginx虚拟主机配置，包括域名、根目录、SSL证书等',
            'config_items' => [
                [
                    'name' => 'Nginx站点配置',
                    'file_path' => '/etc/nginx/sites-available/{{site_name}}',
                    'modifications' => [
                        [
                            'type' => 'replace',
                            'pattern' => 'server_name .*;',
                            'replacement' => 'server_name {{domain_name}};',
                            'description' => '服务器域名'
                        ],
                        [
                            'type' => 'replace',
                            'pattern' => 'root .*;',
                            'replacement' => 'root {{document_root}};',
                            'description' => '网站根目录'
                        ],
                        [
                            'type' => 'replace',
                            'pattern' => 'listen 80;',
                            'replacement' => 'listen {{port}};',
                            'description' => '监听端口'
                        ]
                    ]
                ]
            ],
            'variables' => [
                'site_name' => [
                    'label' => '站点名称',
                    'type' => 'text',
                    'default' => 'default',
                    'required' => true
                ],
                'domain_name' => [
                    'label' => '域名',
                    'type' => 'text',
                    'default' => 'example.com',
                    'required' => true
                ],
                'document_root' => [
                    'label' => '网站根目录',
                    'type' => 'text',
                    'default' => '/var/www/html',
                    'required' => true
                ],
                'port' => [
                    'label' => '监听端口',
                    'type' => 'number',
                    'default' => '80',
                    'required' => true
                ]
            ],
            'is_active' => true
        ]);

        // MySQL 配置模板
        ConfigTemplate::create([
            'name' => 'MySQL服务器配置',
            'description' => '用于修改MySQL服务器配置文件，包括缓冲区大小、连接数等性能参数',
            'config_items' => [
                [
                    'name' => 'MySQL性能配置',
                    'file_path' => '/etc/mysql/mysql.conf.d/mysqld.cnf',
                    'modifications' => [
                        [
                            'type' => 'replace',
                            'pattern' => 'max_connections\s*=.*',
                            'replacement' => 'max_connections = {{max_connections}}',
                            'description' => '最大连接数'
                        ],
                        [
                            'type' => 'replace',
                            'pattern' => 'innodb_buffer_pool_size\s*=.*',
                            'replacement' => 'innodb_buffer_pool_size = {{buffer_pool_size}}',
                            'description' => 'InnoDB缓冲池大小'
                        ],
                        [
                            'type' => 'replace',
                            'pattern' => 'query_cache_size\s*=.*',
                            'replacement' => 'query_cache_size = {{query_cache_size}}',
                            'description' => '查询缓存大小'
                        ]
                    ]
                ]
            ],
            'variables' => [
                'max_connections' => [
                    'label' => '最大连接数',
                    'type' => 'number',
                    'default' => '151',
                    'required' => true
                ],
                'buffer_pool_size' => [
                    'label' => 'InnoDB缓冲池大小',
                    'type' => 'text',
                    'default' => '128M',
                    'required' => true
                ],
                'query_cache_size' => [
                    'label' => '查询缓存大小',
                    'type' => 'text',
                    'default' => '16M',
                    'required' => true
                ]
            ],
            'is_active' => true
        ]);

        // Redis 配置模板
        ConfigTemplate::create([
            'name' => 'Redis服务器配置',
            'description' => '用于修改Redis服务器配置文件，包括内存限制、持久化设置等',
            'config_items' => [
                [
                    'name' => 'Redis基础配置',
                    'file_path' => '/etc/redis/redis.conf',
                    'modifications' => [
                        [
                            'type' => 'replace',
                            'pattern' => 'maxmemory .*',
                            'replacement' => 'maxmemory {{max_memory}}',
                            'description' => '最大内存限制'
                        ],
                        [
                            'type' => 'replace',
                            'pattern' => 'maxmemory-policy .*',
                            'replacement' => 'maxmemory-policy {{eviction_policy}}',
                            'description' => '内存淘汰策略'
                        ],
                        [
                            'type' => 'replace',
                            'pattern' => 'save .*',
                            'replacement' => 'save {{save_interval}}',
                            'description' => '数据持久化间隔'
                        ]
                    ]
                ]
            ],
            'variables' => [
                'max_memory' => [
                    'label' => '最大内存',
                    'type' => 'text',
                    'default' => '256mb',
                    'required' => true
                ],
                'eviction_policy' => [
                    'label' => '内存淘汰策略',
                    'type' => 'select',
                    'options' => ['allkeys-lru', 'volatile-lru', 'allkeys-random', 'volatile-random', 'volatile-ttl', 'noeviction'],
                    'default' => 'allkeys-lru',
                    'required' => true
                ],
                'save_interval' => [
                    'label' => '持久化间隔',
                    'type' => 'text',
                    'default' => '900 1 300 10 60 10000',
                    'required' => true
                ]
            ],
            'is_active' => true
        ]);
    }
}
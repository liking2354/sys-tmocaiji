<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConfigTemplate;

class ConfigTemplateVisualSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 示例1：目录批量处理模板
        ConfigTemplate::create([
            'name' => '数据库配置批量更新',
            'description' => '批量更新应用配置目录中的数据库连接信息',
            'template_type' => 'directory',
            'is_active' => true,
            'template_variables' => [
                [
                    'name' => 'db_host',
                    'default_value' => 'localhost',
                    'description' => '数据库主机地址'
                ],
                [
                    'name' => 'db_port',
                    'default_value' => '3306',
                    'description' => '数据库端口'
                ],
                [
                    'name' => 'db_name',
                    'default_value' => 'myapp',
                    'description' => '数据库名称'
                ]
            ],
            'config_rules' => [
                [
                    'type' => 'directory',
                    'directory' => '/var/www/html/config',
                    'pattern' => '*.conf',
                    'variable' => 'db_host',
                    'match_type' => 'key_value',
                    'match_pattern' => 'DB_HOST=.*',
                    'description' => '更新配置文件中的数据库主机地址'
                ],
                [
                    'type' => 'directory',
                    'directory' => '/var/www/html/config',
                    'pattern' => '*.conf',
                    'variable' => 'db_port',
                    'match_type' => 'key_value',
                    'match_pattern' => 'DB_PORT=.*',
                    'description' => '更新配置文件中的数据库端口'
                ]
            ]
        ]);

        // 示例2：文件精确处理模板
        ConfigTemplate::create([
            'name' => 'Nginx服务器配置更新',
            'description' => '更新Nginx配置文件中的服务器名称和端口',
            'template_type' => 'file',
            'is_active' => true,
            'template_variables' => [
                [
                    'name' => 'server_name',
                    'default_value' => 'example.com',
                    'description' => '服务器域名'
                ],
                [
                    'name' => 'listen_port',
                    'default_value' => '80',
                    'description' => '监听端口'
                ]
            ],
            'config_rules' => [
                [
                    'type' => 'file',
                    'file_path' => '/etc/nginx/sites-available/default',
                    'variable' => 'server_name',
                    'match_type' => 'regex',
                    'match_pattern' => 'server_name\s+.*;',
                    'description' => '更新Nginx配置中的服务器名称'
                ],
                [
                    'type' => 'file',
                    'file_path' => '/etc/nginx/sites-available/default',
                    'variable' => 'listen_port',
                    'match_type' => 'regex',
                    'match_pattern' => 'listen\s+\d+;',
                    'description' => '更新Nginx配置中的监听端口'
                ]
            ]
        ]);

        // 示例3：字符串替换模板
        ConfigTemplate::create([
            'name' => 'API配置字符串替换',
            'description' => '替换应用配置文件中的API相关配置',
            'template_type' => 'string',
            'is_active' => true,
            'template_variables' => [
                [
                    'name' => 'api_url',
                    'default_value' => 'https://api.example.com',
                    'description' => 'API服务地址'
                ],
                [
                    'name' => 'api_key',
                    'default_value' => 'your-api-key-here',
                    'description' => 'API密钥'
                ]
            ],
            'config_rules' => [
                [
                    'type' => 'string',
                    'file_path' => '/var/www/html/config/app.php',
                    'search_string' => "'api_url' => 'https://old-api.example.com'",
                    'replace_string' => "'api_url' => '@{{api_url@}}'",
                    'case_sensitive' => true,
                    'regex_mode' => false,
                    'description' => '替换API服务地址配置'
                ],
                [
                    'type' => 'string',
                    'file_path' => '/var/www/html/config/app.php',
                    'search_string' => "'api_key' => env('API_KEY', 'default-key')",
                    'replace_string' => "'api_key' => env('API_KEY', '@{{api_key@}}')",
                    'case_sensitive' => true,
                    'regex_mode' => false,
                    'description' => '替换API密钥默认值'
                ]
            ]
        ]);

        // 示例4：混合模式模板
        ConfigTemplate::create([
            'name' => '完整应用部署配置',
            'description' => '包含数据库、缓存、日志等多种配置的综合模板',
            'template_type' => 'mixed',
            'is_active' => true,
            'template_variables' => [
                [
                    'name' => 'app_name',
                    'default_value' => 'MyApp',
                    'description' => '应用名称'
                ],
                [
                    'name' => 'app_env',
                    'default_value' => 'production',
                    'description' => '应用环境'
                ],
                [
                    'name' => 'db_host',
                    'default_value' => 'localhost',
                    'description' => '数据库主机'
                ],
                [
                    'name' => 'redis_host',
                    'default_value' => '127.0.0.1',
                    'description' => 'Redis主机'
                ]
            ],
            'config_rules' => [
                [
                    'type' => 'file',
                    'file_path' => '/var/www/html/.env',
                    'variable' => 'app_name',
                    'match_type' => 'key_value',
                    'match_pattern' => 'APP_NAME=.*',
                    'description' => '更新应用名称'
                ],
                [
                    'type' => 'file',
                    'file_path' => '/var/www/html/.env',
                    'variable' => 'app_env',
                    'match_type' => 'key_value',
                    'match_pattern' => 'APP_ENV=.*',
                    'description' => '更新应用环境'
                ],
                [
                    'type' => 'directory',
                    'directory' => '/var/www/html/config',
                    'pattern' => 'database.php',
                    'variable' => 'db_host',
                    'match_type' => 'regex',
                    'match_pattern' => "'host'\\s*=>\\s*env\\('DB_HOST',\\s*'[^']*'\\)",
                    'description' => '更新数据库配置文件中的主机地址'
                ],
                [
                    'type' => 'string',
                    'file_path' => '/var/www/html/config/cache.php',
                    'search_string' => "'host' => env('REDIS_HOST', '127.0.0.1')",
                    'replace_string' => "'host' => env('REDIS_HOST', '@{{redis_host@}}')",
                    'case_sensitive' => true,
                    'regex_mode' => false,
                    'description' => '更新Redis缓存配置'
                ]
            ]
        ]);
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Collector;

class PhpAppCollectorSeeder extends Seeder
{
    /**
     * 运行数据库种子
     *
     * @return void
     */
    public function run()
    {
        // PHP应用采集组件
        $this->createOrUpdateCollector(
            'php_app_info',
            'PHP应用信息采集',
            '获取PHP应用的安装路径、配置文件、数据库依赖、中间件依赖和Web Server配置等信息。',
            $this->getPhpAppInfoScript()
        );
    }
    
    /**
     * 创建或更新采集组件
     *
     * @param string $code
     * @param string $name
     * @param string $description
     * @param string $scriptContent
     * @return void
     */
    private function createOrUpdateCollector($code, $name, $description, $scriptContent)
    {
        $collector = Collector::where('code', $code)->first();
        
        if ($collector) {
            // 更新已存在的采集组件
            $collector->update([
                'name' => $name,
                'description' => $description,
                'script_content' => $scriptContent,
                'status' => 1,
            ]);
            
            $this->command->info("采集组件 [{$code}] 已更新");
        } else {
            // 创建新的采集组件
            Collector::create([
                'name' => $name,
                'code' => $code,
                'description' => $description,
                'script_content' => $scriptContent,
                'status' => 1,
            ]);
            
            $this->command->info("采集组件 [{$code}] 已创建");
        }
    }

    /**
     * 获取PHP应用信息采集脚本
     *
     * @return string
     */
    private function getPhpAppInfoScript()
    {
        return <<<'EOT'
#!/bin/bash

# PHP应用信息采集脚本
# 输出格式为JSON

# 创建临时文件
TEMP_FILE=$(mktemp)

# 开始构建JSON
echo "{" > $TEMP_FILE

# 1. 应用安装目录信息
echo "\"app_installation\": {" >> $TEMP_FILE

# 查找Nginx配置文件中的root路径
NGINX_CONF_PATH="/etc/nginx"
ROOT_PATHS=$(find $NGINX_CONF_PATH -type f -name "*.conf" -exec grep -l "root" {} \; | xargs grep "root" | sed -E 's/.*root\s+([^;]+);.*/\1/g' | sort | uniq)

# 将root路径转换为JSON数组
echo "\"nginx_root_paths\": [" >> $TEMP_FILE
for path in $ROOT_PATHS; do
    # 检查是否是最后一个路径
    if [ "$(echo $ROOT_PATHS | tr ' ' '\n' | tail -n1)" = "$path" ]; then
        echo "\"$path\"" >> $TEMP_FILE
    else
        echo "\"$path\"," >> $TEMP_FILE
    fi
done
echo "]," >> $TEMP_FILE

# 检测常见PHP应用目录结构
PHP_APP_PATHS=[]
for path in $ROOT_PATHS; do
    # 检查是否存在常见PHP应用目录结构
    if [ -f "$path/index.php" ] || [ -d "$path/vendor" ] || [ -f "$path/.env" ] || [ -d "$path/config" ]; then
        PHP_APP_PATHS+=("$path")
    fi
done

# 将PHP应用路径转换为JSON数组
echo "\"detected_php_apps\": [" >> $TEMP_FILE
for ((i=0; i<${#PHP_APP_PATHS[@]}; i++)); do
    path=${PHP_APP_PATHS[$i]}
    echo "{" >> $TEMP_FILE
    echo "\"path\": \"$path\"," >> $TEMP_FILE
    echo "\"has_index_php\": $([ -f "$path/index.php" ] && echo "true" || echo "false")," >> $TEMP_FILE
    echo "\"has_vendor\": $([ -d "$path/vendor" ] && echo "true" || echo "false")," >> $TEMP_FILE
    echo "\"has_env\": $([ -f "$path/.env" ] && echo "true" || echo "false")," >> $TEMP_FILE
    echo "\"has_config_dir\": $([ -d "$path/config" ] && echo "true" || echo "false")" >> $TEMP_FILE
    
    # 检查是否是最后一个路径
    if [ $i -eq $((${#PHP_APP_PATHS[@]}-1)) ]; then
        echo "}" >> $TEMP_FILE
    else
        echo "}," >> $TEMP_FILE
    fi
done
echo "]" >> $TEMP_FILE

echo "}," >> $TEMP_FILE

# 2. 应用配置文件关键信息
echo "\"app_configuration\": {" >> $TEMP_FILE

# PHP.ini配置
PHP_INI_PATH=$(php -r "echo php_ini_loaded_file();")
if [ -f "$PHP_INI_PATH" ]; then
    echo "\"php_ini\": {" >> $TEMP_FILE
    echo "\"path\": \"$PHP_INI_PATH\"," >> $TEMP_FILE
    echo "\"memory_limit\": \"$(grep -E "^memory_limit" $PHP_INI_PATH | sed -E 's/memory_limit\s*=\s*([^;]+).*/\1/g' | tr -d ' ')\"," >> $TEMP_FILE
    echo "\"upload_max_filesize\": \"$(grep -E "^upload_max_filesize" $PHP_INI_PATH | sed -E 's/upload_max_filesize\s*=\s*([^;]+).*/\1/g' | tr -d ' ')\"," >> $TEMP_FILE
    echo "\"post_max_size\": \"$(grep -E "^post_max_size" $PHP_INI_PATH | sed -E 's/post_max_size\s*=\s*([^;]+).*/\1/g' | tr -d ' ')\"," >> $TEMP_FILE
    echo "\"max_execution_time\": \"$(grep -E "^max_execution_time" $PHP_INI_PATH | sed -E 's/max_execution_time\s*=\s*([^;]+).*/\1/g' | tr -d ' ')\"" >> $TEMP_FILE
    echo "}," >> $TEMP_FILE
fi

# 框架配置文件信息
echo "\"framework_configs\": [" >> $TEMP_FILE

# 遍历检测到的PHP应用
for ((i=0; i<${#PHP_APP_PATHS[@]}; i++)); do
    path=${PHP_APP_PATHS[$i]}
    echo "{" >> $TEMP_FILE
    echo "\"app_path\": \"$path\"," >> $TEMP_FILE
    
    # 检查.env文件
    if [ -f "$path/.env" ]; then
        echo "\"env_file\": {" >> $TEMP_FILE
        
        # 数据库配置
        DB_CONNECTION=$(grep -E "^DB_CONNECTION" $path/.env 2>/dev/null | sed -E 's/DB_CONNECTION\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        DB_HOST=$(grep -E "^DB_HOST" $path/.env 2>/dev/null | sed -E 's/DB_HOST\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        DB_PORT=$(grep -E "^DB_PORT" $path/.env 2>/dev/null | sed -E 's/DB_PORT\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        DB_DATABASE=$(grep -E "^DB_DATABASE" $path/.env 2>/dev/null | sed -E 's/DB_DATABASE\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        DB_USERNAME=$(grep -E "^DB_USERNAME" $path/.env 2>/dev/null | sed -E 's/DB_USERNAME\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        
        echo "\"db_connection\": \"$DB_CONNECTION\"," >> $TEMP_FILE
        echo "\"db_host\": \"$DB_HOST\"," >> $TEMP_FILE
        echo "\"db_port\": \"$DB_PORT\"," >> $TEMP_FILE
        echo "\"db_database\": \"$DB_DATABASE\"," >> $TEMP_FILE
        echo "\"db_username\": \"$DB_USERNAME\"," >> $TEMP_FILE
        
        # Redis配置
        REDIS_HOST=$(grep -E "^REDIS_HOST" $path/.env 2>/dev/null | sed -E 's/REDIS_HOST\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        REDIS_PORT=$(grep -E "^REDIS_PORT" $path/.env 2>/dev/null | sed -E 's/REDIS_PORT\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        
        echo "\"redis_host\": \"$REDIS_HOST\"," >> $TEMP_FILE
        echo "\"redis_port\": \"$REDIS_PORT\"," >> $TEMP_FILE
        
        # 应用环境
        APP_ENV=$(grep -E "^APP_ENV" $path/.env 2>/dev/null | sed -E 's/APP_ENV\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        APP_DEBUG=$(grep -E "^APP_DEBUG" $path/.env 2>/dev/null | sed -E 's/APP_DEBUG\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        
        echo "\"app_env\": \"$APP_ENV\"," >> $TEMP_FILE
        echo "\"app_debug\": \"$APP_DEBUG\"" >> $TEMP_FILE
        
        echo "}," >> $TEMP_FILE
    fi
    
    # 检查数据库配置文件
    DB_CONFIG_FILES=("$path/config/database.php" "$path/application/config/database.php")
    for db_config in "${DB_CONFIG_FILES[@]}"; do
        if [ -f "$db_config" ]; then
            echo "\"database_config\": {" >> $TEMP_FILE
            echo "\"path\": \"$db_config\"" >> $TEMP_FILE
            echo "}" >> $TEMP_FILE
            break
        fi
    done
    
    # 检查是否是最后一个路径
    if [ $i -eq $((${#PHP_APP_PATHS[@]}-1)) ]; then
        echo "}" >> $TEMP_FILE
    else
        echo "}," >> $TEMP_FILE
    fi
done

echo "]" >> $TEMP_FILE

echo "}," >> $TEMP_FILE

# 3. 数据库依赖信息
echo "\"database_dependencies\": [" >> $TEMP_FILE

# 遍历检测到的PHP应用
for ((i=0; i<${#PHP_APP_PATHS[@]}; i++)); do
    path=${PHP_APP_PATHS[$i]}
    
    # 检查.env文件中的数据库配置
    if [ -f "$path/.env" ]; then
        DB_CONNECTION=$(grep -E "^DB_CONNECTION" $path/.env 2>/dev/null | sed -E 's/DB_CONNECTION\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        DB_HOST=$(grep -E "^DB_HOST" $path/.env 2>/dev/null | sed -E 's/DB_HOST\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        DB_PORT=$(grep -E "^DB_PORT" $path/.env 2>/dev/null | sed -E 's/DB_PORT\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        DB_DATABASE=$(grep -E "^DB_DATABASE" $path/.env 2>/dev/null | sed -E 's/DB_DATABASE\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        DB_USERNAME=$(grep -E "^DB_USERNAME" $path/.env 2>/dev/null | sed -E 's/DB_USERNAME\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        
        if [ ! -z "$DB_CONNECTION" ] && [ ! -z "$DB_HOST" ]; then
            echo "{" >> $TEMP_FILE
            echo "\"app_path\": \"$path\"," >> $TEMP_FILE
            echo "\"db_type\": \"$DB_CONNECTION\"," >> $TEMP_FILE
            echo "\"db_host\": \"$DB_HOST\"," >> $TEMP_FILE
            echo "\"db_port\": \"$DB_PORT\"," >> $TEMP_FILE
            echo "\"db_name\": \"$DB_DATABASE\"," >> $TEMP_FILE
            echo "\"db_user\": \"$DB_USERNAME\"," >> $TEMP_FILE
            echo "\"db_password\": \"[REDACTED]\"" >> $TEMP_FILE
            
            # 检查是否是最后一个路径
            if [ $i -eq $((${#PHP_APP_PATHS[@]}-1)) ]; then
                echo "}" >> $TEMP_FILE
            else
                echo "}," >> $TEMP_FILE
            fi
        fi
    fi
done

echo "]," >> $TEMP_FILE

# 4. 中间件依赖信息
echo "\"middleware_dependencies\": [" >> $TEMP_FILE

# 遍历检测到的PHP应用
for ((i=0; i<${#PHP_APP_PATHS[@]}; i++)); do
    path=${PHP_APP_PATHS[$i]}
    
    # 检查.env文件中的Redis配置
    if [ -f "$path/.env" ]; then
        REDIS_HOST=$(grep -E "^REDIS_HOST" $path/.env 2>/dev/null | sed -E 's/REDIS_HOST\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        REDIS_PORT=$(grep -E "^REDIS_PORT" $path/.env 2>/dev/null | sed -E 's/REDIS_PORT\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        REDIS_PASSWORD=$(grep -E "^REDIS_PASSWORD" $path/.env 2>/dev/null | sed -E 's/REDIS_PASSWORD\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        
        if [ ! -z "$REDIS_HOST" ]; then
            echo "{" >> $TEMP_FILE
            echo "\"app_path\": \"$path\"," >> $TEMP_FILE
            echo "\"middleware_type\": \"Redis\"," >> $TEMP_FILE
            echo "\"host\": \"$REDIS_HOST\"," >> $TEMP_FILE
            echo "\"port\": \"$REDIS_PORT\"," >> $TEMP_FILE
            echo "\"auth\": \"$([ ! -z "$REDIS_PASSWORD" ] && echo "password" || echo "none")\"" >> $TEMP_FILE
            echo "}," >> $TEMP_FILE
        fi
        
        # 检查Memcached配置
        MEMCACHED_HOST=$(grep -E "^MEMCACHED_HOST" $path/.env 2>/dev/null | sed -E 's/MEMCACHED_HOST\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        MEMCACHED_PORT=$(grep -E "^MEMCACHED_PORT" $path/.env 2>/dev/null | sed -E 's/MEMCACHED_PORT\s*=\s*([^#]+).*/\1/g' | tr -d ' ')
        
        if [ ! -z "$MEMCACHED_HOST" ]; then
            echo "{" >> $TEMP_FILE
            echo "\"app_path\": \"$path\"," >> $TEMP_FILE
            echo "\"middleware_type\": \"Memcached\"," >> $TEMP_FILE
            echo "\"host\": \"$MEMCACHED_HOST\"," >> $TEMP_FILE
            echo "\"port\": \"$MEMCACHED_PORT\"" >> $TEMP_FILE
            echo "}," >> $TEMP_FILE
        fi
        
        # 检查消息队列配置
        MQ_HOST=$(grep -E "^MQ_HOST|^RABBITMQ_HOST|^KAFKA_BROKERS" $path/.env 2>/dev/null | head -1 | sed -E 's/.*=\s*([^#]+).*/\1/g' | tr -d ' ')
        MQ_PORT=$(grep -E "^MQ_PORT|^RABBITMQ_PORT|^KAFKA_PORT" $path/.env 2>/dev/null | head -1 | sed -E 's/.*=\s*([^#]+).*/\1/g' | tr -d ' ')
        
        if [ ! -z "$MQ_HOST" ]; then
            MQ_TYPE="Unknown"
            if grep -q "RABBITMQ_HOST" $path/.env; then
                MQ_TYPE="RabbitMQ"
            elif grep -q "KAFKA_BROKERS" $path/.env; then
                MQ_TYPE="Kafka"
            fi
            
            echo "{" >> $TEMP_FILE
            echo "\"app_path\": \"$path\"," >> $TEMP_FILE
            echo "\"middleware_type\": \"$MQ_TYPE\"," >> $TEMP_FILE
            echo "\"host\": \"$MQ_HOST\"," >> $TEMP_FILE
            echo "\"port\": \"$MQ_PORT\"" >> $TEMP_FILE
            echo "}" >> $TEMP_FILE
        fi
    fi
done

# 移除最后一个逗号（如果有中间件）
sed -i '$ s/,$//' $TEMP_FILE

echo "]," >> $TEMP_FILE

# 5. Nginx配置映射
echo "\"nginx_configurations\": [" >> $TEMP_FILE

# 查找Nginx配置文件中的server块
NGINX_SERVER_BLOCKS=$(find $NGINX_CONF_PATH -type f -name "*.conf" -exec grep -l "server {" {} \;)

for conf_file in $NGINX_SERVER_BLOCKS; do
    # 提取server块信息
    SERVER_NAMES=$(grep -A50 "server {" $conf_file | grep -m1 "server_name" | sed -E 's/.*server_name\s+([^;]+);.*/\1/g')
    LISTEN_PORTS=$(grep -A50 "server {" $conf_file | grep -m1 "listen" | sed -E 's/.*listen\s+([^;]+);.*/\1/g')
    ROOT_PATH=$(grep -A50 "server {" $conf_file | grep -m1 "root" | sed -E 's/.*root\s+([^;]+);.*/\1/g')
    INDEX_FILES=$(grep -A50 "server {" $conf_file | grep -m1 "index" | sed -E 's/.*index\s+([^;]+);.*/\1/g')
    
    if [ ! -z "$SERVER_NAMES" ] || [ ! -z "$ROOT_PATH" ]; then
        echo "{" >> $TEMP_FILE
        echo "\"config_file\": \"$conf_file\"," >> $TEMP_FILE
        echo "\"server_name\": \"$SERVER_NAMES\"," >> $TEMP_FILE
        echo "\"listen\": \"$LISTEN_PORTS\"," >> $TEMP_FILE
        echo "\"root\": \"$ROOT_PATH\"," >> $TEMP_FILE
        echo "\"index\": \"$INDEX_FILES\"" >> $TEMP_FILE
        
        # 检查是否是最后一个配置文件
        if [ "$(echo $NGINX_SERVER_BLOCKS | tr ' ' '\n' | tail -n1)" = "$conf_file" ]; then
            echo "}" >> $TEMP_FILE
        else
            echo "}," >> $TEMP_FILE
        fi
    fi
done

echo "]" >> $TEMP_FILE

# 结束JSON
echo "}" >> $TEMP_FILE

# 输出结果
cat $TEMP_FILE

# 清理临时文件
rm $TEMP_FILE
EOT;
    }
}
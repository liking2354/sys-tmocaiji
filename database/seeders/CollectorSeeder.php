<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Collector;

class CollectorSeeder extends Seeder
{
    /**
     * 运行数据库种子
     *
     * @return void
     */
    public function run()
    {
        // 1. 系统基础信息采集组件
        $this->createOrUpdateCollector(
            'system_basic_info',
            '系统基础信息采集',
            '获取服务器的基本硬件、操作系统、网络和存储等核心信息，用于统一管理和资源分析。',
            $this->getSystemBasicInfoScript()
        );

        // 2. 系统环境变量采集组件
        $this->createOrUpdateCollector(
            'system_env_vars',
            '系统环境变量采集',
            '分析系统运行环境配置情况，为应用调试、迁移和兼容性提供参考。',
            $this->getSystemEnvVarsScript()
        );

        // 3. 系统运行进程采集组件
        $this->createOrUpdateCollector(
            'system_processes',
            '系统运行进程采集',
            '监控服务器运行中进程，发现资源占用大户及关键应用。',
            $this->getSystemProcessesScript()
        );

        // 4. Nginx采集组件
        $this->createOrUpdateCollector(
            'nginx_info',
            'Nginx信息采集',
            '获取Nginx基础配置、运行状态及性能指标，为应用层分析和运维优化提供支持。',
            $this->getNginxInfoScript()
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
     * 获取系统基础信息采集脚本
     *
     * @return string
     */
    private function getSystemBasicInfoScript()
    {
        return <<<'EOT'
#!/bin/bash

# 系统基础信息采集脚本
# 输出格式为JSON

# 创建临时文件
TEMP_FILE=$(mktemp)

# 开始构建JSON
echo "{" > $TEMP_FILE

# 主机标识信息
echo "\"host_info\": {" >> $TEMP_FILE
echo "\"hostname\": \"$(hostname)\"," >> $TEMP_FILE

# 获取主机唯一标识
if [ -f /etc/machine-id ]; then
    MACHINE_ID=$(cat /etc/machine-id)
else
    MACHINE_ID=$(dmidecode -s system-uuid 2>/dev/null || echo "unknown")
fi
echo "\"machine_id\": \"$MACHINE_ID\"," >> $TEMP_FILE

# 内核版本
echo "\"kernel_version\": \"$(uname -r)\"," >> $TEMP_FILE

# 操作系统发行版及版本
if [ -f /etc/os-release ]; then
    OS_NAME=$(grep -E "^NAME=" /etc/os-release | cut -d'"' -f2)
    OS_VERSION=$(grep -E "^VERSION=" /etc/os-release | cut -d'"' -f2)
    echo "\"os_name\": \"$OS_NAME\"," >> $TEMP_FILE
    echo "\"os_version\": \"$OS_VERSION\"," >> $TEMP_FILE
else
    echo "\"os_name\": \"Unknown\"," >> $TEMP_FILE
    echo "\"os_version\": \"Unknown\"," >> $TEMP_FILE
fi

# 系统启动时间和运行时长
UPTIME_SEC=$(cat /proc/uptime | awk '{print $1}')
UPTIME_DAYS=$(echo "$UPTIME_SEC/86400" | bc)
UPTIME_HOURS=$(echo "($UPTIME_SEC%86400)/3600" | bc)
UPTIME_MINUTES=$(echo "($UPTIME_SEC%3600)/60" | bc)
BOOT_TIME=$(who -b | awk '{print $3 " " $4}')

echo "\"boot_time\": \"$BOOT_TIME\"," >> $TEMP_FILE
echo "\"uptime\": \"$UPTIME_DAYS days, $UPTIME_HOURS hours, $UPTIME_MINUTES minutes\"" >> $TEMP_FILE
echo "}," >> $TEMP_FILE

# 硬件信息
echo "\"hardware_info\": {" >> $TEMP_FILE

# CPU信息
CPU_MODEL=$(grep -m 1 "model name" /proc/cpuinfo | cut -d':' -f2 | sed 's/^[ \t]*//')
CPU_CORES=$(grep -c "^processor" /proc/cpuinfo)
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2 + $4}')

echo "\"cpu_model\": \"$CPU_MODEL\"," >> $TEMP_FILE
echo "\"cpu_cores\": $CPU_CORES," >> $TEMP_FILE
echo "\"cpu_usage_percent\": $CPU_USAGE," >> $TEMP_FILE

# 内存信息
MEM_TOTAL=$(free -m | grep Mem | awk '{print $2}')
MEM_USED=$(free -m | grep Mem | awk '{print $3}')
MEM_FREE=$(free -m | grep Mem | awk '{print $4}')
MEM_USAGE_PERCENT=$(echo "scale=2; $MEM_USED*100/$MEM_TOTAL" | bc)

echo "\"memory_total_mb\": $MEM_TOTAL," >> $TEMP_FILE
echo "\"memory_used_mb\": $MEM_USED," >> $TEMP_FILE
echo "\"memory_free_mb\": $MEM_FREE," >> $TEMP_FILE
echo "\"memory_usage_percent\": $MEM_USAGE_PERCENT," >> $TEMP_FILE

# 磁盘信息
echo "\"disk_info\": [" >> $TEMP_FILE
df -h | grep -v "tmpfs" | grep -v "udev" | grep -v "Filesystem" | while read line; do
    FILESYSTEM=$(echo $line | awk '{print $1}')
    SIZE=$(echo $line | awk '{print $2}')
    USED=$(echo $line | awk '{print $3}')
    AVAIL=$(echo $line | awk '{print $4}')
    USE_PERCENT=$(echo $line | awk '{print $5}' | sed 's/%//')
    MOUNTED=$(echo $line | awk '{print $6}')
    
    echo "{" >> $TEMP_FILE
    echo "\"filesystem\": \"$FILESYSTEM\"," >> $TEMP_FILE
    echo "\"size\": \"$SIZE\"," >> $TEMP_FILE
    echo "\"used\": \"$USED\"," >> $TEMP_FILE
    echo "\"available\": \"$AVAIL\"," >> $TEMP_FILE
    echo "\"use_percent\": $USE_PERCENT," >> $TEMP_FILE
    echo "\"mounted_on\": \"$MOUNTED\"" >> $TEMP_FILE
    
    # 检查是否是最后一行
    if [ "$(df -h | grep -v "tmpfs" | grep -v "udev" | grep -v "Filesystem" | tail -n1 | awk '{print $1}')" = "$FILESYSTEM" ]; then
        echo "}" >> $TEMP_FILE
    else
        echo "}," >> $TEMP_FILE
    fi
done
echo "]," >> $TEMP_FILE

# 网卡信息
echo "\"network_interfaces\": [" >> $TEMP_FILE
ip -o link show | grep -v "lo" | while read -r line; do
    INTERFACE=$(echo $line | awk '{print $2}' | sed 's/://')
    MAC=$(echo $line | awk '{print $17}')
    
    # 获取IP地址
    IP_ADDR=$(ip -o -4 addr show dev $INTERFACE | awk '{print $4}' | cut -d'/' -f1)
    
    echo "{" >> $TEMP_FILE
    echo "\"interface\": \"$INTERFACE\"," >> $TEMP_FILE
    echo "\"mac_address\": \"$MAC\"," >> $TEMP_FILE
    echo "\"ip_address\": \"$IP_ADDR\"" >> $TEMP_FILE
    
    # 检查是否是最后一行
    if [ "$(ip -o link show | grep -v "lo" | tail -n1 | awk '{print $2}' | sed 's/://')" = "$INTERFACE" ]; then
        echo "}" >> $TEMP_FILE
    else
        echo "}," >> $TEMP_FILE
    fi
done
echo "]" >> $TEMP_FILE
echo "}," >> $TEMP_FILE

# 网络信息
echo "\"network_info\": {" >> $TEMP_FILE

# 内网IP
INTERNAL_IP=$(hostname -I | awk '{print $1}')
echo "\"internal_ip\": \"$INTERNAL_IP\"," >> $TEMP_FILE

# 公网IP (可能需要网络连接)
PUBLIC_IP=$(curl -s ifconfig.me 2>/dev/null || echo "unknown")
echo "\"public_ip\": \"$PUBLIC_IP\"," >> $TEMP_FILE

# 已打开端口
echo "\"open_ports\": [" >> $TEMP_FILE
ss -tulnp | grep -v "State" | awk '{print $5}' | awk -F: '{print $NF}' | sort -n | uniq | while read -r port; do
    if [ "$(ss -tulnp | grep -v "State" | awk '{print $5}' | awk -F: '{print $NF}' | sort -n | uniq | tail -n1)" = "$port" ]; then
        echo "$port" >> $TEMP_FILE
    else
        echo "$port," >> $TEMP_FILE
    fi
done
echo "]," >> $TEMP_FILE

# 默认网关
DEFAULT_GATEWAY=$(ip route | grep default | awk '{print $3}')
echo "\"default_gateway\": \"$DEFAULT_GATEWAY\"," >> $TEMP_FILE

# DNS服务器
DNS_SERVERS=$(grep "nameserver" /etc/resolv.conf | awk '{print $2}' | tr '\n' ',' | sed 's/,$//')
echo "\"dns_servers\": \"$DNS_SERVERS\"" >> $TEMP_FILE

echo "}" >> $TEMP_FILE

# 结束JSON
echo "}" >> $TEMP_FILE

# 输出结果
cat $TEMP_FILE

# 清理临时文件
rm $TEMP_FILE
EOT;
    }

    /**
     * 获取系统环境变量采集脚本
     *
     * @return string
     */
    private function getSystemEnvVarsScript()
    {
        return <<<'EOT'
#!/bin/bash

# 系统环境变量采集脚本
# 输出格式为JSON

# 创建临时文件
TEMP_FILE=$(mktemp)

# 开始构建JSON
echo "{" > $TEMP_FILE

# 全局环境变量
echo "\"global_env_vars\": {" >> $TEMP_FILE

# PATH
echo "\"PATH\": \"$PATH\"," >> $TEMP_FILE

# 语言和字符集设置
echo "\"LANG\": \"$LANG\"," >> $TEMP_FILE
echo "\"LC_ALL\": \"$LC_ALL\"," >> $TEMP_FILE

# Shell类型
echo "\"SHELL\": \"$SHELL\"," >> $TEMP_FILE

# 用户相关
echo "\"USER\": \"$USER\"," >> $TEMP_FILE
echo "\"HOME\": \"$HOME\"," >> $TEMP_FILE
echo "\"LOGNAME\": \"$LOGNAME\"," >> $TEMP_FILE

# 命令历史大小
echo "\"HISTSIZE\": \"$HISTSIZE\"," >> $TEMP_FILE
echo "\"HISTFILESIZE\": \"$HISTFILESIZE\"" >> $TEMP_FILE

echo "}," >> $TEMP_FILE

# 关键配置变量
echo "\"key_config_vars\": {" >> $TEMP_FILE

# 动态库路径
echo "\"LD_LIBRARY_PATH\": \"$LD_LIBRARY_PATH\"," >> $TEMP_FILE

# 常见语言运行时配置
echo "\"JAVA_HOME\": \"$JAVA_HOME\"," >> $TEMP_FILE
echo "\"PYTHONPATH\": \"$PYTHONPATH\"," >> $TEMP_FILE
echo "\"GOROOT\": \"$GOROOT\"," >> $TEMP_FILE

# 代理设置
echo "\"HTTP_PROXY\": \"$HTTP_PROXY\"," >> $TEMP_FILE
echo "\"HTTPS_PROXY\": \"$HTTPS_PROXY\"" >> $TEMP_FILE

echo "}," >> $TEMP_FILE

# 所有环境变量
echo "\"all_env_vars\": {" >> $TEMP_FILE

# 使用env命令获取所有环境变量
env | sort | while read -r line; do
    KEY=$(echo $line | cut -d'=' -f1)
    VALUE=$(echo $line | cut -d'=' -f2-)
    
    # 处理特殊字符
    VALUE=$(echo $VALUE | sed 's/\\/\\\\/g' | sed 's/"/\\"/g')
    
    # 检查是否是最后一行
    if [ "$(env | sort | tail -n1 | cut -d'=' -f1)" = "$KEY" ]; then
        echo "\"$KEY\": \"$VALUE\"" >> $TEMP_FILE
    else
        echo "\"$KEY\": \"$VALUE\"," >> $TEMP_FILE
    fi
done

echo "}" >> $TEMP_FILE

# 结束JSON
echo "}" >> $TEMP_FILE

# 输出结果
cat $TEMP_FILE

# 清理临时文件
rm $TEMP_FILE
EOT;
    }

    /**
     * 获取系统运行进程采集脚本
     *
     * @return string
     */
    private function getSystemProcessesScript()
    {
        return <<<'EOT'
#!/bin/bash

# 系统运行进程采集脚本
# 输出格式为JSON

# 创建临时文件
TEMP_FILE=$(mktemp)

# 开始构建JSON
echo "{" > $TEMP_FILE

# 进程总体信息
echo "\"process_summary\": {" >> $TEMP_FILE

# 进程总数
PROCESS_TOTAL=$(ps -e | wc -l)
echo "\"total_processes\": $PROCESS_TOTAL," >> $TEMP_FILE

# 运行中进程数
RUNNING_PROCESSES=$(ps -eo stat | grep -c "^R")
echo "\"running_processes\": $RUNNING_PROCESSES," >> $TEMP_FILE

# 休眠进程数
SLEEPING_PROCESSES=$(ps -eo stat | grep -c "^S")
echo "\"sleeping_processes\": $SLEEPING_PROCESSES," >> $TEMP_FILE

# 僵尸进程数
ZOMBIE_PROCESSES=$(ps -eo stat | grep -c "^Z")
echo "\"zombie_processes\": $ZOMBIE_PROCESSES" >> $TEMP_FILE

echo "}," >> $TEMP_FILE

# 详细进程列表
echo "\"process_list\": [" >> $TEMP_FILE

# 获取进程列表（限制前200个进程，避免输出过大）
ps -eo pid,user,ppid,stat,%cpu,%mem,vsz,rss,etime,cmd --sort=-%cpu | head -n 201 | tail -n +2 | while read -r pid user ppid stat cpu mem vsz rss etime cmd; do
    echo "{" >> $TEMP_FILE
    echo "\"pid\": $pid," >> $TEMP_FILE
    echo "\"user\": \"$user\"," >> $TEMP_FILE
    echo "\"ppid\": $ppid," >> $TEMP_FILE
    echo "\"status\": \"$stat\"," >> $TEMP_FILE
    echo "\"cpu_percent\": $cpu," >> $TEMP_FILE
    echo "\"memory_percent\": $mem," >> $TEMP_FILE
    echo "\"vsz\": $vsz," >> $TEMP_FILE
    echo "\"rss\": $rss," >> $TEMP_FILE
    echo "\"elapsed_time\": \"$etime\"," >> $TEMP_FILE
    
    # 处理命令字符串中的特殊字符
    CMD_ESCAPED=$(echo "$cmd" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g')
    echo "\"command\": \"$CMD_ESCAPED\"" >> $TEMP_FILE
    
    # 检查是否是最后一行
    if [ "$(ps -eo pid --sort=-%cpu | head -n 201 | tail -n +2 | tail -n1)" = "$pid" ]; then
        echo "}" >> $TEMP_FILE
    else
        echo "}," >> $TEMP_FILE
    fi
done

echo "]," >> $TEMP_FILE

# 资源TOP进程
echo "\"top_processes\": {" >> $TEMP_FILE

# CPU占用前5的进程
echo "\"top_cpu_processes\": [" >> $TEMP_FILE
ps -eo pid,user,%cpu,cmd --sort=-%cpu | head -n 6 | tail -n +2 | while read -r pid user cpu cmd; do
    echo "{" >> $TEMP_FILE
    echo "\"pid\": $pid," >> $TEMP_FILE
    echo "\"user\": \"$user\"," >> $TEMP_FILE
    echo "\"cpu_percent\": $cpu," >> $TEMP_FILE
    
    # 处理命令字符串中的特殊字符
    CMD_ESCAPED=$(echo "$cmd" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g')
    echo "\"command\": \"$CMD_ESCAPED\"" >> $TEMP_FILE
    
    # 检查是否是最后一行
    if [ "$(ps -eo pid --sort=-%cpu | head -n 6 | tail -n +2 | tail -n1)" = "$pid" ]; then
        echo "}" >> $TEMP_FILE
    else
        echo "}," >> $TEMP_FILE
    fi
done
echo "]," >> $TEMP_FILE

# 内存占用前5的进程
echo "\"top_memory_processes\": [" >> $TEMP_FILE
ps -eo pid,user,%mem,cmd --sort=-%mem | head -n 6 | tail -n +2 | while read -r pid user mem cmd; do
    echo "{" >> $TEMP_FILE
    echo "\"pid\": $pid," >> $TEMP_FILE
    echo "\"user\": \"$user\"," >> $TEMP_FILE
    echo "\"memory_percent\": $mem," >> $TEMP_FILE
    
    # 处理命令字符串中的特殊字符
    CMD_ESCAPED=$(echo "$cmd" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g')
    echo "\"command\": \"$CMD_ESCAPED\"" >> $TEMP_FILE
    
    # 检查是否是最后一行
    if [ "$(ps -eo pid --sort=-%mem | head -n 6 | tail -n +2 | tail -n1)" = "$pid" ]; then
        echo "}" >> $TEMP_FILE
    else
        echo "}," >> $TEMP_FILE
    fi
done
echo "]" >> $TEMP_FILE

echo "}" >> $TEMP_FILE

# 结束JSON
echo "}" >> $TEMP_FILE

# 输出结果
cat $TEMP_FILE

# 清理临时文件
rm $TEMP_FILE
EOT;
    }

    /**
     * 获取Nginx信息采集脚本
     *
     * @return string
     */
    private function getNginxInfoScript()
    {
        return <<<'EOT'
#!/bin/bash

# Nginx信息采集脚本
# 输出格式为JSON

# 创建临时文件
TEMP_FILE=$(mktemp)

# 开始构建JSON
echo "{" > $TEMP_FILE

# 安装与版本信息
echo "\"installation_info\": {" >> $TEMP_FILE

# Nginx安装路径
NGINX_PATH=$(which nginx 2>/dev/null || echo "not found")
echo "\"nginx_path\": \"$NGINX_PATH\"," >> $TEMP_FILE

# Nginx版本
if [ "$NGINX_PATH" != "not found" ]; then
    NGINX_VERSION=$($NGINX_PATH -v 2>&1 | sed -n 's/.*nginx\/\([0-9.]*\).*/\1/p')
else
    NGINX_VERSION="unknown"
fi
echo "\"nginx_version\": \"$NGINX_VERSION\"," >> $TEMP_FILE

# 配置文件路径
if [ "$NGINX_PATH" != "not found" ]; then
    NGINX_CONF_PATH=$($NGINX_PATH -t 2>&1 | grep "configuration file" | sed -n 's/.*file \(.*\) test.*/\1/p')
    if [ -z "$NGINX_CONF_PATH" ]; then
        NGINX_CONF_PATH="/etc/nginx/nginx.conf"
    fi
else
    NGINX_CONF_PATH="/etc/nginx/nginx.conf"
fi
echo "\"config_path\": \"$NGINX_CONF_PATH\"" >> $TEMP_FILE

echo "}," >> $TEMP_FILE

# 配置参数采集
echo "\"configuration\": {" >> $TEMP_FILE

# 检查配置文件是否存在
if [ -f "$NGINX_CONF_PATH" ]; then
    # 主配置文件内容（摘要）
    echo "\"main_config_summary\": {" >> $TEMP_FILE
    
    # worker_processes
    WORKER_PROCESSES=$(grep -E "^\s*worker_processes" "$NGINX_CONF_PATH" | sed -n 's/worker_processes\s*\([0-9]*\);.*/\1/p')
    echo "\"worker_processes\": \"$WORKER_PROCESSES\"," >> $TEMP_FILE
    
    # worker_connections
    WORKER_CONNECTIONS=$(grep -E "worker_connections" "$NGINX_CONF_PATH" | sed -n 's/.*worker_connections\s*\([0-9]*\);.*/\1/p')
    echo "\"worker_connections\": \"$WORKER_CONNECTIONS\"," >> $TEMP_FILE
    
    # keepalive_timeout
    KEEPALIVE_TIMEOUT=$(grep -E "keepalive_timeout" "$NGINX_CONF_PATH" | sed -n 's/.*keepalive_timeout\s*\([0-9]*\);.*/\1/p')
    echo "\"keepalive_timeout\": \"$KEEPALIVE_TIMEOUT\"" >> $TEMP_FILE
    
    echo "}," >> $TEMP_FILE
    
    # 虚拟主机配置
    echo "\"virtual_hosts\": [" >> $TEMP_FILE
    
    # 查找所有包含server块的配置文件
    VHOST_FILES=$(find /etc/nginx/ -type f -name "*.conf" | xargs grep -l "server {")
    
    # 处理每个虚拟主机配置文件
    for vhost_file in $VHOST_FILES; do
        # 提取server_name和listen端口
        grep -A20 "server {" "$vhost_file" | while read -r line; do
            if [[ $line == *"server_name"* ]]; then
                SERVER_NAME=$(echo $line | sed -n 's/.*server_name\s*\(.*\);.*/\1/p')
            fi
            if [[ $line == *"listen"* ]]; then
                LISTEN_PORT=$(echo $line | sed -n 's/.*listen\s*\([0-9]*\).*/\1/p')
            fi
            if [[ ! -z "$SERVER_NAME" && ! -z "$LISTEN_PORT" ]]; then
                echo "{" >> $TEMP_FILE
                echo "\"server_name\": \"$SERVER_NAME\"," >> $TEMP_FILE
                echo "\"listen_port\": \"$LISTEN_PORT\"," >> $TEMP_FILE
                echo "\"config_file\": \"$vhost_file\"" >> $TEMP_FILE
                echo "}," >> $TEMP_FILE
                break
            fi
        done
    done
    
    # 移除最后一个逗号（如果有虚拟主机）
    if [ -n "$VHOST_FILES" ]; then
        sed -i '$ s/,$//' $TEMP_FILE
    fi
    
    echo "]" >> $TEMP_FILE
else
    echo "\"error\": \"Nginx配置文件不存在\"" >> $TEMP_FILE
fi

echo "}," >> $TEMP_FILE

# 运行状态
echo "\"runtime_status\": {" >> $TEMP_FILE

# Nginx主进程PID
NGINX_PID=$(pgrep -o nginx)
if [ -z "$NGINX_PID" ]; then
    echo "\"running\": false," >> $TEMP_FILE
    echo "\"master_pid\": null," >> $TEMP_FILE
    echo "\"worker_count\": 0" >> $TEMP_FILE
else
    echo "\"running\": true," >> $TEMP_FILE
    echo "\"master_pid\": $NGINX_PID," >> $TEMP_FILE
    
    # 工作进程数
    WORKER_COUNT=$(pgrep -c nginx)
    WORKER_COUNT=$((WORKER_COUNT - 1)) # 减去主进程
    echo "\"worker_count\": $WORKER_COUNT," >> $TEMP_FILE
    
    # 服务监听端口
    echo "\"listening_ports\": [" >> $TEMP_FILE
    PORTS=$(ss -tulnp | grep nginx | awk '{print $5}' | awk -F: '{print $NF}' | sort -n | uniq)
    for port in $PORTS; do
        if [ "$(echo $PORTS | tr ' ' '\n' | tail -n1)" = "$port" ]; then
            echo "$port" >> $TEMP_FILE
        else
            echo "$port," >> $TEMP_FILE
        fi
    done
    echo "]" >> $TEMP_FILE
fi

echo "}," >> $TEMP_FILE

# 性能与连接信息
echo "\"performance_info\": {" >> $TEMP_FILE

# 检查是否启用了stub_status模块
STUB_STATUS_URL="http://localhost/nginx_status"
STUB_STATUS=$(curl -s $STUB_STATUS_URL 2>/dev/null)

if [ -n "$STUB_STATUS" ]; then
    # 活跃连接数
    ACTIVE_CONNECTIONS=$(echo "$STUB_STATUS" | grep "Active connections" | awk '{print $3}')
    echo "\"active_connections\": $ACTIVE_CONNECTIONS," >> $TEMP_FILE
    
    # 请求统计
    ACCEPTS=$(echo "$STUB_STATUS" | grep -A1 "server accepts" | tail -n1 | awk '{print $1}')
    HANDLED=$(echo "$STUB_STATUS" | grep -A1 "server accepts" | tail -n1 | awk '{print $2}')
    REQUESTS=$(echo "$STUB_STATUS" | grep -A1 "server accepts" | tail -n1 | awk '{print $3}')
    
    echo "\"accepted_connections\": $ACCEPTS," >> $TEMP_FILE
    echo "\"handled_connections\": $HANDLED," >> $TEMP_FILE
    echo "\"total_requests\": $REQUESTS," >> $TEMP_FILE
    
    # 连接状态
    READING=$(echo "$STUB_STATUS" | grep "Reading" | awk '{print $2}')
    WRITING=$(echo "$STUB_STATUS" | grep "Writing" | awk '{print $4}')
    WAITING=$(echo "$STUB_STATUS" | grep "Waiting" | awk '{print $6}')
    
    echo "\"reading\": $READING," >> $TEMP_FILE
    echo "\"writing\": $WRITING," >> $TEMP_FILE
    echo "\"waiting\": $WAITING" >> $TEMP_FILE
else
    echo "\"error\": \"无法获取Nginx状态信息，可能未启用stub_status模块\"" >> $TEMP_FILE
fi

echo "}" >> $TEMP_FILE

# 结束JSON
echo "}" >> $TEMP_FILE

# 输出结果
cat $TEMP_FILE

# 清理临时文件
rm $TEMP_FILE
EOT;
    }
}
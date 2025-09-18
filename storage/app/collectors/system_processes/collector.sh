#!/bin/bash
set -e

escape_json() {
    # 转义换行、引号和反斜杠
    echo "$1" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g' -e ':a;N;$!ba;s/\n/ /g'
}

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

get_process_info() {
    echo "{"

    # 总进程数
    total_processes=$(ps aux | wc -l)
    echo "  \"total_processes\": $((total_processes - 1)),"  # 去掉标题行

    # 运行、睡眠、僵尸进程数
    running_processes=$(ps aux | awk '$8 ~ /R/ { count++ } END { print count+0 }')
    sleeping_processes=$(ps aux | awk '$8 ~ /S/ { count++ } END { print count+0 }')
    zombie_processes=$(ps aux | awk '$8 ~ /Z/ { count++ } END { print count+0 }')

    echo "  \"running_processes\": $running_processes,"
    echo "  \"sleeping_processes\": $sleeping_processes,"
    echo "  \"zombie_processes\": $zombie_processes,"

    # 系统负载
    if command_exists uptime; then
        load_avg=$(uptime | awk -F'load average:' '{print $2}' | sed 's/^ *//' | sed 's/, */,/g')
        echo "  \"load_average\": \"$load_avg\","
    fi

    # 内存信息
    if command_exists free; then
        mem_info=$(free -m | grep "^Mem:")
        total_mem=$(echo $mem_info | awk '{print $2}')
        used_mem=$(echo $mem_info | awk '{print $3}')
        free_mem=$(echo $mem_info | awk '{print $4}')
        echo "  \"memory\": {"
        echo "    \"total_mb\": $total_mem,"
        echo "    \"used_mb\": $used_mem,"
        echo "    \"free_mb\": $free_mem"
        echo "  },"
    fi

    # CPU核心数
    if [ -f /proc/cpuinfo ]; then
        cpu_cores=$(grep -c ^processor /proc/cpuinfo)
        echo "  \"cpu_cores\": $cpu_cores,"
    fi

    # Top5 CPU进程
    top_cpu=$(ps -eo pid,user,%cpu,%mem,comm --sort=-%cpu | head -n 6 | tail -n 5)
    echo "  \"top5_cpu_processes\": ["
    first=1
    while read -r pid user cpu mem comm; do
        exe_path=$(readlink -f /proc/$pid/exe 2>/dev/null || echo "")
        comm_escaped=$(escape_json "$comm")
        exe_escaped=$(escape_json "$exe_path")
        [[ $first -eq 0 ]] && echo "," 
        echo -n "    {\"pid\":$pid,\"user\":\"$user\",\"cpu_percent\":$cpu,\"mem_percent\":$mem,\"command\":\"$comm_escaped\",\"exe_path\":\"$exe_escaped\"}"
        first=0
    done <<< "$top_cpu"
    echo
    echo "  ],"

    # Top5 内存进程
    top_mem=$(ps -eo pid,user,%cpu,%mem,comm --sort=-%mem | head -n 6 | tail -n 5)
    echo "  \"top5_mem_processes\": ["
    first=1
    while read -r pid user cpu mem comm; do
        exe_path=$(readlink -f /proc/$pid/exe 2>/dev/null || echo "")
        comm_escaped=$(escape_json "$comm")
        exe_escaped=$(escape_json "$exe_path")
        [[ $first -eq 0 ]] && echo "," 
        echo -n "    {\"pid\":$pid,\"user\":\"$user\",\"cpu_percent\":$cpu,\"mem_percent\":$mem,\"command\":\"$comm_escaped\",\"exe_path\":\"$exe_escaped\"}"
        first=0
    done <<< "$top_mem"
    echo
    echo "  ],"

    # 采集时间
    echo "  \"collected_at\": \"$(date +\"%Y-%m-%d %H:%M:%S\")\""
    echo "}"
}

# 执行采集
get_process_info
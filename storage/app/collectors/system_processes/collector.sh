#!/bin/bash

# 系统运行进程采集脚本
# 输出格式为JSON

# 创建临时文件
TEMP_FILE=$(mktemp)

# 检查命令是否存在的函数
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# 错误处理函数
handle_error() {
    echo "{\"success\":false,\"message\":\"$1\"}" > $TEMP_FILE
    cat $TEMP_FILE
    rm $TEMP_FILE
    exit 1
}

# 开始构建JSON
echo "{" > $TEMP_FILE

# 进程总体信息
echo "\"process_summary\": {" >> $TEMP_FILE

# 检查ps命令是否存在
if ! command_exists ps; then
    echo "ps命令不存在，无法获取进程信息" >&2
    echo "\"error\": \"ps命令不存在\"" >> $TEMP_FILE
    echo "}," >> $TEMP_FILE
else
    # 进程总数
    if PROCESS_TOTAL=$(ps -e 2>/dev/null | wc -l); then
        echo "\"total_processes\": $PROCESS_TOTAL," >> $TEMP_FILE
    else
        echo "获取进程总数失败" >&2
        echo "\"total_processes\": 0," >> $TEMP_FILE
    fi

    # 运行中进程数
    if command_exists grep; then
        if RUNNING_PROCESSES=$(ps -eo stat 2>/dev/null | grep -c "^R"); then
            echo "\"running_processes\": $RUNNING_PROCESSES," >> $TEMP_FILE
        else
            echo "获取运行中进程数失败" >&2
            echo "\"running_processes\": 0," >> $TEMP_FILE
        fi

        # 休眠进程数
        if SLEEPING_PROCESSES=$(ps -eo stat 2>/dev/null | grep -c "^S"); then
            echo "\"sleeping_processes\": $SLEEPING_PROCESSES," >> $TEMP_FILE
        else
            echo "获取休眠进程数失败" >&2
            echo "\"sleeping_processes\": 0," >> $TEMP_FILE
        fi

        # 僵尸进程数
        if ZOMBIE_PROCESSES=$(ps -eo stat 2>/dev/null | grep -c "^Z"); then
            echo "\"zombie_processes\": $ZOMBIE_PROCESSES" >> $TEMP_FILE
        else
            echo "获取僵尸进程数失败" >&2
            echo "\"zombie_processes\": 0" >> $TEMP_FILE
        fi
    else
        echo "grep命令不存在，无法获取详细进程状态" >&2
        echo "\"running_processes\": 0," >> $TEMP_FILE
        echo "\"sleeping_processes\": 0," >> $TEMP_FILE
        echo "\"zombie_processes\": 0" >> $TEMP_FILE
    fi

    echo "}," >> $TEMP_FILE
fi

# 详细进程列表
echo "\"process_list\": [" >> $TEMP_FILE

# 检查ps命令是否存在
if command_exists ps; then
    # 获取进程列表（限制前50个进程，避免输出过大）
    PROCESS_LIST=$(ps -eo pid,user,ppid,stat,%cpu,%mem,vsz,rss,etime,cmd --sort=-%cpu 2>/dev/null | head -n 51 | tail -n +2)
    if [ -z "$PROCESS_LIST" ]; then
        echo "获取进程列表失败" >&2
    else
        LAST_PID=$(echo "$PROCESS_LIST" | tail -n1 | awk '{print $1}')
        
        echo "$PROCESS_LIST" | while read -r pid user ppid stat cpu mem vsz rss etime cmd; do
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
            if command_exists sed; then
                CMD_ESCAPED=$(echo "$cmd" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g' 2>/dev/null)
            else
                CMD_ESCAPED="$cmd"
            fi
            echo "\"command\": \"$CMD_ESCAPED\"" >> $TEMP_FILE
            
            # 检查是否是最后一行
            if [ "$LAST_PID" = "$pid" ]; then
                echo "}" >> $TEMP_FILE
            else
                echo "}," >> $TEMP_FILE
            fi
        done
    fi
else
    echo "ps命令不存在，无法获取进程列表" >&2
fi

echo "]," >> $TEMP_FILE

# 资源TOP进程
echo "\"top_processes\": {" >> $TEMP_FILE

# 检查ps命令是否存在
if command_exists ps; then
    # CPU占用前5的进程
    echo "\"top_cpu_processes\": [" >> $TEMP_FILE
    TOP_CPU_PROCESSES=$(ps -eo pid,user,%cpu,cmd --sort=-%cpu 2>/dev/null | head -n 6 | tail -n +2)
    if [ -z "$TOP_CPU_PROCESSES" ]; then
        echo "获取CPU占用前5进程失败" >&2
    else
        LAST_PID=$(echo "$TOP_CPU_PROCESSES" | tail -n1 | awk '{print $1}')
        
        echo "$TOP_CPU_PROCESSES" | while read -r pid user cpu cmd; do
            echo "{" >> $TEMP_FILE
            echo "\"pid\": $pid," >> $TEMP_FILE
            echo "\"user\": \"$user\"," >> $TEMP_FILE
            echo "\"cpu_percent\": $cpu," >> $TEMP_FILE
            
            # 处理命令字符串中的特殊字符
            if command_exists sed; then
                CMD_ESCAPED=$(echo "$cmd" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g' 2>/dev/null)
            else
                CMD_ESCAPED="$cmd"
            fi
            echo "\"command\": \"$CMD_ESCAPED\"" >> $TEMP_FILE
            
            # 检查是否是最后一行
            if [ "$LAST_PID" = "$pid" ]; then
                echo "}" >> $TEMP_FILE
            else
                echo "}," >> $TEMP_FILE
            fi
        done
    fi
    echo "]," >> $TEMP_FILE

    # 内存占用前5的进程
    echo "\"top_memory_processes\": [" >> $TEMP_FILE
    TOP_MEM_PROCESSES=$(ps -eo pid,user,%mem,cmd --sort=-%mem 2>/dev/null | head -n 6 | tail -n +2)
    if [ -z "$TOP_MEM_PROCESSES" ]; then
        echo "获取内存占用前5进程失败" >&2
    else
        LAST_PID=$(echo "$TOP_MEM_PROCESSES" | tail -n1 | awk '{print $1}')
        
        echo "$TOP_MEM_PROCESSES" | while read -r pid user mem cmd; do
            echo "{" >> $TEMP_FILE
            echo "\"pid\": $pid," >> $TEMP_FILE
            echo "\"user\": \"$user\"," >> $TEMP_FILE
            echo "\"memory_percent\": $mem," >> $TEMP_FILE
            
            # 处理命令字符串中的特殊字符
            if command_exists sed; then
                CMD_ESCAPED=$(echo "$cmd" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g' 2>/dev/null)
            else
                CMD_ESCAPED="$cmd"
            fi
            echo "\"command\": \"$CMD_ESCAPED\"" >> $TEMP_FILE
            
            # 检查是否是最后一行
            if [ "$LAST_PID" = "$pid" ]; then
                echo "}" >> $TEMP_FILE
            else
                echo "}," >> $TEMP_FILE
            fi
        done
    fi
    echo "]" >> $TEMP_FILE
else
    echo "ps命令不存在，无法获取TOP进程" >&2
    echo "\"top_cpu_processes\": []," >> $TEMP_FILE
    echo "\"top_memory_processes\": []" >> $TEMP_FILE
fi

echo "}" >> $TEMP_FILE

# 结束JSON
echo "}" >> $TEMP_FILE

# 添加成功标志
if command_exists sed; then
    sed -i '1s/^/{"success":true,/' $TEMP_FILE 2>/dev/null || {
        # 如果sed失败，使用临时文件替代
        TEMP_FILE2=$(mktemp)
        echo '{"success":true,' > $TEMP_FILE2
        cat $TEMP_FILE | tail -n +2 >> $TEMP_FILE2
        mv $TEMP_FILE2 $TEMP_FILE
    }
else
    # 如果sed不可用，使用临时文件替代
    TEMP_FILE2=$(mktemp)
    echo '{"success":true,' > $TEMP_FILE2
    cat $TEMP_FILE | tail -n +2 >> $TEMP_FILE2
    mv $TEMP_FILE2 $TEMP_FILE
fi

# 输出结果
cat $TEMP_FILE

# 清理临时文件
rm $TEMP_FILE 2>/dev/null
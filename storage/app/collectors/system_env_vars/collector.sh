#!/bin/bash
# 系统环境变量采集脚本
# 输出标准JSON格式

# JSON 转义函数
escape_json() {
    local str="$1"
    echo "$str" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g' -e ':a;N;$!ba;s/\n/ /g' -e 's/\r/ /g'
}

# 获取环境变量并输出 JSON
get_env_vars() {
    local collected_at="$(date +"%Y-%m-%d %H:%M:%S")"
    local total_env_vars="$(env | wc -l)"
    local hostname="$(hostname)"

    # 定义字段顺序
    declare -a keys=("path" "home" "user" "shell" "lang" "timezone" "hostname" "pwd" "java_home" "python_path" "node_path" "total_env_vars" "collected_at")

    echo "{"
    local first=true

    for key in "${keys[@]}"; do
        local value=""
        case "$key" in
            path) value="$PATH" ;;
            home) value="$HOME" ;;
            user) value="$USER" ;;
            shell) value="$SHELL" ;;
            lang) value="${LANG:-not_set}" ;;
            timezone) value="${TZ:-$(date +%Z)}" ;;
            hostname) value="$hostname" ;;
            pwd) value="$PWD" ;;
            java_home) [ -n "$JAVA_HOME" ] && value="$JAVA_HOME" ;;
            python_path) [ -n "$PYTHON_PATH" ] && value="$PYTHON_PATH" ;;
            node_path) [ -n "$NODE_PATH" ] && value="$NODE_PATH" ;;
            total_env_vars) value="$total_env_vars" ;;
            collected_at) value="$collected_at" ;;
        esac

        # 如果值为空，则跳过
        [ -z "$value" ] && continue

        if [ "$first" = true ]; then
            first=false
        else
            echo ","
        fi

        echo -n "  \"${key}\": \"$(escape_json "$value")\""
    done

    echo
    echo "}"
}

# 执行采集
get_env_vars
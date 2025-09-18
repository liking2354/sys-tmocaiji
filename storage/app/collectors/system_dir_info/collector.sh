#!/bin/bash
set -e

# 转义 JSON 中的特殊字符
escape_json() {
    echo "$1" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g'
}

ROOT="/"
children="["

# 遍历一级目录
for DIR in "$ROOT"/*; do
    [[ -d "$DIR" ]] || continue
    # 去掉可能的换行符，保证 JSON 一行输出
    clean_dir=$(echo "$DIR" | tr -d '\n' | tr -d '\r')
    children+="\"$(escape_json "$clean_dir")\","
done

# 去掉最后一个逗号
children="${children%,}]"

# 拼接 JSON
json="{\"filesystem\":{\"path\":\"$ROOT\",\"children\":$children}}"

# 输出到终端（单行 JSON）
echo "$json"
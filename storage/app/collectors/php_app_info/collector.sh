#!/bin/bash
# PHP 配置采集脚本 + 依赖分析
# 自动收集 define() / array 配置，并生成 JSON

ROOT_DIR="/wwwroot"

escape_json() {
    echo "$1" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g' -e 's/\r//g' -e ':a;N;$!ba;s/\n/\\n/g'
}

# 开始 JSON 数组
echo "["

FIRST_FILE=1
# 遍历 config/*.php 文件（排除 test/demo/sample/framework/vendor/lib）
find "$ROOT_DIR" -type f -path "*/config/*.php" \
    ! -path "*/test/*" ! -path "*/tests/*" ! -path "*/demo/*" ! -path "*/sample/*" \
     ! -path "*/YiiSmarty/*" ! -path "*/framework/*" ! -path "*/vendor/*" ! -path "*/lib/*" | while read -r FILE; do
    [ -f "$FILE" ] || continue

    # 添加逗号分隔符（除了第一个文件）
    if [ $FIRST_FILE -eq 0 ]; then
        echo ","
    fi
    FIRST_FILE=0

    echo "  {"
    echo "    \"file\": \"$(escape_json "$FILE")\","

    ###### 采集 define() 常量 ######
    echo "    \"defines\": {"
    FIRST_DEFINE=1
    # 使用更稳健的方法提取 define()
    grep -E "define\\s*\$[^)]+\$" "$FILE" 2>/dev/null | while read -r LINE; do
        # 提取 define 语句中的键和值
        KEY=$(echo "$LINE" | sed -E "s/.*define\\s*\$\\s*['\"]([^'\"]+)['\"].*/\1/" 2>/dev/null)
        VALUE=$(echo "$LINE" | sed -E "s/.*define\\s*\\(\\s*[^,]+\\s*,\\s*['\"]?([^'\"]*)['\"]?\\s*\$.*/\1/" 2>/dev/null)
        
        # 跳过无效的键
        [ -z "$KEY" ] || [ "$KEY" = "$LINE" ] && continue
        
        if [ $FIRST_DEFINE -eq 0 ]; then
            echo ","
        fi
        FIRST_DEFINE=0
        echo -n "      \"$(escape_json "$KEY")\": \"$(escape_json "$VALUE")\""
    done
    echo
    echo "    },"

    ###### 采集数组配置 ######
    echo "    \"array_config\": {"
    FIRST_ARRAY=1
    # 提取数组键值对
    grep -E "['\"][^'\"]+['\"]\\s*=>\\s*[^,)]+" "$FILE" 2>/dev/null | while read -r LINE; do
        # 提取键和值
        KEY=$(echo "$LINE" | sed -E "s/.*['\"]([^'\"]+)['\"]\\s*=>.*/\1/" 2>/dev/null)
        VALUE=$(echo "$LINE" | sed -E "s/.*=>\\s*['\"]?([^'\"]*)['\"]?,?.*/\1/" 2>/dev/null)
        
        # 跳过无效的键
        [ -z "$KEY" ] || [ "$KEY" = "$LINE" ] && continue
        
        if [ $FIRST_ARRAY -eq 0 ]; then
            echo ","
        fi
        FIRST_ARRAY=0
        echo -n "      \"$(escape_json "$KEY")\": \"$(escape_json "$VALUE")\""
    done
    echo
    echo "    },"

    ###### 依赖关系分析 ######
    echo "    \"dependencies\": {"
    FIRST_DEP=1

    # 数据库连接
    DB=$(grep -E "(mysql|pgsql|sqlite):" "$FILE" 2>/dev/null | head -n 1)
    if [ -n "$DB" ]; then
        if [ $FIRST_DEP -eq 0 ]; then
            echo ","
        fi
        FIRST_DEP=0
        echo -n "      \"database\": \"$(escape_json "$DB")\""
    fi

    # Redis连接
    REDIS=$(grep -Ei "redis://|redis.*host" "$FILE" 2>/dev/null | head -n 1)
    if [ -n "$REDIS" ]; then
        if [ $FIRST_DEP -eq 0 ]; then
            echo ","
        fi
        FIRST_DEP=0
        echo -n "      \"redis\": \"$(escape_json "$REDIS")\""
    fi

    # Memcache连接
    MEM=$(grep -Ei "memcache|memcached" "$FILE" 2>/dev/null | head -n 1)
    if [ -n "$MEM" ]; then
        if [ $FIRST_DEP -eq 0 ]; then
            echo ","
        fi
        FIRST_DEP=0
        echo -n "      \"memcache\": \"$(escape_json "$MEM")\""
    fi

    # API URLs
    API=$(grep -E "https?://" "$FILE" 2>/dev/null | grep -v "//.*//" | head -n 1)
    if [ -n "$API" ]; then
        if [ $FIRST_DEP -eq 0 ]; then
            echo ","
        fi
        FIRST_DEP=0
        echo -n "      \"api\": \"$(escape_json "$API")\""
    fi

    # CDN
    CDN=$(grep -Ei "cdn" "$FILE" 2>/dev/null | head -n 1)
    if [ -n "$CDN" ]; then
        if [ $FIRST_DEP -eq 0 ]; then
            echo ","
        fi
        FIRST_DEP=0
        echo -n "      \"cdn\": \"$(escape_json "$CDN")\""
    fi

    echo
    echo "    }"

    echo -n "  }"
done

# 结束 JSON 数组
echo
echo "]"
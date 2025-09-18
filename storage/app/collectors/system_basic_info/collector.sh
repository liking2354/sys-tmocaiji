#!/bin/bash
set -e

escape_json() {
    echo "$1" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g'
}

OS_RELEASE=$(grep -E "^PRETTY_NAME=" /etc/os-release 2>/dev/null | cut -d= -f2 | tr -d '"')
[[ -z "$OS_RELEASE" ]] && OS_RELEASE=$(uname -s)
KERNEL=$(uname -r)

CPU_MODEL=$(grep -m1 "model name" /proc/cpuinfo | cut -d: -f2 | sed 's/^ //')
CPU_CORES=$(grep -c ^processor /proc/cpuinfo)

MEM_TOTAL=$(grep MemTotal /proc/meminfo | awk '{print $2}')
MEM_TOTAL_GB=$(awk "BEGIN {printf \"%.2f\", $MEM_TOTAL/1024/1024}")

JSON="{\
\"system\":{\
\"os_release\":\"$(escape_json "$OS_RELEASE")\",\
\"kernel\":\"$KERNEL\",\
\"cpu_model\":\"$(escape_json "$CPU_MODEL")\",\
\"cpu_cores\":$CPU_CORES,\
\"memory_gb\":$MEM_TOTAL_GB\
}\
}"

echo "$JSON"
#!/bin/bash

# 找到 nginx 主进程
nginx_pid=$(ps -eo pid,cmd | grep '[n]ginx: master process' | awk '{print $1}')
if [ -z "$nginx_pid" ]; then
  echo "Nginx 未运行"
  exit 1
fi

# 获取 nginx.conf 路径
nginx_conf=$(ps -p $nginx_pid -o args= | grep -oP '(?<=-c )\S+')
nginx_conf=${nginx_conf:-/etc/nginx/nginx.conf}

declare -A parsed_files
files_to_parse=("$nginx_conf")
parsed_output="/tmp/nginx_vhosts_raw.txt"
> $parsed_output

# 清理字符串中的换行/回车/多余空格
clean_string() {
  echo "$1" | tr -d '\n' | tr -d '\r' | sed 's/  */ /g'
}

# 解析单个配置文件函数
parse_nginx_file() {
  local file="$1"
  [ ! -f "$file" ] && return
  parsed_files["$file"]=1

  # 处理 include，忽略 *.bak 文件
  includes=$(grep -E '^\s*include\s+' "$file" | awk '{print $2}' | sed 's/;//')
  for inc in $includes; do
    for f in $(ls -1 $inc 2>/dev/null); do
      [[ "$f" == *.bak ]] && continue
      if [ -z "${parsed_files[$f]}" ]; then
        files_to_parse+=("$f")
      fi
    done
  done

  # 解析 server 和 location
  awk '
    function trim(s) { gsub(/^[ \t]+|[ \t]+$/,"",s); return s }
    BEGIN {
      server_level=0; location_level=0
      server_name=""; server_root=""; locations=""
      in_server=0; in_location=0; location_path=""; location_proxy=""; location_root=""
    }
    {
      # 去掉注释
      gsub(/#.*/,"")
      line=$0
      if(line ~ /server\s*{/) { in_server=1; server_level++; next }
      if(line ~ /}/) {
        if(in_location && location_level==1) {
          locations=locations location_path "|" location_root "|" location_proxy ";"
          in_location=0; location_path=""; location_root=""; location_proxy=""
        }
        if(in_server && server_level==1) {
          print server_name "|" server_root "|" locations
          in_server=0; server_name=""; server_root=""; locations=""; location_path=""; location_proxy=""; location_root=""
        }
        if(server_level>0) server_level--
        if(location_level>0) location_level--
        next
      }

      if(in_server) {
        if($1=="server_name") {
          server_name_line=""
          for(i=2;i<=NF;i++) server_name_line=server_name_line $i " "
          gsub(/;/,"",server_name_line)
          server_name=trim(server_name_line)
        }
        if($1=="root") { server_root=$2; gsub(/;/,"",server_root) }
        if($1=="location") {
          location_path=$2; gsub(/[{}]/,"",location_path)
          in_location=1; location_level++
        }
        if(in_location) {
          if($1=="proxy_pass") { location_proxy=$2; gsub(/;/,"",location_proxy) }
          if($1=="root") { location_root=$2; gsub(/;/,"",location_root) }
        }
      }
    }
  ' "$file" >> $parsed_output
}

# 遍历所有待解析文件
while [ ${#files_to_parse[@]} -gt 0 ]; do
  f="${files_to_parse[0]}"
  files_to_parse=("${files_to_parse[@]:1}")
  parse_nginx_file "$f"
done

# JSON 输出，去重
declare -A server_unique
echo "{"
echo "  \"nginx_conf\": \"$nginx_conf\","
echo "  \"vhosts\": ["
first=1
while IFS="|" read -r server_name server_root locations; do
  [[ -z "$server_name" && -z "$server_root" ]] && continue
  key="${server_name}_${server_root}_${locations}"
  [[ -n "${server_unique[$key]}" ]] && continue
  server_unique[$key]=1

  server_name=$(clean_string "$server_name")
  server_root=$(clean_string "$server_root")

  [ $first -eq 0 ] && echo ","
  first=0
  echo "    {"
  echo "      \"server_name\": \"$server_name\","
  echo "      \"root\": \"$server_root\","
  echo "      \"locations\": ["
  loc_first=1
  IFS=";" read -ra locs <<< "$locations"
  for loc in "${locs[@]}"; do
    [[ -z "$loc" ]] && continue
    IFS="|" read -r path root proxy <<< "$loc"

    path=$(clean_string "$path")
    root=$(clean_string "$root")
    proxy=$(clean_string "$proxy")

    [ $loc_first -eq 0 ] && echo ","
    loc_first=0
    echo "        {\"path\": \"$path\", \"root\": \"$root\", \"proxy_pass\": \"$proxy\"}"
  done
  echo "      ]"
  echo -n "    }"
done < $parsed_output
echo
echo "  ]"
echo "}"
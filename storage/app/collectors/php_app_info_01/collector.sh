#!/bin/bash
set -e

OUTPUT=$(mktemp)
echo '{' > $OUTPUT

# ==== system ====
echo '"system": {' >> $OUTPUT
echo "\"os_release\": \"$(cat /etc/*release | head -n1)\"," >> $OUTPUT
echo "\"kernel\": \"$(uname -r)\"," >> $OUTPUT
echo "\"cpu\": \"$(lscpu | grep 'Model name' | sed 's/Model name:[ \t]*//')\"" >> $OUTPUT
echo '},' >> $OUTPUT

# ==== php ====
PHP_VERSION=$(php -v 2>/dev/null | head -n1)
echo "\"php\": { \"version\": \"${PHP_VERSION}\" }," >> $OUTPUT

# ==== nginx ====
NGINX_PROC=$(pgrep -a nginx | head -n1)
NGINX_BIN=$(echo $NGINX_PROC | awk '{print $2}')
if [ -x "$NGINX_BIN" ]; then
  NGINX_CONF=$($NGINX_BIN -t 2>&1 | grep 'nginx.conf' | awk -F 'nginx.conf' '{print $1 "nginx.conf"}' | head -n1)
fi

echo '"nginx": {' >> $OUTPUT
echo "\"process\": {\"proc\": \"${NGINX_PROC}\"}," >> $OUTPUT
echo "\"config_file\": \"${NGINX_CONF}\"," >> $OUTPUT
echo '"server_blocks": [' >> $OUTPUT

if [ -n "$NGINX_CONF" ]; then
  grep -R "server_name" $(dirname $NGINX_CONF)/*.conf 2>/dev/null | while read line; do
    FILE=$(echo $line | cut -d: -f1)
    SERVER_NAME=$(echo $line | sed -E 's/.*server_name[ \t]+([^;]+);/\1/')
    ROOT=$(grep -E "root " $FILE | awk '{print $2}' | sed 's/;//')
    PROXY=$(grep -E "proxy_pass" $FILE | awk '{print $2}' | sed 's/;//')
    echo "{ \"file\": \"$FILE\", \"server_name\": [\"$SERVER_NAME\"], \"root\": \"$ROOT\", \"proxy_pass\": \"$PROXY\" }," >> $OUTPUT
  done
fi

echo '],' >> $OUTPUT
echo '},' >> $OUTPUT

# ==== applications ====
echo '"applications": [' >> $OUTPUT
for DIR in /alidata /wwwroot /srv /opt /var/www; do
  [ -d "$DIR" ] || continue

  FRAMEWORK="unknown"
  if [ -f "$DIR/wp-config.php" ]; then FRAMEWORK="WordPress"; fi
  if [ -f "$DIR/artisan" ]; then FRAMEWORK="Laravel"; fi
  if [ -d "$DIR/thinkphp" ]; then FRAMEWORK="ThinkPHP"; fi
  if [ -d "$DIR/protected" ] && [ -f "$DIR/framework/yii.php" ]; then FRAMEWORK="Yii1"; fi
  if [ -d "$DIR/vendor/yiisoft/yii2" ]; then FRAMEWORK="Yii2"; fi
  if [ -f "$DIR/bin/console" ] && [ -d "$DIR/vendor/symfony" ]; then FRAMEWORK="Symfony"; fi
  if [ -f "$DIR/system/core/CodeIgniter.php" ]; then FRAMEWORK="CodeIgniter"; fi
  if [ -f "$DIR/core/lib/Drupal.php" ]; then FRAMEWORK="Drupal"; fi
  if [ -f "$DIR/configuration.php" ] && grep -qi Joomla $DIR/configuration.php 2>/dev/null; then FRAMEWORK="Joomla"; fi

  echo "{" >> $OUTPUT
  echo "\"path\": \"$DIR\"," >> $OUTPUT
  echo "\"detected_by\": []," >> $OUTPUT
  echo "\"framework\": \"$FRAMEWORK\"," >> $OUTPUT
  echo "\"configs\": {" >> $OUTPUT

  # database
  DB=$(grep -R -E "DB_HOST|mysql" $DIR 2>/dev/null | head -n5 | sed 's/"/\\"/g')
  echo "\"database\": [\"$DB\"]," >> $OUTPUT
  # redis
  REDIS=$(grep -R -i "redis" $DIR 2>/dev/null | head -n5 | sed 's/"/\\"/g')
  echo "\"redis\": [\"$REDIS\"]," >> $OUTPUT
  # object storage
  OSS=$(grep -R -i "oss" $DIR 2>/dev/null | head -n5 | sed 's/"/\\"/g')
  echo "\"object_storage\": [\"$OSS\"]," >> $OUTPUT
  # mq
  MQ=$(grep -R -i "mq" $DIR 2>/dev/null | head -n5 | sed 's/"/\\"/g')
  echo "\"message_queue\": [\"$MQ\"]," >> $OUTPUT
  # third party
  TP=$(grep -R -E "http://" $DIR 2>/dev/null | head -n5 | sed 's/"/\\"/g')
  echo "\"third_party\": [\"$TP\"]" >> $OUTPUT

  echo "}" >> $OUTPUT
  echo "}," >> $OUTPUT
done
echo '],' >> $OUTPUT

# ==== dependencies ====
echo '"dependencies": {' >> $OUTPUT
if pgrep mysqld >/dev/null; then
  echo '"mysql": {"running": true},' >> $OUTPUT
else
  echo '"mysql": {"running": false},' >> $OUTPUT
fi
if pgrep redis-server >/dev/null; then
  echo '"redis": {"running": true},' >> $OUTPUT
else
  echo '"redis": {"running": false},' >> $OUTPUT
fi
if pgrep memcached >/dev/null; then
  echo '"memcached": {"running": true}' >> $OUTPUT
else
  echo '"memcached": {"running": false}' >> $OUTPUT
fi
echo '}' >> $OUTPUT

echo '}' >> $OUTPUT

cat $OUTPUT
rm -f $OUTPUT
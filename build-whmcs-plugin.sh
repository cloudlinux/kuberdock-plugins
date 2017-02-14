#!/usr/bin/env bash
set -e

#yum install -y zip

NAME=kuberdock-whmcs-plugin
NOW=$(pwd)
SOURCES_DIR=${1:-"./kuberdock-whmcs-plugin"}
VERSION=$(grep "'version' =>" $SOURCES_DIR/modules/addons/KuberDock/KuberDock.php | grep -oP "\d+\.\d+(.\d)?")

echo "########## Building KD WHMCS plugin. ##########"
wget https://getcomposer.org/composer.phar
php -d allow_url_fopen=1 composer.phar install --no-dev -d kuberdock-whmcs-plugin/modules/servers/KuberDock
rm -f composer.phar
cd $SOURCES_DIR
zip -rq $NOW/$NAME.zip ./
cd $NOW
echo "########## Done zip archive. Find $NAME.zip ##########"


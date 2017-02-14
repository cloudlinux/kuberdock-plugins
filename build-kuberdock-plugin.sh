#!/usr/bin/env bash
set -e

yum install -y rpm-build

NOW=$(pwd)
SOURCES_DIR=${1:-"./kuberdock-plugin"}
DST=${2:-$NOW}
NAME=kuberdock-plugin

DIST=$(rpm -q --queryformat '%{RELEASE}' rpm | grep -o el[[:digit:]]*\$)
VERSION=$(grep "Version:" $SOURCES_DIR/kuberdock-plugin.spec | grep -oP "\d+\.\d+.*")
BUILD_VER=$(grep "Release:" $SOURCES_DIR/kuberdock-plugin.spec | sed -rn 's/.*: (.*)%\{\?dist\}(.*)/\1.'$DIST'\2/p' | tr -d '[:blank:]')
TMP_PATH="/tmp/$NAME-$VERSION"

cd $SOURCES_DIR
if [ -n "$KD_GIT_REF" ]; then
    echo "########## Building KD RPM of '$KD_GIT_REF' version. Changes not in this version are ignored. ##########"
    git archive --format=tar --prefix=$NAME-$VERSION/ $KD_GIT_REF | bzip2 -9 > $TMP_PATH.tar.bz2
else
    echo "########## Building KD RPM from the current state of repo. All changes are included. ##########"
    rm -rf "$TMP_PATH"
    mkdir "$TMP_PATH"
    rsync -aP --quiet --exclude=".*" --exclude="*.rpm" . "$TMP_PATH/"
    cd /tmp
    tar -cjf "$NAME-$VERSION.tar.bz2" "$NAME-$VERSION"
    cd -
fi

mkdir -p /root/rpmbuild/{SPECS,SOURCES}/

cp $NAME.spec /root/rpmbuild/SPECS/
mv "/tmp/$NAME-$VERSION.tar.bz2" /root/rpmbuild/SOURCES/

echo "########## Starting the RPM build ##########"
rpmbuild --quiet -bb /root/rpmbuild/SPECS/$NAME.spec
EXTRA_NAME=".noarch.rpm"
cp -f "/root/rpmbuild/RPMS/noarch/$NAME-$VERSION-$BUILD_VER$EXTRA_NAME" "$DST/kuberdock-plugin.rpm"
echo "########## Done RPM build. Find kuberdock-plugin.rpm ##########"
cd "$NOW"

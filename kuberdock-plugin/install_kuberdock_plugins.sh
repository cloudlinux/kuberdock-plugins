#!/bin/sh

PLUGIN_NAME="KuberDock"
SOURCE_PATH=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

CPANEL_CGI_PATH=/usr/local/cpanel/whostmgr/cgi
CPANEL_TEMPLATE_PATH=/usr/local/cpanel/base/frontend
CONFIG_FILE=/var/cpanel/apps/kuberdock_whmcs.json

function install
{
     # client
    CLIENT_SOURCE_PATH="$SOURCE_PATH/client-plugin/cpanel"
    CONF_PATH="$CLIENT_SOURCE_PATH/conf"

    for template in $( /bin/find $CPANEL_TEMPLATE_PATH -mindepth 1 -maxdepth 1 -type d ! -type l ); do
        if [ ! -e $template/dynamicui ]; then
            mkdir $template/dynamicui
        fi
        /bin/cp $CONF_PATH/dynamicui_kuberdockgroup.conf $template/dynamicui
        /bin/cp -R $CLIENT_SOURCE_PATH/$PLUGIN_NAME $template
    done

    if [ -e /usr/local/cpanel/scripts/install_plugin ]; then
        /bin/tar -cjf $CONF_PATH/kuberdock-plugin.tar.bz2 -C $CONF_PATH/kuberdock-plugin/ .
        /usr/local/cpanel/scripts/install_plugin $CONF_PATH/kuberdock-plugin.tar.bz2
    fi

    for plugin in $( /bin/find $CONF_PATH/*.plugin -type f ); do
        /usr/local/cpanel/bin/register_cpanelplugin $plugin
    done

    # admin
    ADMIN_SOURCE_PATH=$SOURCE_PATH/admin-plugin/cpanel
    APP_PATH=/var/cpanel/apps

    for conf in $( /bin/find $ADMIN_SOURCE_PATH/conf/*.conf -type f ); do
        /usr/local/cpanel/bin/register_appconfig $conf
    done

    /bin/cp -R $ADMIN_SOURCE_PATH/cgi/$PLUGIN_NAME $CPANEL_CGI_PATH/$PLUGIN_NAME
    /bin/cp $ADMIN_SOURCE_PATH/cgi/addon_kuberdock.cgi $CPANEL_CGI_PATH
    /bin/chmod ugo+x $CPANEL_CGI_PATH/addon_kuberdock.cgi
    /bin/chmod -R 600 $CPANEL_CGI_PATH/$PLUGIN_NAME

    if [[ -e /etc/kubecli.conf && ! -e /root/.kubecli.conf ]]; then
        /bin/cp -f /etc/kubecli.conf /root/.kubecli.conf
    fi

    # install API
    /bin/cp -Rf $ADMIN_SOURCE_PATH/module/admin /usr/local/cpanel/bin
    /bin/cp -Rf $ADMIN_SOURCE_PATH/module/API /usr/local/cpanel/Cpanel
    /bin/chmod -R 700 /usr/local/cpanel/bin/admin/KuberDock/Module

    echo "Plugin installed"
}

function uninstall
{
    # client
    CLIENT_SOURCE_PATH=$SOURCE_PATH/client-plugin/cpanel
    CONF_PATH=$CLIENT_SOURCE_PATH/conf

    for plugin in $( /bin/find $CONF_PATH/*.plugin -type f ); do
        /usr/local/cpanel/bin/unregister_cpanelplugin $plugin
    done

    for template in $( /bin/find $CPANEL_TEMPLATE_PATH -mindepth 1 -maxdepth 1 -type d ! -type l ); do
        if [ -e $template/dynamicui/dynamicui_kuberdockgroup.conf ]; then
            /bin/rm $template/dynamicui/dynamicui_kuberdockgroup.conf
        fi

        if [ -e $template/$PLUGIN_NAME ]; then
            /bin/rm -R $template/$PLUGIN_NAME
        fi

        if [ -e $template/dynamicui ]; then
            for plugin in $( /bin/find $template/dynamicui/*kuberdock*.conf -type f ); do
                /bin/rm -f $plugin
            done
        fi
    done

    if [ -e /root/.kuberdock_pre_apps ]; then
        /bin/rm -R /root/.kuberdock_pre_apps
    fi

    # admin
    ADMIN_SOURCE_PATH=$SOURCE_PATH/admin-plugin/cpanel
    APP_PATH=/var/cpanel/apps

    for conf in $( /bin/find $ADMIN_SOURCE_PATH/conf/*.conf -type f ); do
        /usr/local/cpanel/bin/unregister_appconfig $conf
    done

    if [ -e $CONFIG_FILE ]; then
        /bin/rm -f $CONFIG_FILE
    fi

    if [ -e $CPANEL_CGI_PATH/addon_kuberdock.cgi ]; then
        /bin/rm $CPANEL_CGI_PATH/addon_kuberdock.cgi
    fi

    if [ -e $CPANEL_CGI_PATH/$PLUGIN_NAME ]; then
        /bin/rm -R $CPANEL_CGI_PATH/$PLUGIN_NAME
    fi

    if [ -e /var/cpanel/apps/kuberdock_key ]; then
        /bin/rm -f /var/cpanel/apps/kuberdock_key
    fi

    # API
    if [ -e /usr/local/cpanel/bin/admin/KuberDock ]; then
        /bin/rm -R /usr/local/cpanel/bin/admin/KuberDock
    fi

    if [ -e /usr/local/cpanel/Cpanel/API/KuberDock.pm ]; then
        /bin/rm -f /usr/local/cpanel/Cpanel/API/KuberDock.pm
    fi

    echo "Plugin uninstalled"
}

function upgrade
{
     # client
    CLIENT_SOURCE_PATH="$SOURCE_PATH/client-plugin/cpanel"
    CONF_PATH="$CLIENT_SOURCE_PATH/conf"

    for template in $( /bin/find $CPANEL_TEMPLATE_PATH -mindepth 1 -maxdepth 1 -type d ! -type l ); do
        /bin/cp $CONF_PATH/dynamicui_kuberdockgroup.conf $template/dynamicui
        if [ -e $template/$PLUGIN_NAME ]; then
            /bin/rm -R $template/$PLUGIN_NAME
        fi
        /bin/cp -R $CLIENT_SOURCE_PATH/$PLUGIN_NAME $template
    done

    if [ -e /usr/local/cpanel/scripts/install_plugin ]; then
        /bin/tar -cjf $CONF_PATH/kuberdock-plugin.tar.bz2 -C $CONF_PATH/kuberdock-plugin/ .
        /usr/local/cpanel/scripts/install_plugin $CONF_PATH/kuberdock-plugin.tar.bz2
    fi

    for plugin in $( /bin/find $CONF_PATH/*.plugin -type f ); do
        /usr/local/cpanel/bin/register_cpanelplugin $plugin
    done

    # admin
    ADMIN_SOURCE_PATH=$SOURCE_PATH/admin-plugin/cpanel
    APP_PATH=/var/cpanel/apps

    for conf in $( /bin/find $ADMIN_SOURCE_PATH/conf/*.conf -type f ); do
        /usr/local/cpanel/bin/register_appconfig $conf
    done

    /bin/rm -R $CPANEL_CGI_PATH/$PLUGIN_NAME
    /bin/cp -R $ADMIN_SOURCE_PATH/cgi/$PLUGIN_NAME $CPANEL_CGI_PATH/$PLUGIN_NAME
    /bin/cp $ADMIN_SOURCE_PATH/cgi/addon_kuberdock.cgi $CPANEL_CGI_PATH

    /bin/chmod ugo+x $CPANEL_CGI_PATH/addon_kuberdock.cgi
    /bin/chmod -R 600 $CPANEL_CGI_PATH/$PLUGIN_NAME

    if [ -e $CONFIG_FILE ]; then
        /bin/chmod 600 $CONFIG_FILE
    fi

    # install API
    /bin/cp -Rf $ADMIN_SOURCE_PATH/module/admin /usr/local/cpanel/bin
    /bin/cp -Rf $ADMIN_SOURCE_PATH/module/API /usr/local/cpanel/Cpanel
    /bin/chmod -R 700 /usr/local/cpanel/bin/admin/KuberDock/Module

    echo "Plugin upgraded"
}

function usage
{
    echo "Use following syntax to manage KuberDock install utility:"
    echo "Options:"
    echo " -i    : install KuberDock plugin"
    echo " -d    : uninstall KuberDock plugin"
    echo " -u    : upgrade KuberDock plugin"
}

if [ $1 == "-i" ]; then
    install
elif [ $1 == "-u" ]; then
    upgrade
elif [ $1 == "-d" ]; then
    uninstall
else
    usage
fi

exit 0
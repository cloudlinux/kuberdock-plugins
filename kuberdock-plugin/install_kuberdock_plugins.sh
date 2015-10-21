#!/bin/sh

PLUGIN_NAME="KuberDock"
SOURCE_PATH=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

CPANEL_CGI_PATH=/usr/local/cpanel/whostmgr/cgi
CPANEL_TEMPLATE_PATH=/usr/local/cpanel/base/frontend

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

        for plugin in $( /bin/find /usr/share/kuberdock-plugins/client-plugin/cpanel/conf/*.plugin -type f -exec basename {} .plugin \; ); do
            if [ -e $template/dynamicui/dynamicui_$plugin.conf ]; then
                /bin/rm $template/dynamicui/dynamicui_$plugin.conf
            fi
        done
    done

    # admin
    ADMIN_SOURCE_PATH=$SOURCE_PATH/admin-plugin/cpanel
    APP_PATH=/var/cpanel/apps

    for conf in $( /bin/find $ADMIN_SOURCE_PATH/conf/*.conf -type f ); do
        /usr/local/cpanel/bin/unregister_appconfig $conf
    done

    if [ -e $APP_PATH/kuberdock_whmcs.json ]; then
        /bin/rm $APP_PATH/kuberdock_whmcs.json
    fi

    if [ -e $CPANEL_CGI_PATH/addon_kuberdock.cgi ]; then
        /bin/rm $CPANEL_CGI_PATH/addon_kuberdock.cgi
    fi

    if [ -e $CPANEL_CGI_PATH/$PLUGIN_NAME ]; then
        /bin/rm -R $CPANEL_CGI_PATH/$PLUGIN_NAME
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

    echo "Plugin upgraded"
}

function usage
{
    echo "Use following syntax to manage KuberDock install utility:"
    echo "Options:"
    echo " -i    : install KuberDock plugins"
    echo " -d    : uninstall KuberDock plugins"
    echo " -u    : upgrade KuberDock plugins"
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
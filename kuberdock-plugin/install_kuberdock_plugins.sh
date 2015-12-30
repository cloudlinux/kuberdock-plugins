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
            /bin/mkdir $template/dynamicui
        fi
        /bin/cp $CONF_PATH/dynamicui_kuberdockgroup.conf $template/dynamicui
        /bin/cp -R $CLIENT_SOURCE_PATH/$PLUGIN_NAME $template
        /bin/chmod 755 $template/$PLUGIN_NAME/bin/*
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

    /bin/touch /var/log/kuberdock-plugin.log
    /bin/chmod 666 /var/log/kuberdock-plugin.log

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

    if [ -e /var/log/kuberdock-plugin.log ]; then
        /bin/rm -f /var/log/kuberdock-plugin.log
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
        /bin/chmod 755 $template/$PLUGIN_NAME/bin/*
    done

    # delete unneeded apps
    remove_bad_apps

    if [ -e /usr/local/cpanel/scripts/install_plugin ]; then
        /bin/tar -cjf $CONF_PATH/kuberdock-plugin.tar.bz2 -C $CONF_PATH/kuberdock-plugin/ .
        /usr/local/cpanel/scripts/install_plugin $CONF_PATH/kuberdock-plugin.tar.bz2
    fi

    for plugin in $( /bin/find $CONF_PATH/*.plugin -type f ! -name "*redis*" ! -name "*mysql*" ! -name "*elastic*" ! -name "*memcache*" ); do
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

    /bin/touch /var/log/kuberdock-plugin.log
    /bin/chmod 666 /var/log/kuberdock-plugin.log

    echo "Plugin upgraded"
}

function remove_bad_apps
{
    # find all installed in cpanel predefined apps
    all_installed_predefined=()
    for installed_predefined in $(kcli -c /root/.kubecli.conf kubectl get templates --origin cpanel \
        | /bin/grep -oE '(kuberdock_template_id: )[0-9]+');
    do
         if [ ! ${installed_predefined} == 'kuberdock_template_id:' ]; then
            all_installed_predefined+=(${installed_predefined})
         fi
    done

    for template in $( /bin/find $CPANEL_TEMPLATE_PATH -mindepth 1 -maxdepth 1 -type d ! -type l ); do
        # uninstall default apps
        for needless_plugin in memcache mysql elastic redis; do
            if [ -e ${template}/dynamicui/dynamicui_kuberdock-${needless_plugin}.conf ]; then
                /bin/rm -f ${template}/dynamicui/dynamicui_kuberdock-${needless_plugin}.conf
            fi
        done

        # delete uninstalled predefined apps part1
        for plugin in $( /bin/find ${template}/dynamicui -regex '.*dynamicui_kuberdock_\([0-9]+\)\.conf'); do
            if [[ ! " ${all_installed_predefined[@]} " =~ " $(echo "$plugin" | grep -o '[0-9]\+') " ]]; then
                 /bin/rm -f ${plugin}
            fi
        done
    done

    # delete uninstalled predefined apps part2
    for plugin in $( /bin/find /root/.kuberdock_pre_apps -type d -regex '.*kuberdock_\([0-9]+\)'); do
        if [[ ! " ${all_installed_predefined[@]} " =~ " $(echo "$plugin" | grep -o '[0-9]\+') " ]]; then
            /bin/rm -R ${plugin}
        fi
    done

    /usr/local/cpanel/bin/rebuild_sprites --force
}

function home_script
{
    home_script='<script src="/frontend/paper_lantern/KuberDock/assets/script/home.js"></script>'
    for template in "paper_lantern/index.auto.tmpl" "x3/index.html"; do
        index=$CPANEL_TEMPLATE_PATH/$template;
        if [ -e $index ]; then
            tail=$( tail -1 $index );
            if [[ $tail != $home_script && $1 == "add" ]]; then
                $( echo $home_script >> $index )
            fi;
            if [[ $tail == $home_script && $1 == "remove" ]]; then
                $( head -n -1 $index > tmp && mv tmp $index )
            fi;
        fi;
    done
}

function usage
{
    echo "Use following syntax to manage KuberDock install utility:"
    echo "Options:"
    echo " -i    : install KuberDock plugin"
    echo " -d    : uninstall KuberDock plugin"
    echo " -u    : upgrade KuberDock plugin"
}

if [[ $1 == "-i" ]]; then
    home_script add
    install
elif [[ $1 == "-u" ]]; then
    home_script add
    upgrade
elif [[ $1 == "-d" ]]; then
    home_script remove
    uninstall
else
    usage
fi

exit 0
#!/usr/bin/python

import os
import sys
import re
import getopt
import glob
import shutil
import subprocess

import cldetectlib as detect

PLUGIN_NAME = 'KuberDock'
SOURCE_PATH = '/usr/share/kuberdock-plugins/'

CPANEL_CGI_PATH = '/usr/local/cpanel/whostmgr/cgi/'
CPANEL_TEMPLATE_PATH = '/usr/local/cpanel/base/frontend/'


def usage():
    print ''
    print 'Use following syntax to manage KuberDock install utility:'
    print sys.argv[0]+' [OPTIONS]'
    print 'Options:'
    print ' -i | --install     : install KuberDock plugins'
    print ' -u | --uninstall   : uninstall KuberDock plugins'
    

def install_plugin():
    if cp_name == 'cPanel':
        # client
        client_source_path = SOURCE_PATH + 'client-plugin/cpanel/'
        conf_path = client_source_path + 'conf/'

        for template in get_cpanel_templates():
            template_path = CPANEL_TEMPLATE_PATH + template + '/'
            shutil.copy(conf_path + 'dynamicui_kuberdockgroup.conf', template_path + '/dynamicui')
            shutil.copytree(client_source_path + PLUGIN_NAME, template_path + PLUGIN_NAME)

        if compare_version(cp_version, '11.44') >= 0:
            exec_command(['/bin/tar', '-cjf', conf_path + 'kuberdock-plugin.tar.bz2',
                          '-C', conf_path + 'kuberdock-plugin/', '.'])
            exec_command(['/usr/local/cpanel/scripts/install_plugin', conf_path + 'kuberdock-plugin.tar.bz2'], False)

        for plugin in glob.glob(conf_path + '*.plugin'):
            exec_command(['/usr/local/cpanel/bin/register_cpanelplugin', plugin], False)

        # admin
        admin_source_path = SOURCE_PATH + 'admin-plugin/cpanel/'
        app_path = '/var/cpanel/apps/'

        for conf in glob.glob(admin_source_path + '/conf/*.conf'):
            exec_command(['/usr/local/cpanel/bin/register_appconfig', conf])

        shutil.copytree(admin_source_path + 'cgi/' + PLUGIN_NAME, CPANEL_CGI_PATH + PLUGIN_NAME)
        shutil.copy(admin_source_path + 'cgi/addon_kuberdock.cgi', CPANEL_CGI_PATH)


def uninstall_plugin():
    if cp_name == 'cPanel':
        # client
        client_source_path = SOURCE_PATH + 'client-plugin/cpanel/'
        conf_path = client_source_path + 'conf/'

        dynamic_files = ['dynamicui_' + os.path.basename(f).replace('plugin', 'conf')
                         for f in glob.glob(conf_path + '*.plugin')]

        for plugin in glob.glob(conf_path + '*.plugin'):
            exec_command(['/usr/local/cpanel/bin/unregister_cpanelplugin', plugin], False)

        for template in get_cpanel_templates():
            template_path = CPANEL_TEMPLATE_PATH + template + '/'

            for f in dynamic_files:
                path = template_path + 'dynamicui/' + f
                if os.path.exists(path):
                    os.remove(path)

            if os.path.exists(template_path + 'dynamicui/dynamicui_kuberdockgroup.conf'):
                os.remove(template_path + 'dynamicui/dynamicui_kuberdockgroup.conf')

            if os.path.exists(template_path + PLUGIN_NAME):
                shutil.rmtree(template_path + PLUGIN_NAME)

        # admin
        admin_source_path = SOURCE_PATH + 'admin-plugin/cpanel/'
        app_path = '/var/cpanel/apps/'

        for conf in glob.glob(admin_source_path + 'conf/*.conf'):
            exec_command(['/usr/local/cpanel/bin/unregister_appconfig', conf])

        if os.path.exists(app_path + 'kuberdock_whmcs.json'):
            os.remove(app_path + 'kuberdock_whmcs.json')

        if os.path.exists(CPANEL_CGI_PATH + 'addon_kuberdock.cgi'):
            os.remove(CPANEL_CGI_PATH + 'addon_kuberdock.cgi')

        if os.path.exists(CPANEL_CGI_PATH + PLUGIN_NAME):
            shutil.rmtree(CPANEL_CGI_PATH + PLUGIN_NAME)


def get_cpanel_templates():
    return [f for f in os.listdir(CPANEL_TEMPLATE_PATH)
            if os.path.isdir(os.path.join(CPANEL_TEMPLATE_PATH, f))
            and not os.path.islink(os.path.join(CPANEL_TEMPLATE_PATH, f))]


def exec_command(command, output=True):
    std_out = None

    if isinstance(command, basestring):
        command = [command]
    if not output:
        std_out = open(os.devnull, 'wb')

    subprocess.call(command, stdout=std_out)


def compare_version(ver1, ver2):
    def normalize(v):
        return [int(x) for x in re.sub(r'(\.0+)*$', '', v).split('.')]

    return cmp(normalize(ver1), normalize(ver2))

detect.getCP()
cp_name = detect.CP_NAME
cp_version = detect.CP_VERSION

try:
    opts, args = getopt.getopt(sys.argv[1:], 'hiu', ['help', 'install', 'uninstall'])
except getopt.GetoptError, err:
    print str(err)
    usage()
    sys.exit(2)

if not opts:
    usage()
    sys.exit(2)

for o, a in opts:
    if o in ('-h', '--help'):
        usage()
        sys.exit()
    elif o in ('-i', '--install'):
        try:
            install_plugin()
        except OSError:
            print 'KuberDock plugins already installed'
            sys.exit(1)
    elif o in ('-u', '--uninstall'):
        uninstall_plugin()
    else:
        usage()
        sys.exit(2)
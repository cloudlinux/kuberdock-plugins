#!/usr/bin/python

import os
import argparse
import glob
import shutil
import subprocess
import re
import json


KDCOMMON = '/usr/bin/kdcommon'
KCLI = '/usr/bin/kcli'

PLUGIN_NAME = 'KuberDock'
SOURCE_PATH = '/usr/share/kuberdock-plugin/'

CPANEL_CGI_PATH = '/usr/local/cpanel/whostmgr/cgi/'
CPANEL_TEMPLATE_PATH = '/usr/local/cpanel/base/frontend/'

PLESK_EXTENTION_TOOL = '/usr/local/psa/bin/extension'

DA_PLUGIN_PATH = '/usr/local/directadmin/plugins'


class Plugin:
    def __init__(self):
        self.panel = exec_command([KDCOMMON, 'panel', 'detect'])

    def install(self):
        action = '{0}_install'.format(self.panel.lower())
        try:
            getattr(self, action)()
        except AttributeError:
            print 'Unknown panel'
            exit(1)

        print 'Plugin installed'

    def upgrade(self):
        action = '{0}_upgrade'.format(self.panel.lower())
        try:
            getattr(self, action)()
        except AttributeError:
            print 'Unknown panel'
            exit(1)

        print 'Plugin upgraded'

    def delete(self):
        action = '{0}_delete'.format(self.panel.lower())
        try:
            getattr(self, action)()
        except AttributeError:
            print 'Unknown panel'
            exit(1)

        print 'Plugin uninstalled'

    # cPanel
    def cpanel_install(self):
        # client
        common_source_path = os.path.join(SOURCE_PATH, 'client-plugin/common')
        client_source_path = os.path.join(SOURCE_PATH, 'client-plugin/cpanel')
        conf_path = os.path.join(client_source_path, 'conf')

        for template in self.cpanel_templates():
            template_path = os.path.join(CPANEL_TEMPLATE_PATH, template)
            if os.path.isdir(os.path.join(template_path, 'dynamicui')):
                shutil.copy(os.path.join(conf_path, 'dynamicui_kuberdockgroup.conf'),
                            os.path.join(template_path, 'dynamicui'))
            if os.path.exists(os.path.join(CPANEL_TEMPLATE_PATH, template, PLUGIN_NAME)):
                shutil.rmtree(os.path.join(CPANEL_TEMPLATE_PATH, template, PLUGIN_NAME))
            exec_command(['/bin/cp', '-R', os.path.join(client_source_path, PLUGIN_NAME), template_path])
            exec_command(['/bin/cp', '-R', os.path.join(common_source_path, PLUGIN_NAME), template_path])

        exec_command(['/bin/tar', '-cjf', os.path.join(conf_path, 'kuberdock-plugin.tar.bz2'),
                      '-C', os.path.join(conf_path, 'kuberdock-plugin'), '.'])
        exec_command(['/usr/local/cpanel/scripts/install_plugin', os.path.join(conf_path, 'kuberdock-plugin.tar.bz2')])

        #for plugin in glob.glob(os.path.join(conf_path, '*.plugin')):
        #    exec_command(['/usr/local/cpanel/bin/register_cpanelplugin', plugin])

        # admin
        admin_source_path = os.path.join(SOURCE_PATH, 'admin-plugin', 'cpanel')

        for conf in glob.glob(os.path.join(admin_source_path, 'conf', '*.conf')):
            exec_command(['/usr/local/cpanel/bin/register_appconfig', conf])

        if os.path.exists(os.path.join(CPANEL_CGI_PATH, PLUGIN_NAME)):
            shutil.rmtree(os.path.join(CPANEL_CGI_PATH, PLUGIN_NAME))
        shutil.copytree(os.path.join(admin_source_path,  'cgi', PLUGIN_NAME),
                        os.path.join(CPANEL_CGI_PATH, PLUGIN_NAME))
        shutil.copy(os.path.join(admin_source_path, 'cgi', 'addon_kuberdock.cgi'), CPANEL_CGI_PATH)
        exec_command(['/bin/chmod', 'ugo+x', os.path.join(CPANEL_CGI_PATH, 'addon_kuberdock.cgi')])
        exec_command(['/bin/chmod', '-R', '600', os.path.join(CPANEL_CGI_PATH, PLUGIN_NAME)])

        # install API
        exec_command(['/bin/cp',  '-Rf',  os.path.join(admin_source_path, 'module/admin'), '/usr/local/cpanel/bin'])
        exec_command(['/bin/cp', '-Rf',  os.path.join(admin_source_path, 'module/API'), '/usr/local/cpanel/Cpanel'])
        exec_command(['/bin/chmod', '-R', '700', '/usr/local/cpanel/bin/admin/KuberDock/Module'])

        exec_command(['/bin/touch', '/var/log/kuberdock-plugin.log'])
        exec_command(['/bin/chmod', '666', '/var/log/kuberdock-plugin.log'])

        self.cpanel_home_script('append')

    def cpanel_upgrade(self):
        # client
        common_source_path = os.path.join(SOURCE_PATH, 'client-plugin/common')
        client_source_path = os.path.join(SOURCE_PATH, 'client-plugin/cpanel')
        conf_path = os.path.join(client_source_path, 'conf')

        for template in self.cpanel_templates():
            template_path = os.path.join(CPANEL_TEMPLATE_PATH, template)
            if os.path.isdir(os.path.join(template_path, 'dynamicui')):
                shutil.copy(os.path.join(conf_path, 'dynamicui_kuberdockgroup.conf'),
                            os.path.join(template_path, 'dynamicui'))
            exec_command(['/bin/cp', '-R', os.path.join(client_source_path, PLUGIN_NAME), template_path])
            exec_command(['/bin/cp', '-R', os.path.join(common_source_path, PLUGIN_NAME), template_path])

        exec_command(['/bin/tar', '-cjf', os.path.join(conf_path, 'kuberdock-plugin.tar.bz2'),
                      '-C', os.path.join(conf_path, 'kuberdock-plugin'), '.'])
        exec_command(['/usr/local/cpanel/scripts/install_plugin', os.path.join(conf_path, 'kuberdock-plugin.tar.bz2')])

        #for plugin in glob.glob(os.path.join(conf_path, '*.plugin')):
        #    exec_command(['/usr/local/cpanel/bin/register_cpanelplugin', plugin])

        # admin
        admin_source_path = os.path.join(SOURCE_PATH, 'admin-plugin', 'cpanel')

        for conf in glob.glob(os.path.join(admin_source_path, 'conf', '*.conf')):
            exec_command(['/usr/local/cpanel/bin/register_appconfig', conf])

        if os.path.exists(os.path.join(CPANEL_CGI_PATH, PLUGIN_NAME)):
            shutil.rmtree(os.path.join(CPANEL_CGI_PATH, PLUGIN_NAME))
        shutil.copytree(os.path.join(admin_source_path,  'cgi', PLUGIN_NAME),
                        os.path.join(CPANEL_CGI_PATH, PLUGIN_NAME))
        shutil.copy(os.path.join(admin_source_path, 'cgi', 'addon_kuberdock.cgi'), CPANEL_CGI_PATH)
        exec_command(['/bin/chmod', 'ugo+x', os.path.join(CPANEL_CGI_PATH, 'addon_kuberdock.cgi')])
        exec_command(['/bin/chmod', '-R', '600', os.path.join(CPANEL_CGI_PATH, PLUGIN_NAME)])

        if os.path.exists('/var/cpanel/apps/kuberdock_whmcs.json'):
            os.remove('/var/cpanel/apps/kuberdock_whmcs.json')

        # install API
        exec_command(['/bin/cp',  '-Rf',  os.path.join(admin_source_path, 'module/admin'), '/usr/local/cpanel/bin'])
        exec_command(['/bin/cp', '-Rf',  os.path.join(admin_source_path, 'module/API'), '/usr/local/cpanel/Cpanel'])
        exec_command(['/bin/chmod', '-R', '700', '/usr/local/cpanel/bin/admin/KuberDock/Module'])

        exec_command(['/bin/touch', '/var/log/kuberdock-plugin.log'])
        exec_command(['/bin/chmod', '666', '/var/log/kuberdock-plugin.log'])

        if os.path.exists('/root/.kubecli.conf'):
            exec_command([KCLI, '-c', '/root/.kubecli.conf', 'kubectl', 'register'])

        # Remove home script
        self.cpanel_remove_home_script()
        self.cpanel_home_script('append')

        templates = exec_command([KCLI, '-j', '-c', '/root/.kubecli.conf',
                                     'kubectl', 'get', 'templates', '--origin', 'cpanel'])
        try:
            template_ids = [template.get('id', None) for template in json.loads(templates)]
        except (TypeError, ValueError):
            return

        # Remove non exist apps
        if os.path.exists('/root/.kuberdock_pre_apps'):
            for template_dir in glob.glob('/root/.kuberdock_pre_apps/kuberdock_*'):
                m = re.search('(\d+)$', template_dir)
                if m and int(m.group(0)) not in template_ids:
                    shutil.rmtree(template_dir)

        for conf in glob.glob(os.path.join(CPANEL_TEMPLATE_PATH, '*', 'dynamicui', 'dynamicui_kuberdock_*.conf')):
            m = re.search('(\d+)\.conf$', conf)
            if not m:
                continue

            if int(m.group(1)) not in template_ids and os.path.exists(conf):
                os.remove(conf)
            else:   # update to new url
                try:
                    f = open(conf, 'r+')
                    lines = f.readlines()
                    f.seek(0)
                    for line in lines:
                        data = line.split(',')
                        for i, d in enumerate(data):
                            if d.startswith('url'):
                                m = re.search('template=(\d+)$', d)
                                if m:
                                    data[i] = 'url=>KuberDock/kuberdock.live.php#predefined/{0}'.format(m.group(1))
                        f.write(','.join(data))
                    f.truncate()
                    f.close()
                except IOError:
                    continue
        exec_command('/usr/local/cpanel/bin/rebuild_sprites')

    def cpanel_delete(self):
        # client
        client_source_path = os.path.join(SOURCE_PATH, 'client-plugin/cpanel')
        conf_path = os.path.join(client_source_path, 'conf')

        for plugin in glob.glob(os.path.join(conf_path, '*.plugin')):
            exec_command(['/usr/local/cpanel/bin/unregister_cpanelplugin', plugin])

        for template in self.cpanel_templates():
            template_path = os.path.join(CPANEL_TEMPLATE_PATH, template)

            for path in glob.glob(os.path.join(template_path, 'dynamicui/*kuberdock*.conf')):
                if os.path.exists(path):
                    os.remove(path)

            if os.path.exists(os.path.join(template_path, 'dynamicui/dynamicui_kuberdockgroup.conf')):
                os.remove(os.path.join(template_path, 'dynamicui/dynamicui_kuberdockgroup.conf'))

            if os.path.exists(os.path.join(template_path, PLUGIN_NAME)):
                shutil.rmtree(os.path.join(template_path, PLUGIN_NAME))

        # admin
        if os.path.exists('/root/.kuberdock_pre_apps'):
            shutil.rmtree('/root/.kuberdock_pre_apps')

        admin_source_path = os.path.join(SOURCE_PATH, 'admin-plugin/cpanel')
        app_path = '/var/cpanel/apps/'

        for conf in glob.glob(os.path.join(admin_source_path, 'conf/*.conf')):
            exec_command(['/usr/local/cpanel/bin/unregister_appconfig', conf])

        if os.path.exists(os.path.join(app_path, 'kuberdock_whmcs.json')):
            os.remove(os.path.join(app_path, 'kuberdock_whmcs.json'))

        if os.path.exists(os.path.join(CPANEL_CGI_PATH, 'addon_kuberdock.cgi')):
            os.remove(os.path.join(CPANEL_CGI_PATH,  'addon_kuberdock.cgi'))

        if os.path.exists(os.path.join(CPANEL_CGI_PATH, PLUGIN_NAME)):
            shutil.rmtree(os.path.join(CPANEL_CGI_PATH, PLUGIN_NAME))

        if os.path.exists('/var/cpanel/apps/kuberdock_key'):
            os.remove('/var/cpanel/apps/kuberdock_key')

        # API
        if os.path.exists('/usr/local/cpanel/bin/admin/KuberDock'):
            shutil.rmtree('/usr/local/cpanel/bin/admin/KuberDock')

        if os.path.exists('/usr/local/cpanel/Cpanel/API/KuberDock.pm'):
            os.remove('/usr/local/cpanel/Cpanel/API/KuberDock.pm')

        if os.path.exists('/var/log/kuberdock-plugin.log'):
            os.remove('/var/log/kuberdock-plugin.log')

        self.cpanel_home_script('delete')
        self.cpanel_remove_home_script()

    def cpanel_templates(self):
        return [f for f in os.listdir(CPANEL_TEMPLATE_PATH)
            if os.path.isdir(os.path.join(CPANEL_TEMPLATE_PATH, f))
            and not os.path.islink(os.path.join(CPANEL_TEMPLATE_PATH, f))]

    def cpanel_remove_home_script(self):
        # todo: remove when all clients don't have these files
        # Remove home script of old format
        home_script = '<script src="/frontend/paper_lantern/KuberDock/assets/script/home.js"></script>'
        for template in ['paper_lantern/index.auto.tmpl', 'x3/index.html']:
            index = os.path.join(CPANEL_TEMPLATE_PATH, template)
            if os.path.exists(index):
                f = open(index, 'r+')
                lines = f.readlines()
                f.seek(0)
                for i in lines:
                    if i.strip() != home_script:
                        f.write(i)
                f.truncate()
                f.close()

    def cpanel_home_script(self, action):
        # Remove home script of old format
        for template, file in {'paper_lantern': 'paper_lantern/index.auto.tmpl', 'x3': 'x3/index.html'}.iteritems():
            index = os.path.join(CPANEL_TEMPLATE_PATH, file)
            home_script = '<script src="/frontend/' + template + '/KuberDock/assets/script/lib/home.' + template + '.js"></script>'
            if os.path.exists(index):
                f = open(index, 'r+')
                lines = f.readlines()
                f.seek(0)
                for i in lines:
                    if i.strip() != home_script:
                        f.write(i)
                if action == 'append':
                    f.write(home_script + "\n")
                f.truncate()
                f.close()

    # Plesk
    def plesk_install(self):
        # update structure
        common_source_path = os.path.join(SOURCE_PATH, 'client-plugin/common')
        client_path = os.path.join(SOURCE_PATH, 'client-plugin')
        client_source_path = os.path.join(client_path, 'plesk')

        exec_command(['/bin/cp', '-R', os.path.join(common_source_path, 'KuberDock'),
                      os.path.join(client_source_path, 'plib', 'library')])
        exec_command(['/bin/cp', '-R', os.path.join(common_source_path, 'KuberDock', 'assets'),
                      os.path.join(client_source_path, 'htdocs')])

        # extension
        os.chdir(client_source_path)
        exec_command(['/usr/bin/zip', '-r', 'KuberDock.zip', '.'])
        exec_command(['/bin/mv', '-f', os.path.join(client_source_path, PLUGIN_NAME + '.zip'), client_path])
        exec_command([PLESK_EXTENTION_TOOL, '--install', os.path.join(client_path, PLUGIN_NAME + '.zip')])

    def plesk_upgrade(self):
        self.plesk_install()

    def plesk_delete(self):
        exec_command([PLESK_EXTENTION_TOOL, '--uninstall', PLUGIN_NAME])

    # DirectAdmin
    def directadmin_install(self):
        plugin_path = os.path.join(DA_PLUGIN_PATH, PLUGIN_NAME)
        common_source_path = os.path.join(SOURCE_PATH, 'client-plugin', 'common', PLUGIN_NAME)
        client_source_path = os.path.join(SOURCE_PATH, 'client-plugin', 'directadmin', PLUGIN_NAME)

        if not os.path.exists(DA_PLUGIN_PATH):
            os.mkdir(DA_PLUGIN_PATH)

        exec_command(['/bin/cp', '-r', client_source_path, DA_PLUGIN_PATH])
        exec_command(['/bin/cp', '-r', common_source_path, plugin_path])

        images_path = os.path.join(plugin_path, 'images')
        if not os.path.exists(images_path):
            os.mkdir(images_path)

        exec_command(['/bin/cp', '-r', os.path.join(common_source_path, 'assets'), images_path])
        exec_command(['/bin/chown', '-R', 'diradmin:diradmin', plugin_path])

        for role in ['user', 'admin', 'reseller']:
            path = os.path.join(plugin_path, role)
            if os.path.exists(path):
                for filename in glob.glob(os.path.join(path, '*')):
                    exec_command(['/bin/chmod', '755', filename])

    def directadmin_upgrade(self):
        self.directadmin_install()

    def directadmin_delete(self):
        plugin_path = os.path.join(DA_PLUGIN_PATH, PLUGIN_NAME)

        if os.path.exists(plugin_path):
            shutil.rmtree(plugin_path)


def exec_command(command, **kwargs):
    if isinstance(command, basestring):
        command = [command]

    p = subprocess.Popen(command, stdout=subprocess.PIPE, **kwargs)
    output = p.stdout.read()
    return output.strip()


def process_parser():
    parser = argparse.ArgumentParser('KuberDock plugin install utility')
    parser.set_defaults(call=wrapper)
    group = parser.add_mutually_exclusive_group()
    group.add_argument('-i', '--install', action='store_const', const='install',
                       dest='action', help='Install plugin')
    group.add_argument('-u', '--upgrade', action='store_const', const='upgrade',
                       dest='action', help='Upgrade plugin')
    group.add_argument('-d', '--delete', action='store_const', const='delete',
                       dest='action', help='Delete plugin')

    return parser


def wrapper(data):
    data = vars(data)
    getattr(Plugin(), data.get('action'))()


if __name__ == '__main__':
    parser = process_parser()
    args = parser.parse_args()

    if args.action is None:
        parser.print_help()
    else:
        args.call(args)

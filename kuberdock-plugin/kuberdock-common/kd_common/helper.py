import json
import os
import pwd
import subprocess
import re

from functools import wraps
from kd_common.exceptions import CLIError


class Utils(object):
    @classmethod
    def output(cls, func):
        @wraps(func)
        def wrapper(*args, **kwargs):
            as_json = getattr(args[0], 'json', False)
            data = func(*args, **kwargs)
            if as_json:
                print json.dumps({'status': 'OK', 'data': data})
            else:
                if isinstance(data, list):
                    print '\n'.join(map(lambda tuple_: ' '.join(tuple_), func(*args, **kwargs)))
                else:
                    print data
        return wrapper

    @classmethod
    def get_user(cls, login):
        try:
            return pwd.getpwnam(login)
        except KeyError:
            raise CLIError('User Not found')

    @classmethod
    def get_current_user(cls):
        return pwd.getpwuid(os.geteuid()).pw_name

    @classmethod
    def is_root(cls):
        return os.geteuid() == 0

    @classmethod
    def exec_command(cls, command, **kwargs):
        if isinstance(command, basestring):
            command = [command]
        p = subprocess.Popen(command, stdout=subprocess.PIPE, **kwargs)
        output = p.stdout.read()
        return output.strip()

    @classmethod
    def apache_conf_parser(cls, conf_file):
        if not os.path.isfile(conf_file):
            raise Exception('Unknown file %s' % conf_file)

        conf_data = list()
        f = open(conf_file, 'r')
        data_all = f.readlines()
        f.close()

        data = filter(lambda i: re.search('^((?!#).)*$', i), data_all)

        ID = 0
        enable = False

        result = {}
        vhost = []
        while len(data) > 0:
            out = data.pop(0)
            if "<VirtualHost" in out:
                ip_port = out.split()[1]
                port = '0'
                try:
                    ip, port = ip_port.split(':')
                    port = port.replace('>', '')
                except ValueError:
                    ip = ip_port
                vhost.append(ip)
                vhost.append(port)
                enable = True
                continue

            if "</VirtualHost>" in out:
                result[ID] = vhost
                ID+=1
                enable = False
                vhost = []
                continue

            if enable:
                vhost.append(out)
                continue

        for i in result:
            # result[i][0] is an IP
            # result[i][1] is a port
            ssl = False
            user = server_alias = None
            for line in result[i]:
                if "ServerName" in line:
                    server_name = line.split()[1].strip().replace('www.', '')
                    continue
                if "DocumentRoot" in line:
                    document_root = line.split()[1].strip()
                    continue
                if "ServerAlias" in line:
                    server_alias = ','.join(str(n) for n in line.split()[1:])
                    continue
                if "SuexecUserGroup" in line:
                    user = line.split()[1].strip()
                if "SSLEngine" in line:
                    ssl = line.split()[1].strip().lower() == 'on'

            conf_data.append({
                'user': user,
                'server_name': server_name,
                'document_root': document_root,
                'server_alias': server_alias,
                'port': int(result[i][1]),
                'ssl': ssl
            })

        return conf_data

import json
import os
import pwd
import subprocess

from functools import wraps
from exceptions import CLIError


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

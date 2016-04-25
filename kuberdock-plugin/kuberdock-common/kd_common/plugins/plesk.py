import re
import os

from kd_common.helper import Utils
from kd_common.exceptions import CLIError

PLESK_BIN = '/usr/sbin/plesk'
PLESK_ADMIN = 'psaadm'


class Plesk(object):
    def __init__(self, **args):
        self.json = args.get('json', False)

    def get_user_domains(self, user):
        if not Utils.is_root() and Utils.get_current_user() != PLESK_ADMIN and user != Utils.get_current_user():
            raise CLIError('Action do not permitted', json=self.json)

        sql = Utils.exec_command([PLESK_BIN, 'db',
                                  'SELECT d.id, d.name, d.parentDomainId FROM domains d '
                                  'LEFT JOIN clients c ON d.cl_id=c.id '
                                  'WHERE c.login="{0}" '
                                  'ORDER BY parentDomainId ASC'.format(user)])

        home_dir = Utils.get_user(user).pw_dir
        domains_list = list()

        for line in sql.split("\n"):
            m = re.match(r'^\|\s+(?P<id>\d+)\s+\|\s+(?P<domain>\S+)\s+\|\s+(?P<parentId>\d+)\s+\|', line)
            if m:
                document_root = os.path.join(home_dir, 'httpdocs') \
                    if not int(m.group('parentId')) else os.path.join(home_dir, m.group('domain'))

                domains_list.append((m.group('domain'), document_root))

        if not domains_list:
            raise CLIError('Not found', json=self.json)
        return domains_list

    def get_domain_docroot(self, user, domain):
        domains = self.get_user_domains(user)
        for d in domains:
            user_domain, docroot = d
            if user_domain == domain:
                return docroot
        raise CLIError('Not found', json=self.json)

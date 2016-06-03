import os

from kd_common.helper import Utils
from kd_common.exceptions import CLIError

DA_USERS_PATH = '/usr/local/directadmin/data/users'


class DirectAdmin(object):
    def __init__(self, **args):
        self.json = args.get('json', False)

    def get_user_domains(self, user):
        if not Utils.is_root() and user != Utils.get_current_user():
            raise CLIError('Action do not permitted', json=self.json)

        domains_list = list()
        main_domain_path = None
        user_path = os.path.join(DA_USERS_PATH, user)
        if not os.path.exists(user_path):
            return domains_list

        user_home = os.path.expanduser('~' + user)
        public_path = os.path.join(user_home, 'public_html')
        if os.path.exists(public_path) and os.path.islink(public_path):
            main_domain_path = os.path.realpath(public_path)

        httpd_conf = Utils.apache_conf_parser(os.path.join(user_path, 'httpd.conf'))
        for domain in httpd_conf:
            if domain['ssl'] is True:
                continue

            if domain['server_name'] in main_domain_path:
                domains_list.insert(0, (domain['server_name'], domain['document_root']))
            else:
                domains_list.append((domain['server_name'], domain['document_root']))

        return domains_list

    def get_domain_docroot(self, user, domain):
        domains = self.get_user_domains(user)
        for d in domains:
            user_domain, docroot = d
            if user_domain == domain:
                return docroot
        raise CLIError('Docroot Not found', json=self.json)

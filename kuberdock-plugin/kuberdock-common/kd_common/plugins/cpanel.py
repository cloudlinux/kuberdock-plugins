from kd_common.helper import Utils
from kd_common.exceptions import CLIError

USER_DATA_DOMAINS_PATH = "/etc/userdatadomains"


class CPanel(object):
    def __init__(self, **args):
        self.json = args.get('json', False)

    def get_user_domains(self, user, _path=USER_DATA_DOMAINS_PATH):
        if not Utils.is_root() and user != Utils.get_current_user():
            raise CLIError('Action do not permitted', json=self.json)

        domains_list = list()
        if '{user}' in _path:
            _path = _path.replace('{user}', user)
        for path in _path.split(';'):
            try:
                domains_file = open(path)
            except IOError:
                continue
            for n, line in enumerate(domains_file):
                if not line.strip():
                    continue

                domain, domain_raw_data = line.split(': ')
                domain_data = domain_raw_data.strip().split('==')
                user_ = domain_data[0]
                if user == user_:
                    document_root = domain_data[4]
                    main_domain = 'main' == domain_data[2]
                    if main_domain:
                        domains_list.insert(0, (domain, document_root))  # main domain must be first in list
                    else:
                        domains_list.append((domain, document_root))
            domains_file.close()
        if not domains_list:
            raise CLIError('Domains Not found', json=self.json)
        return domains_list

    def get_domain_docroot(self, user, domain):
        domains = self.get_user_domains(user)
        for d in domains:
            user_domain, docroot = d
            if user_domain == domain:
                return docroot
        raise CLIError('Docroot Not found', json=self.json)

from kd_common.helper import Utils
from kd_common.panel import Panel
from kd_common.exceptions import CLIError


class User(object):
    def __init__(self, **args):
        self.json = args.get('json', False)
        self.user = args.get('user', Utils.get_current_user())
        self.domain = args.get('domain', False)
        panel = Panel(json=self.json)
        self.panel = panel.load()

    @Utils.output
    def domains(self):
        return self.panel.get_user_domains(self.user)

    @Utils.output
    def docroot(self):
        return self.panel.get_domain_docroot(self.user, self.domain)


def parser(subs):
    panel = subs.add_parser('user')
    panel.set_defaults(call=wrapper)
    action = panel.add_subparsers(
        help="Action",
        title="Target actions",
        description="Valid actions for targets",
        dest="action")

    domains = action.add_parser('domains')
    domains.add_argument(
        '--user', '-u', required=False, help="User login", default=Utils.get_current_user()
    )

    docroot = action.add_parser('docroot')
    docroot.add_argument(
        '--user', '-u', required=False, help="User login", default=Utils.get_current_user()
    )
    docroot.add_argument(
        '--domain', '-D', required=True, help="User domain"
    )


def wrapper(data):
    args = vars(data)
    user = User(**args)
    getattr(user, args.get('action', 'domains'), 'domains')()


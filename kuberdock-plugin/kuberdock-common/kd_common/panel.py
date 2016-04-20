import os

from kd_common.helper import Utils
from kd_common.plugins.cpanel import CPanel
from kd_common.exceptions import CLIError

UNKNOWN_PANEL = 'Unknown'
PANELS = {
    'cPanel': CPanel,
}


class Panel(object):
    def __init__(self, **args):
        self.json = args.get('json', False)

    def get_current(self):
        if os.path.isfile('/usr/local/cpanel/cpanel'):
            return 'cPanel'
        elif os.path.isfile('/usr/local/psa/bin/pleskbackup'):
            return 'Plesk'
        else:
            raise CLIError(UNKNOWN_PANEL, json=self.json)

    def load(self, panel=None):
        current_panel = panel if panel else self.get_current()
        return PANELS.get(current_panel)()

    @Utils.output
    def detect(self):
        return self.get_current()


def parser(subs):
    panel = subs.add_parser('panel')
    panel.set_defaults(call=wrapper)
    action = panel.add_subparsers(
        help="Action",
        title="Target actions",
        description="Valid actions for targets",
        dest="action")

    action.add_parser('detect', help="Get name of current hosting panel")


def wrapper(data):
    args = vars(data)
    panel = Panel(**args)
    getattr(panel, args.get('action', 'detect'), 'detect')()




import json


class CLIError(SystemExit):
    message = 'Unknown error'

    def __init__(self, message=None, **kwargs):
        if message is not None:
            self.message = message
        if kwargs.get('json', False):
            message = json.dumps({'status': 'ERROR', 'message': message})
        super(CLIError, self).__init__(message)

    def __str__(self):
        return self.message

    def __repr__(self):
        return '<{0}: "{1}">'.format(self.__class__.__name__, self.message)

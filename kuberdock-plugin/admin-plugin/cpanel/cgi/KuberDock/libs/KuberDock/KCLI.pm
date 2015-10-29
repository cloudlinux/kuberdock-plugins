package KuberDock::KCLI;
use strict;
use warnings FATAL => 'all';

use Data::Dumper;
use KuberDock::JSON;

use constant KUBERDOCK_KCLI_PATH => '/usr/bin/kcli';
use constant KUBERDOCK_CONF_PATH => '/etc/kubecli.conf';

sub getTemplate {
    my ($templateId) = @_;
    return KuberDock::KCLI::execute('kubectl', 'get', 'template', '--id', $templateId);
}

sub getTemplates {
    return KuberDock::KCLI::execute('kubectl', 'get', 'templates');
}

sub createTemplate {
    my ($filename, $name) = @_;
    return KuberDock::KCLI::execute('kubectl', 'create', 'template', '-f', $filename,
        '--name', sprintf("'%s'", $name));
}

sub updateTemplate {
    my ($templateId, $filename, $name) = @_;
    return KuberDock::KCLI::execute('kubectl', 'update', 'template', '--id', $templateId,
        '-f', $filename, '--name', sprintf("'%s'", $name));
}

sub deleteTemplate {
    my ($templateId) = @_;
    return KuberDock::KCLI::execute('kubectl', 'delete', 'template', '--id', $templateId);
}

sub execute {
    my $json = KuberDock::JSON->new();
    my @defaults = (KUBERDOCK_KCLI_PATH, '--json', '-c', KUBERDOCK_CONF_PATH);
    my $command = join(' ', (@defaults, @_));

    my $response = `$command`;

    if($response eq '') {
        return ();
    }

    my $answer = $json->decode($response);
    return $answer;
}

1;
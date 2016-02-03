package KuberDock::KCLI;

use File::HomeDir;

use strict;
use warnings FATAL => 'all';

use Data::Dumper;
use KuberDock::JSON;

use constant KUBERDOCK_KCLI_PATH => '/usr/bin/kcli';
use constant KUBERDOCK_CONF_NAME => '.kubecli.conf';
use constant KUBERDOCK_TEMPLATE_ORIGIN => 'cpanel';

our $responseJSON = 0;

sub getTemplate {
    my ($templateId) = @_;
    return KuberDock::KCLI::execute('kubectl', 'get', 'template', '--id', $templateId);
}

sub getTemplates {
    return KuberDock::KCLI::execute('kubectl', 'get', 'templates', '--origin', KuberDock::KCLI::KUBERDOCK_TEMPLATE_ORIGIN);
}

sub createTemplate {
    my ($filename, $name) = @_;
    return KuberDock::KCLI::execute('kubectl', 'create', 'template', '-f', $filename,
        '--name', sprintf("'%s'", $name), '--origin', KuberDock::KCLI::KUBERDOCK_TEMPLATE_ORIGIN);
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

sub registerPanel {
    return KuberDock::KCLI::execute('kubectl', 'register');
}

sub getConfPath {
    return File::HomeDir->my_home . "/" . KUBERDOCK_CONF_NAME;
}

sub execute {
    my $json = KuberDock::JSON->new();
    my $confPath = KuberDock::KCLI::getConfPath();
    my @defaults = (KUBERDOCK_KCLI_PATH, '--json', '-c', $confPath);
    my $command = join(' ', (@defaults, @_));

    my $response = `$command 2>&1`;

    if($responseJSON) {
        return $response;
    }

    if($response eq '') {
        return ();
    }

    my $answer = $json->decode($response);
    return $answer;
}

sub setResponseType {
    my ($data) = @_;
    $responseJSON = $data;
}

1;
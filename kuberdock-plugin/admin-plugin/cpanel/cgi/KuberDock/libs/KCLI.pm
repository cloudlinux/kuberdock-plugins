package KCLI;
use strict;
use warnings FATAL => 'all';

use Data::Dumper;
use JSON;

use constant KUBERDOCK_KCLI_PATH => '/usr/bin/kcli';
use constant KUBERDOCK_CONF_PATH => '/etc/kubecli.conf';

sub getTemplate {
    my ($templateId) = @_;
    return KCLI::execute('kubectl', 'get', 'template', '--id', $templateId);
}

sub getTemplates {
    return KCLI::execute('kubectl', 'get', 'templates');
}

sub createTemplate {
    my ($filename) = @_;
    return KCLI::execute('kubectl', 'create', 'template', '-f', $filename);
}

sub updateTemplate {
    my ($templateId, $filename) = @_;
    return KCLI::execute('kubectl', 'update', 'template', '--id', $templateId, '-f', $filename);
}

sub deleteTemplate {
    my ($templateId) = @_;
    return KCLI::execute('kubectl', 'delete', 'template', '--id', $templateId);
}

sub execute {
    my $json = JSON->new();
    my @defaults = (KUBERDOCK_KCLI_PATH, '--json', '-c', KUBERDOCK_CONF_PATH);
    my $command = join(' ', (@defaults, @_));

    my $response = `$command`;
    if($response eq '') {
        return 0;
    }

    my $answer = $json->decode($response);
    return $answer;
}

1;
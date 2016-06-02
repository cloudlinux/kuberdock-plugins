package KuberDock::KubeCliConf;

use strict;
use warnings FATAL => 'all';
use Template;
use KuberDock::KCLI;
use KuberDock::API;
use Data::Dumper;

use constant KUBE_CLI_CONF_ROOT_FILE => '/root/.kubecli.conf';
use constant KUBE_CLI_CONF_ETC_FILE => '/etc/kubecli.conf';
use constant KUBERDOCK_TEMPLATE_PATH => '/usr/local/cpanel/whostmgr/docroot/cgi/KuberDock/templates';

sub new {
    my $class = shift;
    my $self = {
    };

    return bless $self, $class;
}

# we take login & password from /root/.kubecli.conf
# and url & registry - from /etc/kubecli.conf
sub read {
    my ($self) = @_;

    my $contentRoot = $self->readFile(KUBE_CLI_CONF_ROOT_FILE);
    my $contentEtc = $self->readFile(KUBE_CLI_CONF_ETC_FILE);

    if($@) {
        return {
            url => '',
            registry => 'registry.hub.docker.com',
            password => '',
            user => '',
        }
    }

    my $data = {
        url => $self->getKey($contentEtc, 'url'),
        registry => $self->getKey($contentEtc, 'registry'),
        password => $self->getKey($contentRoot, 'password'),
        user => $self->getKey($contentRoot, 'user'),
        token => $self->getKey($contentRoot, 'token'),
    };
    print Dumper($self->getKey($contentRoot, 'token'),);
    return $data;
}

sub readCGI {
    my ($self, $cgi) = @_;
    my $data = {
        url => $cgi->param('kubecli_url'),
        user => $cgi->param('kubecli_user'),
        password => $cgi->param('kubecli_password'),
        registry => $cgi->param('kubecli_registry'),
    };

    return $data;
}

sub save {
    my ($self, $data) = @_;

    my $api = new KuberDock::API($data->{url}, $data->{user}, $data->{password});
    eval {
        $data->{token} = $api->getToken();
        delete $data->{user};
        delete $data->{password};
    };

    if ($@) {
        # pass
    }

    Template->new({
        INCLUDE_PATH => KUBERDOCK_TEMPLATE_PATH,
        INTERPOLATE  => 1,
        OUTPUT => KUBE_CLI_CONF_ROOT_FILE,
    })->process('kubecli/template_root.tmpl', $data);

    Template->new({
        INCLUDE_PATH => KUBERDOCK_TEMPLATE_PATH,
        INTERPOLATE  => 1,
        OUTPUT => KUBE_CLI_CONF_ETC_FILE,
    })->process('kubecli/template_etc.tmpl', $data);

    chmod 0600, KUBE_CLI_CONF_ROOT_FILE;
    KuberDock::KCLI::setResponseType(1);
    KuberDock::KCLI::registerPanel();
}

sub getKey {
    my ($self, $content, $key) = @_;

    my ($string) = $content =~ /\r?\n$key = ([\w\d:\/\.\|]+)\r?\n?/;

    return $string;
}

sub readFile {
    my ($self, $file) = @_;

    if(!-e $file) {
        print 'File not exists.';
        return;
    }

    my $data;
    {
        local $/;
        open my $fh, '<', $file || die 'File not founded.';
        $data = <$fh>;
        close $fh;
    }

    return $data;
}

1;

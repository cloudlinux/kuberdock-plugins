package KuberDock::API;

use strict;
use warnings FATAL => 'all';

use LWP::UserAgent;
use Config::Tiny;

use KuberDock::KCLI;
use KuberDock::JSON;
use Data::Dumper;

sub new {
    my $class = shift;
    my $self = {
        _server => shift || undef,
        _username => shift || undef,
        _password => shift || undef,
    };

    my $config = Config::Tiny->read(KuberDock::KCLI::KUBERDOCK_CONF_PATH);
    $self->{_server} = $config->{global}->{url};
    $self->{_username} = $config->{defaults}->{user};
    $self->{_password} = $config->{defaults}->{password};

    return bless $self, $class;
}

sub getPackages {
    my ($self) = @_;

    return $self->request('/api/pricing/packages', 'GET');
}

sub getPackageKubes {
    my ($self, $packageId) = @_;

    return $self->request('/api/pricing/packages/' . $packageId . '/kubes', 'GET');
}

sub getPackageKubesById {
    my ($self, $packageId) = @_;

    return $self->request('/api/pricing/packages/' . $packageId . '/kubes-by-id', 'GET');
}

sub request {
    my ($self, $url, $requestType, $data) = @_;
    my $agent = LWP::UserAgent->new;

    my $endpoint = $self->{_server} . $url;

    if(!grep {$_ eq $requestType} ('GET', 'POST')) {
        warn 'Unknown request type';
    }

    my $req = HTTP::Request->new($requestType => $endpoint);
    $req->header('content-type' => 'application/json');
    $req->authorization_basic($self->{_username}, $self->{_password});

    if($requestType eq 'POST') {
        $req->content($data);
    }

    my $resp = $agent->request($req);

    if($resp->is_success) {
        return $self->getData($resp->decoded_content);
    } else {
        warn $resp->code . ': ' . $resp->message;
    }
}

sub getData {
    my ($self, $data) = @_;
    my $json = KuberDock::JSON->new;
    my $decoded = $json->decode($data);

    if($decoded->{'status'} eq 'OK' && defined $decoded->{data}) {
        return $decoded->{data};
    } else {
        warn $decoded->{message};
    }
}

1;
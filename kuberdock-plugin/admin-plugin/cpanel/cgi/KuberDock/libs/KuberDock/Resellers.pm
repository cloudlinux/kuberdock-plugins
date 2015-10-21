package KuberDock::Resellers;

use strict;
use warnings FATAL => 'all';

use Whostmgr::Resellers;
use KuberDock::JSON;
use Data::Dumper;

use constant KUBERDOCK_WHMCS_DATA_FILE => '/var/cpanel/apps/kuberdock_whmcs.json';

sub new {
    my $class = shift;
    my $self = {
        _user => $ENV{'REMOTE_USER'},
        _json => KuberDock::JSON->new(),
    };

    return bless $self, $class;
}

sub get() {
    my ($self) = @_;
    my @users = ();

    if(Whostmgr::Resellers::is_reseller($self->{_user})) {
        @users = ($self->{_user});
    } else {
        @users = ('root', keys Whostmgr::Resellers::list());
    }

    return sort @users;
}

sub save() {
    my ($self, %form) = @_;
    my %data;

    foreach my $key (keys %form) {
        next if $key eq 'a';
        if(ref($form{$key}) eq ref({})) {
            $data{$key} = $form{$key};
        } else {
            my ($reseller, $param) = split(':', $key);
            next if $param eq 'owner' || $reseller ne 'ALL';

            $data{$reseller}{$param} = $form{$key};
        }
    }

    $self->saveFile(%data);
}

sub delete() {
    my ($self, $owner) = @_;
    my $fileData = $self->loadFile();

    if(Whostmgr::Resellers::is_reseller($self->{_user}) && $self->{_user} != $owner) {
        return;
    }

    delete $fileData->{$owner};
    $self->{_json}->saveFile(KUBERDOCK_WHMCS_DATA_FILE, $fileData);
}

sub saveFile() {
    my ($self, %data) = @_;
    my $fileData = $self->loadFile();

    foreach my $key (keys %data) {
        if(Whostmgr::Resellers::is_reseller($self->{_user}) && $key != $self->{_user}) {
            next;
        }
        $fileData->{$key} = $data{$key};
    }

    $self->{_json}->saveFile(KUBERDOCK_WHMCS_DATA_FILE, $fileData);
}

sub loadData() {
    my ($self) = @_;
    my $data = $self->loadFile();

    if(Whostmgr::Resellers::is_reseller($self->{_user})) {
        if(exists $data->{$self->{_user}}) {
            $data = {
                $self->{_user} => $data->{$self->{_user}}
            };
        } else {
            $data = {};
        }
    }

    return $data;
}

sub loadFile() {
    my ($self) = @_;

    if(-e KUBERDOCK_WHMCS_DATA_FILE && !-z KUBERDOCK_WHMCS_DATA_FILE) {
        return $self->{_json}->loadFile(KUBERDOCK_WHMCS_DATA_FILE);
    } else {
        return {};
    }
}

1;
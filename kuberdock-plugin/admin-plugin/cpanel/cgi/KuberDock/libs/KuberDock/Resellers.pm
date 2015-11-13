package KuberDock::Resellers;

use strict;
use warnings FATAL => 'all';

use Whostmgr::Resellers;
use Crypt::CBC;
use KuberDock::JSON;
use Data::Dumper;

use constant KUBERDOCK_WHMCS_DATA_FILE => '/var/cpanel/apps/kuberdock_whmcs.json';
use constant KUBERDOCK_ENCRYPT_KEY => '/var/cpanel/apps/kuberdock_key';

sub new {
    my $class = shift;
    my $self = {
        _user => 'ALL',
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
        if(defined $form{$key}{password}) {
            $form{$key}{password} = $self->encrypt($form{$key}{password});
        }
        $data{$key} = $form{$key};
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

    system('/bin/chmod', 600, KUBERDOCK_WHMCS_DATA_FILE);
}

sub loadData() {
    my ($self) = @_;
    my $data = $self->loadFile();

    if(defined $data->{ALL}) {
        $data->{ALL}->{password} = $self->decrypt($data->{ALL}->{password});
    } else {
        $data = {};
    }

    return $data;
}

sub loadDefaults() {
    my ($self) = @_;
    my $data = $self->loadFile();

    if(defined $data->{defaults}) {
        return $data->{defaults};
    } else {
        return {};
    }
}

sub loadFile() {
    my ($self) = @_;

    if(-e KUBERDOCK_WHMCS_DATA_FILE && !-z KUBERDOCK_WHMCS_DATA_FILE) {
        return $self->{_json}->loadFile(KUBERDOCK_WHMCS_DATA_FILE);
    } else {
        return {};
    }
}

sub getCipher {
    my ($self) = @_;
    my $iv = '8cHr3Lng';
    my $key;

    if(-e KUBERDOCK_ENCRYPT_KEY) {
        open(FH, '<', KUBERDOCK_ENCRYPT_KEY);
        $key = <FH>;
        close(FH);
    } else {
        open(FH, '+>', KUBERDOCK_ENCRYPT_KEY);
        $key = join'', map +(0..9,'a'..'z','A'..'Z')[rand(10+26*2)], 1..56;
        print FH $key;
        close(FH);
    }

    system('/bin/chmod', 600, KUBERDOCK_ENCRYPT_KEY);

    return Crypt::CBC->new(
        -key => $key,
        -cipher => 'Blowfish',
        -iv => $iv,
        -header => 'none',
    );
}

sub encrypt {
    my ($self, $text) = @_;
    my $cipher = $self->getCipher($text);

    return $cipher->encrypt_hex($text);
}

sub decrypt {
    my ($self, $text) = @_;
    my $cipher = $self->getCipher($text);
    my $decrypted = $cipher->decrypt(pack("H*", $text));
    my $encrypted = $self->encrypt($decrypted);

    if($encrypted eq $text) {
        return $decrypted;
    } else {
        # encrypt if plain
        my $data = $self->loadFile();
        if(defined $data->{ALL}) {
            $data->{ALL}->{password} = $self->encrypt($text);
        }
        $self->saveFile(%{$data});

        return $text;
    }
}

1;
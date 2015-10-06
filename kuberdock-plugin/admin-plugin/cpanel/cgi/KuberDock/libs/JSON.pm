package JSON;

use strict;
use warnings FATAL => 'all';
use JSON::XS;
use Data::Dumper;

sub new {
    my $class = shift;
    my $self = {};

    return bless $self, $class;
}

sub decode() {
    my ($self, $data) = @_;
    my $coder = JSON::XS->new->utf8->pretty->allow_nonref;

    return $coder->decode($data);
}

sub encode() {
    my ($self, $data) = @_;
    my $coder = JSON::XS->new->utf8->pretty->allow_nonref;

    return $coder->encode($data);
}

sub loadFile() {
    my ($self, $file) = @_;
    my $json = $self->readFile($file);
    my $coder = JSON::XS->new->utf8->pretty->allow_nonref;

    return $coder->decode($json);
}

sub saveFile() {
    my ($self, $file, $data) = @_;
    my $coder = JSON::XS->new->utf8->pretty->allow_nonref;
    my $json = $coder->encode($data);

    open my $fh, '>', $file || die 'File not founded.';
    print $fh $json;
    close $fh;
}

sub readFile() {
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
package KuberDock::Validate;

use strict;
use warnings FATAL => 'all';

use Data::Dumper;

sub new {
    my $class = shift;
    my $self = {};

    return bless $self, $class;
}

sub validate {
    my ($self, $data, $rules) = @_;

    foreach my $key (keys $data) {
        if(defined $rules->{$key}) {
            foreach my $validator (keys $rules->{$key}) {
                if($self->can($validator)) {
                    my $error = $self->$validator($key, $data->{$key}, $rules->{$key}->{$validator});
                    die $error if $error;
                } else {
                    print sprintf('Validator "%s" not founded', $validator);
                    return 0;
                }
            }
        }
    }
}

sub required {
    my ($self, $name, $value) = @_;

    if($value eq '') {
        return sprintf('Empty %s', $name);
    }

    return 0;
}

sub min {
    my ($self, $name, $value, $data) = @_;

    if(length $value < $data) {
        return sprintf('Minimum length of "%s" should be %d symbols', $name, $data);
    }

    return 0;
}

sub max {
    my ($self, $name, $value, $data) = @_;

    if(length $value > $data) {
        return sprintf('Maximum length of "%s" should be %d symbols', $name, $data);
    }

    return 0;
}

sub url {
    my ($self, $name, $value, $data) = @_;

    return $self->regex(
        $value,
        '^((http(?:s)?\:\/\/)?[a-zA-Z0-9]+(?:(?:\.|\-)[a-zA-Z0-9]+)+(?:\:\d+)?(?:\/[\w\-]+)*(?:\/?|\/\w+\.[a-zA-Z]{2,4}(?:\?[\w]+\=[\w\-]+)?)?(?:\&[\w]+\=[\w\-]+)*)$',
        sprintf('Enter correct URL to %s', $name)
    );
}

# allowed characters are alphanum, minus and underscore
sub alphanum {
    my ($self, $name, $value, $data) = @_;

    return $self->regex(
        $value,
        '^([a-zA-Z0-9\-\_]+)$',
        sprintf('Only alphanum characters, minus and underscore allowed in %s', $name)
    );
}

sub regex {
    my ($self, $value, $regex, $msg) = @_;

    if($value !~ m!$regex!) {
        return $msg;
    }

    return 0;
}

1;
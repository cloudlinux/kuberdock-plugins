package Exception;
use strict;
use warnings FATAL => 'all';

our $errors = 1;

sub throw {
    my ($message) = @_;
    return if $errors > 1;

    print Exception::getFormattedMessage($message);
    $errors++;
}

sub getFormattedMessage {
    my ($message) = @_;
    my $template = '<div class="alert alert-danger" role="alert">%s</div>';

    return sprintf($template, $message);
}

1;
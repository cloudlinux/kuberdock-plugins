package KuberDock::Exception;
use strict;
use warnings FATAL => 'all';

our $errors = 1;

sub throw {
    my ($message) = @_;
    return if $errors > 1;

    print KuberDock::Exception::getFormattedMessage($message);
    $errors++;
}

sub getFormattedMessage {
    my ($message) = @_;
    $message = substr $message, 0, -abs(index($message, 'at /') - length($message));
    my $template = '<div class="alert alert-danger" role="alert">%s</div>';

    return sprintf($template, $message);
}

1;
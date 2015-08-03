#!/bin/sh
eval 'if [ -x /usr/local/cpanel/3rdparty/bin/perl ]; then exec /usr/local/cpanel/3rdparty/bin/perl -x -- $0 ${1+"$@"}; else exec /usr/bin/perl -x -- $0 ${1+"$@"};fi'
if 0;
#!/usr/bin/perl

# CloudLinux - whostmgr/docroot/cgi/addon_kuberdock.cgi Copyright(c) 2015 CloudLinux, Inc.
#                                                                  All rights Reserved.
#                                                             http://www.cloudlinux.com
#
#   This program is free software: you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation, either version 3 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
#WHMADDON:kuberdock:KuberDock

#Title: cPanel KuberDock plugin.
#Version: 0.0.1
#Author:
#Site: http://cloudLinux.com 

BEGIN { unshift @INC, '/usr/local/cpanel'; }

use warnings;
use diagnostics;

use strict;
use Whostmgr::ACLS;
use Whostmgr::HTMLInterface;
use Whostmgr::Resellers;
use Cpanel::Form;
use JSON::XS;
use Template;
use Data::Dumper;

use constant KUBERDOCK_TEMPLATE_PATH => '/usr/local/cpanel/whostmgr/docroot/cgi/KuberDock/templates';
use constant KUBERDOCK_WHMCS_DATA_FILE => '/var/cpanel/apps/kuberdock_whmcs.json';
    
Whostmgr::ACLS::init_acls();

print "Content-type: text/html\n\n";
Whostmgr::HTMLInterface::defheader('KuberDock', '/images/kuberdock.png.', '/cgi/addon_kuberdock.cgi');

my $user = $ENV{'REMOTE_USER'};

if(!Whostmgr::ACLS::hasroot() && !Whostmgr::Resellers::is_reseller($user)) {
    print qq(<div align="center"><h1>Permission denied</h1></div>);
    exit;
}

my %FORM = Cpanel::Form::parseform();

if(exists $FORM{'a'}) {
    if($FORM{'a'} eq 'add') {
        add();
    } elsif($FORM{'a'} eq 'save') {
        save();
    } elsif($FORM{'a'} eq 'delete' && exists $FORM{'o'}) {
        deleteReseller($FORM{'o'});
    }
}

my $tt = Template->new({
    INCLUDE_PATH => KUBERDOCK_TEMPLATE_PATH,
    INTERPOLATE  => 1,
}) || die "$Template::ERROR\n";


my $data = loadFile();

if(Whostmgr::Resellers::is_reseller($user)) {
    if(exists $data->{$user}) {
        $data = {
            $user => $data->{$user}
        };
    } else {
        $data = {};
    }
}

my $vars = {
    resellers => [getResellers()],
    data => $data,
};

# Render template
$tt->process('index.tmpl', $vars) || die $tt->error(), "\n";

sub add() {
    my $owner = $FORM{'newOwner'};
    my $server = $FORM{'newServer'};
    my $username = $FORM{'newUsername'};
    my $password = $FORM{'newPassword'};
    my %data;

    if($owner ne 'ALL') {
        return 0;
    }

    if($owner && $server && $username && $password) {
        $data{$owner} = {
            server => $server,
            username => $username,
            password => $password,
        };
        saveFile(%data);
        return 1;
    } else {
        return 0;
    }
}

sub save() {
    my %data;

    foreach my $key (keys %FORM) {
        next if $key eq 'a';

        my ($reseller, $param) = split(':', $key);
        next if $param eq 'owner' || $reseller ne 'ALL';

        $data{$reseller}{$param} = $FORM{$key};
    }

    saveFile(%data);
}

sub deleteReseller() {
    my $owner = shift;
    my $fileData = loadFile();

    if(Whostmgr::Resellers::is_reseller($user) && $user != $owner) {
        return;
    }

    delete $fileData->{$owner};
    my $coder = JSON::XS->new->utf8->pretty->allow_nonref;
    my $json = $coder->encode($fileData);

    open my $fh, '>', KUBERDOCK_WHMCS_DATA_FILE;
    print $fh $json;
    close $fh;
}

sub getResellers() {
    my @users = ();

    if(Whostmgr::Resellers::is_reseller($user)) {
        @users = ($user);
    } else {
        @users = ('root', keys Whostmgr::Resellers::list());
    }

    return sort @users;
}

sub loadFile() {
    if(-e KUBERDOCK_WHMCS_DATA_FILE && !-z KUBERDOCK_WHMCS_DATA_FILE) {
        my $json;
        {
          local $/;
          open my $fh, '<', KUBERDOCK_WHMCS_DATA_FILE;
          $json = <$fh>;
          close $fh;
        }

        my $coder = JSON::XS->new->utf8->pretty->allow_nonref;
        return $coder->decode($json);
    } else {
        return undef;
    }
}

sub saveFile() {
    my %data = @_;
    my $fileData = loadFile();
    my $coder = JSON::XS->new->utf8->pretty->allow_nonref;

    foreach my $key (keys %data) {
        if(Whostmgr::Resellers::is_reseller($user) && $key != $user) {
            next;
        }
        $fileData->{$key} = $data{$key};
    }

    my $json = $coder->encode($fileData);

    open my $fh, '>', KUBERDOCK_WHMCS_DATA_FILE;
    print $fh $json;
    close $fh;
}
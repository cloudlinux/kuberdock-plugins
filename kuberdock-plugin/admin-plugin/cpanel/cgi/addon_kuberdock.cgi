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
#Version: 0.1.5
#Author:
#Site: http://cloudLinux.com 

BEGIN {
    unshift @INC, '/usr/local/cpanel';
    use CGI::Carp qw(fatalsToBrowser);
}

use warnings;
use diagnostics;
use strict;
use CGI ();

use File::Basename;
use Cwd qw(abs_path);
use lib dirname(Cwd::abs_path($0)).qw(/KuberDock/libs);

use Data::Dumper;
use KuberDock::Controller;

my $app = CGI->new;

my %form = Cpanel::Form::parseform();
my $controller = KuberDock::Controller->new(\%form, $app);
$controller->run();
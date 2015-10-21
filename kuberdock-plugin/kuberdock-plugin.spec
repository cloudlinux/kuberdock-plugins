Version: 0.1
Name: kuberdock-plugin
Summary: KuberDock plugins
Release: 8%{?dist}.cloudlinux
Group: Applications/System
BuildArch: noarch
License: CloudLinux Commercial License
URL: http://www.cloudlinux.com
Source0: %{name}-%{version}.tar.bz2

Requires: kuberdock-cli

# AutoReq: 0
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%description
Kuberdock plugins

%prep
%setup -q

%build

%install
rm -rf %{buildroot}
mkdir -p %{buildroot}/usr/share/kuberdock-plugin
cp -r * %{buildroot}/usr/share/kuberdock-plugin

%clean
rm -rf %{buildroot}

%post
if [ $1 == 1 ] ; then
    bash /usr/share/kuberdock-plugin/install_kuberdock_plugins.sh -i
    exit 0
elif [ $1 == 2 ] ; then
    bash /usr/share/kuberdock-plugin/install_kuberdock_plugins.sh -u
    exit 0
fi

%preun
# uninstall?
if [ $1 == 0 ] ; then
    bash /usr/share/kuberdock-plugin/install_kuberdock_plugins.sh -d
    exit 0
fi

%files
%defattr(-,root,root,-)
%{_datadir}/kuberdock-plugin/*

%changelog

* Tue Oct 21 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-8
- cPanel. Fixed api connect to ssl host. Cgi empty values in methods.

* Tue Oct 20 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-7
- Fixed few notice errors, no whmcs product error, predefined app bugfix, added Spyc - yaml parser
- AC-1309 cPanel. Add list of predefined apps on search page, search bugfix - page should start from 1
- AC-1305 cPanel. Separate page for each app WHMCS. Notice admin if KD return old format usage
- AC-1307 cPanel. Set default package&kube_type
- cPanel. Move libs to KuberDock dir
- AC-1304 cPanel. Upload app icon by url from yaml section
- cPanel. Added cgi exceptions
- AC-674 cPanel. User notice about app start
- AC-1188 cPanel > Apps > Not handled error when it cannot connect to whmcs
- AC-999 cPanel. Parse and start yaml app

* Tue Oct 6 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-6
- AC-997 cPanel. Admin interface for yaml apps WHMCS. API handle server not found error
- AC-998 cPanel. Create pre-setup applications within admin interface
- AC-673 cPanel. Added volumes functionality
- AC-651 cPanel. Added stop and delete notifications
- AC-762 cPanel. Local storage limits
- AC-765 cPanel. Use token for kcli requests
- AC-566 cPanel Changes for kcli new port syntax

* Fri Jul 31 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-5
- Fix pod deletion
- Default controller refactoring

* Fri Jul 24 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-3
- cPanel and WHMCS fixes
- AC-630 cPanel. Search page fixes AC-671 cPanel. Public IP checkbox AC-675 cPanel.
- Application list AC-676 cPanel. Application list new template AC-696 WHMCS. Use email as
- AC-629 cPanel Add docker hub links
- AC-626 cPanel Display\edit ports and env variables AC-627 cPanel Display tooltips
- AC-616 cPanel Count and display application price
- AC-617 cPanel rename pod port and fill value
- AC-615 cPanel Calculate kube limits
- Some WHMCS and cPanel fixes

* Fri Jun 21 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-2
- Add ports interface

* Tue Jun 09 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-1
- First release
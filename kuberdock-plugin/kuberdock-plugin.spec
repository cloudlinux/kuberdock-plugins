Version: 0.1
Name: kuberdock-plugin
Summary: KuberDock plugins
Release: 6%{?dist}.cloudlinux
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
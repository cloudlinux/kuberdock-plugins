Version: 1.0
Name: kuberdock-plugin
Summary: KuberDock plugins
Release: 1%{?dist}.cloudlinux
Group: Applications/System
BuildArch: noarch
License: CloudLinux Commercial License
URL: http://www.cloudlinux.com
Source0: %{name}-%{version}.tar.bz2

Requires: kuberdock-cli >= 1.0-1

AutoReq: 0
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

%{__install} -D -d -m 755 %{buildroot}%{python_sitelib}/kd_common
%{__install} -D -m 755 kuberdock-common/kdcommon %{buildroot}%{_bindir}/kdcommon
cp -r kuberdock-common/kd_common/* %{buildroot}%{python_sitelib}/kd_common

%clean
rm -rf %{buildroot}

%post
if [ $1 = 1 ] ; then
    python /usr/share/kuberdock-plugin/install_kuberdock_plugins.py -i
    exit 0
fi
if [ $1 = 2 ] ; then
    python /usr/share/kuberdock-plugin/install_kuberdock_plugins.py -u
    exit 0
fi

%preun
# uninstall?
if [ $1 == 0 ] ; then
    python /usr/share/kuberdock-plugin/install_kuberdock_plugins.py -d
    exit 0
fi

%files
%defattr(-,root,root,-)
%{_datadir}/kuberdock-plugin/*
%{_bindir}/kdcommon
%{python_sitelib}/kd_common/*

%changelog

* Mon Mar 14 2016 Prokhor Sednev <psednev@cloudlinux.com>, Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 1.0-1
- Added referer cPanel. jquery issue for 11.54 WHMCS & cPanel. Decrease kubes
- AC-2507 cPanel & WHMCS. Upgrade kubes without redirect. cPanel & WHMCS fixed for AC-2538
- AC-2387 Pay and start without redirect to billing
- AC-2577 cPanel. Create user in KD for no billing case
- AC-2550 - cPanel check max kubes count

* Fri Mar 04 2016 Prokhor Sednev <psednev@cloudlinux.com>, Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 1.0-0.rc.5
- cPanel. Get auth data for new product
- cPanel. Fixed pay process
- cPanel. Add payAndstart logic, restart pod AC 2507.
- AC-2512 - cPanel. Cannot connect KD API via admin interface
- AC-2085 kuberdock-plugin. Add kdcommon cli utility

* Tue Feb 16 2016 Prokhor Sednev <psednev@cloudlinux.com>, Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 1.0-0.rc.4.1
- AC-2085 kuberdock-plugin. Add kdcommon cli utility
- AC-2233 - cPanel. Get and save defaults in KuberDock
- AC-2200 - part 2 - Cpanel plugin : start, stop, pod details  should work correct if predef apps yaml was deleted
- AC-2320 : fix defign PA page in cPanel v. 54.0;

* Mon Feb 08 2016 Prokhor Sednev <psednev@cloudlinux.com> 1.0-0.rc.4
- AC-2177 - Cpanel input validation
- AC-2200 - Cpanel plugin : start, stop, pod details  should work correct if predef apps yaml was deleted

* Thu Jan 14 2016 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com>, Prokhor Sednev <psednev@cloudlinux.com> 1.0-0.rc.3.1
- AC-2042 cPanel. Package-specific addition to the postDescription
- AC-1307 cPanel. Set default package&kube_type p2
- AC-1306 cPanel. Filter predefined apps p2

* Thu Jan 14 2016 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com>, Prokhor Sednev <psednev@cloudlinux.com> 1.0-0.rc.3
- cPanel. Register host in KD
- AC-2006 cPanel. KuberDock Apps > Yaml's > Memcached
- AC-2044 cPanel > KuberDock plugin > Edit kubecli.conf tab > Validation fields
- AC-2037 - cPanel > KuberDock plugin > Application defaults tab > Software error
- AC-2069 - mend in cPanel
- AC-2050 - cPanel. Prefix and suffix in PA
- AC-2017: Fix price displayed; AC-2016: Fix plan valign;

* Thu Jan 14 2016 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com>, Prokhor Sednev <psednev@cloudlinux.com> 1.0-0.rc.2
- AC-1558 cPanel > KuberDock plugin > Resellers tab > Validation fields
- AC-1927 - Possibility to edit kubecli conf in cpanel
- AC-1934 - apps sorting in cPanel

* Wed Dec 30 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com>, Prokhor Sednev <psednev@cloudlinux.com> 0.1-20
- AC-1932 cPanel. Notice for app delete, fixed error for icon param
- AC-1814 cPanel > KuberDock plugin > Apps from old version available after install new version cPanel plugin
- AC-1801 cPanel current values in application defaults tab
- cPanel. Alert style fix

* Mon Dec 28 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com>, Prokhor Sednev <psednev@cloudlinux.com> 0.1-19
- AC-1917 cPanel. Support new yaml format. Move template to own model, also calculate publicIp&PD, fixed x3 theme icons
- AC-1892: Add style to choose plan preapp in cpanel
- AC-1899 - link to support in cPanel and whmcs plugins
- Fix style in preapp install page + add some styles to choose plan page
- AC-1902 cPanel. Select plan page skeleton
- AC-1648 cPanel > KuberDock > Null error
- AC-1661 cPanel. Remove default apps

* Thu Dec 24 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-18
- AC-1904 Fix error 'value must be of string type' for sugarcrm.yaml
- cPanel. Fixed add env
- AC-1887 cPanel. Display app url if proxy section exists cPanel. Fixed template app link, display public IP\PD name on details pod page correctly
- cPanel. Fixed yaml variable regexp
- cPanel. Fixed ajax loader on app install page
- AC-1803: add style to preapp install page (cpanel client plugin)
- AC-1658 - cPanel. Rename our block with apps

* Thu Dec 17 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-17
- AC-1820 cPanel. Using variable in YAML for user`s domain usage
- AC-1646 cPanel. Display actual pod status
- AC-1742 cPanel. Display only own yaml templates

* Fri Dec 11 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-16
- cPanel. Display error if product is Pending
- AC-1645 cPanel > KuberDock > Quantity of kubes displayed incorrect
- AC-1720 cPanel > KuberDock plugin > Billing Integration tab > User can see password from console
- AC-1755 cPanel. Refactor app defaults

* Tue Dec 1 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-15
- AC-1550 cPanel. Usability issues
- AC-1656 cPanel. Use bbcode in pre\post description cPanel. Use YAML library instead Cpanel::YAML
- AC-1563 cPanel. Change predefined app details page
- AC-1559 Make cPanel Apache proxy traffic to a container (predefined app YAML settings)
- AC-1561 cPanel. Add user domain variable %USER_DOMAIN%

* Fri Nov 20 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-14
- AC-1523 cPanel. Use token from user config
- AC-1553 cPanel. Save errors to log file
- AC-1552 cPanel. User can't upload yml file
- AC-1547 cPanel. Display error if WHMCS API url are incorrect
- cPanel. Fixed pre-app vars accordingly to AC-1460
- AC-1468 cPanel. Don't allow users read whmcs config
- AC-1465 cPanel. Predefined-app page do not allow save admin token to local config

* Wed Nov 11 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-13
- cPanel. Exception & install script fix

* Tue Nov 10 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-12
- cPanel. Exception message fix, install script fixes
- cPanel. Allow upload image by https url
- AC-1449 cPanel. Fixed modals in cPanel with anigular
- AC-1337 cPanel.  Pre-defined applications > No Back button, when creating new applications
- AC-1413 cPanel. Error message if not enough funds for 1st deposit WHMCS. API kuberdockgetinfo decode server password. KD new user behavior

* Thu Nov 5 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-11
- AC-1414 cPanel. Add PUBLIC_ADDRESS variable cPanel
- Add slider for kube count variables cPanel
- Display only available templates for user
- AC-1415 cPanel. autogen fields cPanel. %var% can be used everywhere in yaml
- cPanel. Now worked with getkuberdockinfo api request cPanel. Use hostingPanel user
- Fix plugin style user side

* Fri Oct 30 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-10
- cPanel. Exception if not setted /etc/kubecli.conf

* Fri Oct 30 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-9
- cPanel. Fixed redesign bugs, added ajax file uploader
- Merge "WHMCS. Added server_id for kubes, separate products\kubes by servers  Fixed standart kube, added High CPU and High memory stadart kubes  Added exc
- WHMCS. Added server_id for kubes, separate products\kubes by servers  Fixed standart kube, added High CPU and High memory stadart kubes  Added exception
- AC-1318: Add style to predefined app cpanel plagin to admin
- Merge "AC-1318: Add style to predefined list app table"
- AC-1318: Add style to predefined list app table
- AC-1318: Add style to appdetail page
- Fixed missed product id for custom field
- AC-1318: Add style to applist table in kuberdock plugin AC-1308 cPanel. Click on back button logic AC-1306 cPanel. Different pages for list of apps cre

* Wed Oct 21 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-8
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

* Sun Jun 21 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-2
- Add ports interface

* Tue Jun 09 2015 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 0.1-1
- First release
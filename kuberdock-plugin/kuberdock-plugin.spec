Version: 1.2
Name: kuberdock-plugin
Summary: KuberDock plugins
Release: 2%{?dist}.cloudlinux
Group: Applications/System
BuildArch: noarch
License: CloudLinux Commercial License
URL: http://www.cloudlinux.com
Source0: %{name}-%{version}.tar.bz2

BuildRequires: python

Requires: kuberdock-cli >= 1.0-3

AutoReq: 0
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%define python_sitelib %(%{__python} -c "from distutils.sysconfig import get_python_lib; print get_python_lib()")

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
mkdir -p %{buildroot}%{python_sitelib}/kd_common
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
%doc LICENSE
%defattr(-,root,root,-)
%{_datadir}/kuberdock-plugin/*
%{_bindir}/kdcommon
%{python_sitelib}/kd_common/*

%changelog

* Tue Oct 18 2016 Prokhor Sednev <psednev@cloudlinux.com>, Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 1.2-2
- updated EULA
- Plesk. Fixed error for logins contains dot in name
- Plesk. Bugfix with excessive tabs
- AC-4356 DA. Add tab with available PAs for user

* Thu Oct 06 2016 Prokhor Sednev <psednev@cloudlinux.com>, Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 1.2-1
- AC-4717 Plesk. postDescription doesn't change
- Hosting panels. Validation error fixed. kuberdock_template_id moved to client side.
- Hosting panels. Remove unused class
- AC-4483 Hosting panels. Fix prices for app packages in KD
- AC-4484 Hosting panels. Forbid switching packages if this pod was edited at least once.
- AC-4366 cPanel part AC-4492 - change PA templates schema: rename domain -> baseDomain (hosting panels part)
- AC-3070 Hosting panels. Ability to switch appPackage
- Hosting panels. Define python_sitelib
- AC-4277 Encoding in postDescription
- Hosting panels. Changed kube type yaml section
- AC-3838 Hosting panels. UI improvement in KuberDock plugin for cPanel and Plesk;
- AC-4326 EBS is not being dysplayed in postDescription.
- AC-4366 Hosting panels. Display appropriate prices/resources on the PA page in case of using domain

* Tue Aug 30 2016 Prokhor Sednev <psednev@cloudlinux.com>, Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 1.2-0
- AC-4353 DA. User side > User can't buy pod after payed invoice
- AC-4354 DA. User side > No validation for PV in pod settings
- AC-4248 Hosting panels. Incorrect behavior .htaccess when user create/delete PA with proxy
- DA. Remove yaml extension validation when upload file
- AC-3717 DA. Admin have no access to admin side
- AC-3641 DA. Create yaml validation
- AC-3214 DA. Admin tab - Set defaults page
- AC-3833 Plesk. UI improvements
- AC-3230 DA. Add suid wrapper to read admin config. Fixed available kubes.
- AC-3214 DA. Admin tab - Set defaults page
- AC-3213 DA. Admin tab - Applications CRUD
- AC-3215 DA. Admin tab - Update kubecli.conf settings
- AC-3216 DA. Client tab - Client controller Plesk change get login query
- AC-3212 DA. Add DA support to plugins install script
- AC-3211 DA. KDcommon changes to support DA
- DA. Base structure

* Fri Jul 22 2016 Prokhor Sednev <psednev@cloudlinux.com>, Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 1.1-1
- AC-3871 Plugin > User panel > Endless preloader when default kube settings
- Hosting panels. Rised required kcli version to 1.0-4
- cPanel. Bugfix: deleting kubecli.conf when saving apps
- AC-2935 - Plesk. Admin page. Design. Styles
- AC-3473 - Hosting admin: Registry by default
- AC-3640 - Create yaml validation for cPanel
- AC-3639 - Create yaml validation for Plesk
- Plesk. Text changes. Hosting panel. Fixed edit link
- AC-3450 cPanel. PA icons that no exist, are displayed in the list of available applications.
- Hosting panels. Use token2 for SSE translator

* Mon Jun 13 2016 Prokhor Sednev <psednev@cloudlinux.com>, Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 1.1-0
- Hosting panels. Fixed availbale kubes, user creation
- AC-2939 Plesk. Enable rewrite engine in .htaccess
- Changed getInfo, orderProduct args
- Fixed kdcommon get user domains, catch errors on AdminLink
- AC-3354 Hosting plugin. Generate token for admin
- Further fixes
- Error fixed, when there is no .kubecli.conf file
- Plesk. Change get user login method
- AC-3003 cPanel > Remove "kube_type:" from message
- AC-3157 In hosting panels show asterisks instead of passwords
- AC-2972 Action buttons need to separate, to display them in each own column
- AC-3004 Move link to the right side
- Fixed SSE translator for cPanel\Plesk Install script catch get templates exception
- AC-2936 Plesk. Client tab - Available apps Fixed no billing logic
- AC-2934 Plesk. Admin tab - Applications. Add\Update\Delete. Install\Uninstall
- AC-2937 Plesk. Client tab - Client controller extends Base controller
- AC-2939 Plesk. Client tab - Implement Proxy mechanizm (for YAML proxy section) Separate styles.css for each panel
- AC-2929 Plesk. KDcommon changes to support Plesk
- AC-2933 Plesk. Admin tab - Set default package\kube
- AC-2930 Plesk. Update plugin install script according to new structure
- AC-2928 Plesk. Add Plesk assets component
- AC-2932 Plesk. Admin tab - Update kubecli.conf settings
- cPanel. Plesk. Base structure

* Thu Apr 28 2016 Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 1.0-4
- cPanel. Fixed Validation.pm error for cPanel v.56. Fixed no billing logic

* Thu Apr 14 2016 Prokhor Sednev <psednev@cloudlinux.com>, Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 1.0-3
- Move error log to user home directory. Change config file\log permissions
- AC-2667 cPanel. Successful kubecli.conf edit notification

* Wed Apr 06 2016 Prokhor Sednev <psednev@cloudlinux.com>, Ruslan Rakhmanberdiev <rrakhmanberdiev@cloudlinux.com> 1.0-2
- cPanel. Catch sysapi request. Fixed pod link
- AC-2729 - Add Pod IP to cpanel interface
- AC-2666 - cPanel plugin version display

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
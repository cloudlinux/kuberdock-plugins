KuberDock plugins for web control panels and WHMCS billing
============================

![KuberDock logo](kuberdock-plugin/admin-plugin/cpanel/cgi/KuberDock/assets/images/default.png "KuberDock")

KuberDock - is a platform that allows users to run applications using Docker container images and create SaaS / PaaS based on these applications.

[More about KuberDock](https://github.com/cloudlinux/kuberdock-platform)

------

## Features
- KuberDock plugin for [cPanel](https://cpanel.com/), [Plesk](https://www.plesk.com/) and [DirectAdmin](http://directadmin.com)
- KuberDock plugin for [WHMCS](http://whmcs.com/) billing

------

# Deploy control panels plugin
_Note: You may use this software in production only at your own risk_
_Note: RPM package requires [kuberdock-cli](https://github.com/cloudlinux/kuberdock-platform/tree/master/kuberdock-cli)_

To build RPM package just run `sh build-kuberdock-plugin.sh`

Further information can be found at "Shared hosting panels integration" on [docs folder](https://github.com/cloudlinux/kuberdock-platform/blob/master/docs/kd_doc.md).

# Deploy WHMCS plugin

To build zip archive with plugin run `sh build-whmcs-plugin.sh`, then copy archive to `<WHMCS_ROOT>` and make unzip.

Further information can be found at "WHMCS integration" on [docs folder](https://github.com/cloudlinux/kuberdock-platform/blob/master/docs/kd_doc.md).

# Contributing to KubeDock
If you gonna hack on KuberDock, you should know few things:
1. This is an Awesome Idea! :)
2. We are opened to discussions and PRs. Feel free to open Github issues.
3. You can contact some contributors for help. See [CONTRIBUTORS.md](CONTRIBUTORS.md)

--------

# Licensing
KuberDock plugins code itself is licensed under the GPL License, Version 2.0 (see
[LICENSE](https://github.com/cloudlinux/kuberdock-platform/blob/master/LICENSE)). 

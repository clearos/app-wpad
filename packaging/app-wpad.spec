
Name: app-wpad
Epoch: 1
Version: 2.1.0
Release: 1%{dist}
Summary: Web Proxy Auto Discovery
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base

%description
The Web Proxy Auto Discovery Protocol (WPAD) provides applications connecting to the Internet with information about proxies and rules to use when connecting to servers (local or remote).

%package core
Summary: Web Proxy Auto Discovery - Core
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-web-server-core
Requires: app-network-map-core
Requires: app-dhcp-core => 1:1.5.1

%description core
The Web Proxy Auto Discovery Protocol (WPAD) provides applications connecting to the Internet with information about proxies and rules to use when connecting to servers (local or remote).

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/wpad
cp -r * %{buildroot}/usr/clearos/apps/wpad/

install -d -m 755 %{buildroot}/var/clearos/wpad
install -D -m 0755 packaging/10-wpad %{buildroot}/etc/clearos/firewall.d/10-wpad
install -D -m 0755 packaging/wpad-init %{buildroot}/usr/sbin/wpad-init
install -D -m 0640 packaging/wpad.conf %{buildroot}/etc/clearos/wpad.conf

%post
logger -p local6.notice -t installer 'app-wpad - installing'

%post core
logger -p local6.notice -t installer 'app-wpad-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/wpad/deploy/install ] && /usr/clearos/apps/wpad/deploy/install
fi

[ -x /usr/clearos/apps/wpad/deploy/upgrade ] && /usr/clearos/apps/wpad/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-wpad - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-wpad-core - uninstalling'
    [ -x /usr/clearos/apps/wpad/deploy/uninstall ] && /usr/clearos/apps/wpad/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/wpad/controllers
/usr/clearos/apps/wpad/htdocs
/usr/clearos/apps/wpad/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/wpad/packaging
%exclude /usr/clearos/apps/wpad/unify.json
%dir /usr/clearos/apps/wpad
%dir %attr(755,webconfig,apache) /var/clearos/wpad
/usr/clearos/apps/wpad/deploy
/usr/clearos/apps/wpad/language
/usr/clearos/apps/wpad/libraries
%config(noreplace) /etc/clearos/firewall.d/10-wpad
/usr/sbin/wpad-init
%attr(0640,webconfig,webconfig) %config(noreplace) /etc/clearos/wpad.conf

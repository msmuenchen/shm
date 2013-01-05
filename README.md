SH management - the next generation of server management
===

# History
SHM got developed to manage all the servers comprising the setup of SparkHosting, as well as the clients which access the servers.

Since nothing available fit my needs or was too complex (I had experience with GOsa, but LDAP is a no-go for me), I decided to create my own toolkit.

# Features
* Supported OSes: Debian 7.0 (Wheezy), quite possibly Ubuntu 12.x (untested)
* Debian automated install using TFTP netboot and preseeding, supporting normal partitioning and crypto LVM
* Management of IP networks and address distribution to clients using DHCP servers (isc-dhcp-server) and/or static configuration via /etc/network/interfaces
* Management of UNIX users and groups in a MySQL database, as well as authentication via libpam-mysql and name-services via libnss-mysql-bg
* Management of hosts in virtual machines (VMware)
* Management of NFS exports
* Management of Apache and Lighttpd virtual hosts with full privilege separation and version management for PHP using suPHP
* Management of Exim virtual domains and users

# Dependencies
* SHM needs PHP >= 5.4.0, a MySQL server and the PHP PEAR package Console/Table.
* SHM MySQL user-authentication via PAM requires libpam-mysql and libnss-mysql-bg, as well as two MySQL users which can access the MySQL server from any machine in the network
* SHM DHCP management requires isc-dhcp-server
* SHM DNS management requires PowerDNS
* SHM virtual machine configuration support requires VMWare Workstation 9.0 or later, possibly ESXi is supported too, but untested
* SHM netboot/netinst requires tftpd and, if not used together with managed DHCP, also a DHCP server usable with netboot

# Architecture

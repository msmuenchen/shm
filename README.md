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
## Object model
1. Everything in SHM is abstracted from the basic objects of Machines and Users.
2. Every SHM object is named after the singular form (as one object represents one virtual/physical object)
3. Every SHM table is named after the plural form in all-lowercase (as every table represents multiple Objects)
4. Every SHM object has one "id" attribute, which is used to find the data in the database.
5. A Machine is a physical device with:
   * One or more network interfaces (NIC). A NIC is represented by a Machine_Interface object with
   *    * a network-wide unique MAC address - there can only be 1 Host_Interface object with the same MAC ID in the database. The MAC address is a string, exactly 17 characters long, with either : or - as separator. Notation is in 802.3 order (the same as in ifconfig output)
   *    * one or more Net_Address object(s), if this NIC will be configured with an IP address. The Network_Address has two properties:
   *    *    * IP address, string in aaa.bbb.ccc.ddd notation (IPv6 support planned - the DB supports also IPv6 addresses, but the config generators likely will produce garbage!)
   *    *    * Net_Network object:
   *    *    *    * Start IP of range
   *    *    *    * End IP of range
   *    *    *    * Router (or default gateway) to be set for all Net_Address objects using this Network
   *    *    *    * Network (No CIDR range) - this is what 'd be as "network" in /etc/network/interfaces on a Debian host
   *    *    *    * Netmask (subnet mask)
   *    *    *    * Broadcast address
   *    *    *    * DNS server 0 and 1 (currently no more supported)
   *    *    *    * DNS search domain (also, this in conjunction with the hostname, makes the FQDN of this hostname)
   *    * a "isPrimary" attribute - only one Interface can have this attribute for a Host; the primary interface is assigned the hostname in /etc/hosts
   * One or more Host objects - each describes a specific installation of an Operating System:
   *    * one (and exactly one!) OS object with the attributes:
   *    *    * Name of the OS (e.g. "Debian", "Windows", "FreeBSD", "NetBSD", "iOS", "Unix", "NoOS" for embedded stuff like routers, printers etc. which can not be managed using SHM)
   *    *    * ID of its parent OS to allow tree-building and sharing of common files like /etc/hosts, which is common for unixoids (and Windows, but PHP doesn't support polymorphy, but hey - at least all Unixoids have only one generator!)
   *    *    * Version identifier, either text or numeric (like "7" to represent Debian Wheezy, "10.8" to represent OS X Mountain Lion, etc.)
   *    *    * Architecture
   *    * one or more associated Service objects, representing the Services this Host provides to other machines (e.g. network services) or the user (desktop environments):
   *    *    * Name of the Service (NOT the package name!). Examples: "NFS", "FTP", "SMB", "HTTP", "MySQL", "DHCP"
   *    *    * Description of the Service (a short one-liner describing what the Service provides, like "NFS Server")
   *    *    * Associated default values of general configuration properties for this Service (like: the domains to which a HTTP server responds)
   *    * one or more Package objects
   *    *    * Name of the Package (like dnsmasq, apache2)
   *    *    * ID of the Service(s) this Package provides (e.g. dnsmasq will provide TFTP, DNS and DHCP)

This information is still in active development! Everything I did until now was developing proof of concept - now all the horrid hacks I did have to be converted to something usable!

# Notes
* When modelling Machines and Networks, make sure each Machine has a fully configured Machine_Interface with Net_Address and Net_Network!
* Multiple Network_Addresses for one Host and multiple Hosts with the same Network_Address are supported
*     * Anycast (aka multiple hosts with same IP) is NOT supported officially. Especially, you have to take care of ALWAYS configuring a "management IP"!
* For the lulz, in the hacks I made privately and intend to port over to SHM: iOS provisioning support, AVM Fritz!Box WLAN+LAN config generation, Mac OS X automated netboot install, Win7 x32+x64 automated netboot install
* The documentation and the source to the individual OS classes will show how I accomplished all that.
* For remote installing, I sticked to two basic principles:
*    * install a system as bare as possible and then, using a post-install script, install the Packages and configure the Services
*    * keep as close to stock as possible - don't overwrite or edit config files when the OS provides you with a native way of configuring the desired effects (like pam-auth-update on Debian/Ubuntu instead raw editing /etc/pam.d/common-*)
* When developing your own Package (actually, that's easy - import your distribution's package list :D) or Service (*hehe* config generators), please consider sharing them to the public.

# License
SHM is licensed under the AGPL v3 license. This means you have to publish to anyone you provide the SHM service (typically, the employees of the company you work for) the source of SHM and any customizations you use.
However, this does NOT include the configuration details - unless you know what you're doing, don't share them - they're like building blueprints, just for IT infrastructure.

I won't sue you for breaching the license as long as you're not doing it commercially. Commercially means for me: any company above 5 Machines. You're using a powerful Open Source product, free of any charge - the least you can do is to give something back to the Community!

# Warranties
As stated in the AGPL v3 license, there are, except when written code states otherwise, NO WARRANTIES for correct, stable operation and data loss (including data loss to hackers)!

You're using SHM fully on your own risk and responsibility.

#!/usr/bin/php
<?
//SH management

//Include this to use the API.

//config
require("/etc/sh_management/config.php");

//api-wide common functions
require($config["dir"]["api_basedir"]."api_base.php");

//if CLI, then load cli_base which will do the work
if(php_sapi_name()=="cli") {
  require($config["dir"]["api_basedir"]."cli_base.php");
}

//if not, then we're running mod_php or cgi, and got included
//by an API client which does the work itself

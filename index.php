<?php
error_reporting(E_ERROR | E_PARSE);

date_default_timezone_set("Asia/Shanghai");

defined('APP_PATH') OR define('APP_PATH', dirname(__FILE__).'/');
define("Template", APP_PATH."html/");//模板路径
define("Ext",".html");//模板扩展名
define("C_N","c");//控制器名称
define("A_N","a");//方法标识

require(APP_PATH.'Libs/Core/Core.class.php');
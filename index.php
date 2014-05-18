<?php

	/**
	 * codr Framework
	 *
	 * @author Jeppe Christiansen <jeppe@codr.dk>
	 * @version 0.1
	 * @package codr framework
	 */

	define("DIRECT_SCRIPT_ACCESS", 	true);

	error_reporting(E_ALL);

	/* Set the directory to current, if this is a CLI request. */

	if (defined("STDIN"))
	{
		chdir(dirname(__FILE__));
	}

	$sys_folder = "codr";
	$app_folder = "app";

	if (realpath($sys_folder) !== FALSE)
	{
		$sys_folder = realpath($sys_folder)."/";
	}

	//Ensure we have a trailing slash.
	$sys_folder = rtrim($sys_folder, "/")."/";

	if (!is_dir($sys_folder))
	{
		exit("The system folder is not set correctly. Please correct this at: ".__FILE__);
	}
	// PHP file extension (Has been deprecated)
	define("EXT",		".php");

	// Path to system folder
	define("BASEPATH",	str_replace("\\", "/", $sys_folder));

	// Path to application folder
	if (is_dir($app_folder))
	{
		define("APPPATH", $app_folder."/");
	}
	else
	{
		if (!is_dir(BASEPATH.$app_folder."/"))
		{
			exit("The application folder is not set correctly. Please correct this at: ".__FILE__);
		}
		define("APPPATH", BASEPATH.$app_folder."/");
	}

	/**
	 * When I wrote this, only God and I understood what I was doing.
	 * Now, God only knows.
	 *
	 * Boot up the kernel!
	 */

	require_once BASEPATH."core/Kernel.php";
?>
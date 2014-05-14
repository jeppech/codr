<?php

	$db_cfg['development']	['local']	['hostname'] 		= 'localhost';
	$db_cfg['development']	['local']	['username'] 		= '';
	$db_cfg['development']	['local']	['password'] 		= '';
	$db_cfg['development']	['local']	['database'] 		= '';
	$db_cfg['development']	['local']	['charset'] 		= '';

	$db_cfg['development']	['local']	['fetch_mode']		= PDO::FETCH_OBJ;
	$db_cfg['development']	['local']	['error_mode']		= PDO::ERRMODE_WARNING;


	$db_cfg['production']	['default']	['hostname'] 		= 'localhost';
	$db_cfg['production']	['default']	['username'] 		= '';
	$db_cfg['production']	['default']	['password'] 		= '';
	$db_cfg['production']	['default']	['database'] 		= '';
	$db_cfg['production']	['default']	['charset'] 		= '';

	$db_cfg['production']	['default']	['fetch_mode']		= PDO::FETCH_OBJ;
	$db_cfg['production']	['default']	['error_mode']		= PDO::ERRMODE_WARNING;

	$config = $db_cfg[ENVIRONMENT];

	$config['connection'] = 'local';
	
	/*
	 * Free some memory
	 */
	unset($db_cfg);
?>
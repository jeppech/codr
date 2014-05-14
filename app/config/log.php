<?php
	
	/**
	 * Enable/disable logging.
	 */
	$log['log_enabled'] = true;

	/**
	 * Log filename, and log save path.
	 *
	 * Notice, no extension is added to the
	 * log filename from here.
	 */
	$log['log_filename'] = 'codr';
	$log['log_filepath'] = APPPATH.'logs/'.$log['log_filename'];

	/**
	 * 
	 */

	$log['log_levels'] = array(
		"error" 		=> 1,
		"warning" 		=> 2,
		"debug" 		=> 3,
		"production" 	=> 4,
		"client"		=> 5);

	/**
	 * Which levels should be logged,
	 *
	 * Notice, enabling all logging levels
	 * can set back the avg. load time.
	 */
	$log['log_these_levels'] = array(1,2,4);
?>
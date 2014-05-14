<?php

	class CODR_Log
	{

		/**
		 * Enable/disable logging
		 * ------
		 * @var boolean
		 */
		private $_log_enabled = true;

		/**
		 * Path to log file
		 * ------
		 * @var string
		 */

		private $_log_filepath;

		/**
		 * Values equilant to the different logging levels.
		 * ------
		 * @var array
		 */
		private $_log_levels;

		/**
		 * Which levels should be logged.
		 * ------
		 * @var array
		 */
		private $_log_these;

		function __construct()
		{
			include APPPATH.'config/log.php';
			$this->_log_enabled 	= $log['log_enabled'];

			if ($this->_log_enabled)
			{
				$this->_log_filepath 	= $log['log_filepath'];
				$this->_log_levels 		= $log['log_levels'];
				$this->_log_these 		= $log['log_these_levels'];

				// Get the instance of the super object,
				// and return the Filesystem object.
				$this->fs = load_class('Filesystem','core');
			}
			$this->log_message('debug','Log class initialized');
		}

		public function log_message($level,$message)
		{
			if ($this->_log_enabled)
			{
				if (!isset($this->_log_levels[$level]))
				{
					show_error('Unsupported logging level','Logging level <b>'.$level.'</b>, is not supported.');
				}

				if (!in_array($this->_log_levels[$level], $this->_log_these))
				{
					return;
				}

				$date_fileformat = date("d-m-y");

				$_log_filepath = $this->_log_filepath.$date_fileformat.'.log';

				if (!file_exists($_log_filepath))
				{
					$this->fs->put_contents($_log_filepath,null);
				}

				$this->_write_log($level,$message,$_log_filepath);
			}
		}

		private function _write_log($level,$message,&$_log_filepath)
		{
			$_buffer = date("[H:i:s]");
			$_buffer .= '[level::'.$level.'] >>>';
			$_buffer .= ' '.$message."\r\n";

			$this->fs->write($_log_filepath,$_buffer);
		}
	}
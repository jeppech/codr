<?php

	class CODR_Config {

		/**
		 * Array containing all config values
		 * @var array
		 */

		public $config 			= [];

		private $_cfg_loaded 	= [];

		public function __construct()
		{
			log_message("debug","Config class initialized");
			$this->config =& load_config();

			if ($this->config["base_url"] == "")
			{
				$base_url = false;

				if (isset($_SERVER["HTTP_HOST"]))
				{
					$base_url = ((isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) !== "off") ? "https" : "http");
					$base_url .= "://".$_SERVER["HTTP_HOST"];
					$base_url .= str_replace(basename($_SERVER["SCRIPT_NAME"]), "", $_SERVER["SCRIPT_NAME"]);
				}
				else
				{
					$base_url = "http://localhost/";
				}
				$this->set_item("base_url",$base_url);
			}
		}

		public function &load($file = null)
		{
			if (empty($file))
			{
				show_error("Load config","No config file was assigned");
			}

			$file 	= str_replace(".php", "", $file);
			$loaded = false;

			$check_location = [APPPATH];

			foreach ($check_location as $location) {
				$file_path = $location."config/".$file.EXT;

				if (in_array($file_path, $this->_cfg_loaded))
				{
					return $this->config[$file];
				}
				if (!file_exists($file_path))
				{
					show_error("Load config","The requested config file, does not exist");
				}

				include $file_path;

				if (!isset($config) || !is_array($config))
				{
					show_error("Load config","The requested config file, is not valid.");
				}

				return $this->config[$file] =& $config;
			}
		}

		/**
		 * Assign a value to an item in the config array.
		 *
		 * @param string $item  Config item
		 * @param string $value Config value
		 */
		public function set_item($item,$value)
		{
			$this->config[$item] = $value;
		}

		/**
		 * Fetch a config item from the config array.
		 *
		 * @param  string $item  Config item
		 * @param  string $index Optional index, for multidimensional arrays
		 * @return string        Value corresponding to item
		 */
		public function get_item($item, $index = "")
		{
			$pref = false;

			if ($index == "")
			{
				if (!isset($this->config[$item]))
				{
					return false;
				}

				$pref = $this->config[$item];
			}
			else
			{
				if (!isset($this->config[$index]))
				{
					return false;
				}

				if (!isset($this->config[$index][$item]))
				{
					return false;
				}

				$pref = $this->config[$index][$item];
			}

			return $pref;
		}
	}

?>
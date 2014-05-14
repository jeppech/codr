<?php

	class CODR_Hooks
	{

		private $_hooks = array();
		private $_enabled = false;

		function __construct()
		{
			log_message("debug","Hooks class initialized");
			$this->_initialize_hooks();
		}

		private function _initialize_hooks()
		{
			$cfg 	=& load_class("Config","core");
			$hooks 	= false;

			if (!$cfg->get_item("enable_hooks"))
			{
				return;
			}

			if (is_file(APPPATH."config/hooks.php"))
			{
				include_once APPPATH."config/hooks.php";
			}

			if (!isset($hooks) || !is_array($hooks))
			{
				return;
			}

			$this->_hooks = $hooks;
			$this->_enabled = true;
		}

		public function _call_hook($which = "")
		{
			if (!$this->_enabled || !isset($this->_hooks[$which]))
			{
				return false;
			}

			if (isset($this->_hooks[$which][0]) && is_array($this->_hooks[$which][0]))
			{
				foreach ($this->_hooks[$which] as $val)
				{
					$this->_execute_hook($val);
				}
			}
			else
			{
				$this->_execute_hook($this->_hooks[$which]);
			}
		}

		private function _execute_hook($hookdata)
		{
			if (!is_array($hookdata))
			{
				return false;
			}

			if (!isset($hookdata["filename"]) || !isset($hookdata["filepath"]))
			{
				return false;
			}
			$filepath = APPPATH.$hookdata["filepath"]."/".$hookdata["filename"];

			if (!file_exists($filepath))
			{
				return false;
			}

			$class 		= false;
			$function 	= false;
			$params 	= "";

			if (isset($hookdata["function"]))
			{
				$function = $hookdata["function"];
			}

			if (isset($hookdata["class"]) && $hookdata["class"] != "")
			{
				$class = $hookdata["class"];
			}

			if (isset($hookdata["params"]) && is_array($hookdata["params"]))
			{
				$params = $hookdata["params"];
			}

			require_once $filepath;

			if ($class)
			{
				if (class_exists($class))
				{
					$HOOK = new $class();

					if (method_exists($HOOK, $function))
					{
						$HOOK->$function($params);
					}
				}
			} else if (function_exists($function))
			{
				$function($params);
			}
			return true;
		}
	}
?>
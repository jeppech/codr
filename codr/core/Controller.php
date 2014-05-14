<?php

	class CODR_Controller {

		private static $instance;

		public function __construct()
		{
			self::$instance =& $this;

			/**
			 * --------
			 * Assign all the previous loaded classes, to a
			 * local variable in this controller, resulting
			 * in one super object.
			 * --------
			 */
			foreach (is_loaded() as $classVar => $className)
			{
				$this->$classVar =& load_class($className);
			}

			$this->load =& load_class('Loader','core');
			$this->fs 	=& load_class('Filesystem','core');
			
		}
		/**
		 * Get instance
		 * 
		 * Get the instance of the super object
		 * from anywhere.
		 * 
		 * @access public
		 * @return object
		 */
		public static function &get_instance()
		{
			return self::$instance;
		}
	}

?>
<?php

	class CODR_Model
	{

		/**
		 * Magic method - Makes the controller
		 * methods available to the models.
		 * 
		 * @param  method
		 * @return mixed
		 */

		function __get($method)
		{
			$CODR =& get_instance();
			return $CODR->$method;
		}
	}
?>
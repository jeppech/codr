<?php

	class CODR_Profiler {

		/**
		 * Container profiler point
		 */
		private $_profile_point = array();

		function __construct()
		{
			log_message('debug','Profiler class initialized');
		}

		public function mark($point)
		{
			list($msecs,$secs) = explode(' ',microtime());
			$markpoint = ($secs+$msecs);

			$this->_set_mark($point,$markpoint);
		}

		public function elapsed_time($startpoint,$endpoint)
		{
			if (!isset($this->_profile_point[$startpoint]) || !isset($this->_profile_point[$endpoint]))
			{
				log_message('error','Profiler->elapsed_time(): Points defined in elapsed_time does not exist');
				return 0;
			}

			return ($this->_profile_point[$endpoint]-$this->_profile_point[$startpoint]);
		}

		private function _set_mark($point,$markpoint)
		{
			$this->_profile_point[$point] = $markpoint;
		}
	}
?>
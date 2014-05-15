<?php


	class CODR_View {

		/**
		 * Contains the path for view files,
		 * requested with the load method
		 * @var array
		 */
		private $_requested_views = [];

		/**
		 * Contains data for the every loaded
		 * view, through the load method
		 * @var array
		 */
		private $_requested_data = [];

		public function load($view, $data = null)
		{
			$file_path = APPPATH."views/".$view.EXT;

			if (!file_exists($file_path))
			{
				log_message("error","CODR_View->load: Could not load view file. Does not exist ".APPPATH.$view.".php");
				show_error("View","Could not load <b>".$view."</b>, template does not exist.");
				return;
			}

			$this->_set_view($view,$file_path,$data);
		}

		/**
		 * Renders cached output, that have been
		 * requested from the load method.
		 */

		public function _output()
		{
			global $profiler;
			global $header;

			$elapsed_time = number_format($profiler->elapsed_time("execution_start","execution_end"),6);
			$memory_used = (!function_exists("memory_get_usage") ? 0 : number_format((memory_get_usage()/1024/1024),3));
			$memory_peak = (!function_exists("memory_get_peak_usage") ? 0 : number_format((memory_get_peak_usage()/1024/1024),3));

			ob_start();
			foreach ($header->get() as $h)
			{
				header($h);
			}
			ob_end_flush();

			ob_start();
			foreach ($this->_requested_views as $view => $file_path)
			{
				extract(isset($this->_requested_data[$view]) ? $this->_requested_data[$view] : []);

				$buffer = str_replace("{elapsed_time}", $elapsed_time, file_get_contents($file_path));
				$buffer = str_replace("{memory_used}", $memory_used, $buffer);
				$buffer = str_replace("{memory_peak}", $memory_peak, $buffer);

				echo eval(" ?>".$buffer.PHP_EOL."<?php ");
			}
			unset($buffer);
			ob_end_flush();
		}

		private function _set_view($view, $file_path, $data = null)
		{
			$this->_requested_views[$view] = $file_path;
			$this->_requested_data[$view] = $data;
		}
	}


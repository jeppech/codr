<?php

	class CODR_Uri {

		/**
		 * Contains a filtered uri. Query string stripped.
		 * @var string
		 */
		public $uri_string;

		/**
		 * Contains an re-indexed array of
		 * the URI segments, offset starts at 1.
		 * @var array
		 */
		public $segments 		= array();

		/**
		 * Contains an array of $_GET variable.
		 * @var array
		 */
		public $query_string 	= array();

		function __construct()
		{
			// If this was requested by the command line, parse the arguments instead.
			if ((php_sapi_name() == "cli") || (defined("STDIN")))
			{
				$this->_set_uri_string($this->_fetch_cli_args());
				return;
			}

			// Use the global REQUEST_URI variable to fetch the URI.
			if ($uri = $this->_fetch_uri_string())
			{
				$this->_set_uri_string($uri);
			}
			log_message("debug","URI class initialized");
		}

		/**
		 * Fetches the URI, and filters unnecessary elements.
		 * @return string Filtered URI.
		 */
		private function _fetch_uri_string()
		{
			if (!isset($_SERVER["REQUEST_URI"]) || !isset($_SERVER["SCRIPT_NAME"]))
			{
				return "";
			}

			// Strip the URI string for query segments.
			$uri = str_replace("?".$_SERVER["QUERY_STRING"], "", $_SERVER["REQUEST_URI"]);

			// Ensure we dont have any leading/trailing slashes.

			return str_replace(array("/","../"), "/", trim($uri,"/"));
		}

		/**
		 * Fetchs CLI arguments and parses as URI.
		 * @return string CLI URI segments
		 */
		private function _fetch_cli_args()
		{
			$args = array_slice($_SERVER["argv"],1);
			return (isset($args) ? implode("/", $args) : "");
		}

		private function _set_uri_string($uri)
		{
			$uri = ($uri == "/" ? "" : $uri);

			$this->uri_string = $uri;

			$this->_set_uri_segments($uri);
		}

		private function _set_uri_segments($uri)
		{
			$parts = explode("/", $uri);
			foreach ($parts as $key => $val)
			{
				if (isset($val) && !empty($val))
				{
					$this->segments[$key+1] = $val;
				}
			}

			return;
		}
	}

?>
<?php

	class CODR_Header
	{

		public $headers 				= [];
		private $mimes 					= [];
		private $http_response_codes 	= [];
		private $mime_type;

		function __construct()
		{
			$this->mimes 				= include APPPATH."config/mimetypes.php";
			$this->http_response_codes 	= include APPPATH."config/http_response_codes.php";

			log_message("debug","Header class initialized");
		}

		public function applyNow()
		{
			foreach ($this->headers as $h)
			{
				@header($h);
			}
			$this->headers = [];
		}

		public function get()
		{
			return $this->headers;
		}

		public function set($header)
		{
			if (!isset($header))
			{
				show_error("Header","No header was set.");
			}

			$this->headers[] = $header;

			return $this;
		}

		public function location($url, $now = false)
		{
			if ($now)
			{
				header("location: $url");
				exit;
			}

			$this->set("location: $url");
		}

		public function set_response($code, $string = null)
		{
			if (!isset($code))
			{
				show_error("Header","No response code was set.");
			}

			if (isset($this->http_response_codes[$code]) && empty($string))
			{
				$this->response =& $this->http_response_codes[$code];
			} else {
				$this->response = $string;
			}
			$protocol = (isset($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.0");
			$this->headers[] = $protocol." ".$code." ".$this->response;
		}

		public function set_content_type($mime_type,$charset = false)
		{
			if (!isset($mime_type))
			{
				show_error("Header","Mimetype was not defined.");
			}

			if (isset($this->mimes[$mime_type]))
			{
				$this->mime_type =& $this->mimes[$mime_type];

				if (is_array($this->mimes[$mime_type]))
				{
					$this->mime_type =& current($this->mimes[$mime_type]);
				}
			}

			$header = "Content-Type: ".$this->mime_type.(!$charset ? NULL : " ;charset=utf-8");

			$this->headers[] = $header;
		}
	}

?>
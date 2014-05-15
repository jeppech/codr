<?php

	class Welcome extends CODR_Controller
	{
		private $_vdata;

		function __construct()
		{
			parent::__construct();

			$this->_vdata = [
				"_media"	=> APPPATH."layout/media/",
				"_fonts" 	=> APPPATH."layout/fonts/",
				"_css" 		=> APPPATH."layout/css/",
				"_js" 		=> APPPATH."layout/js/",
				"lib" 		=> null
			];
		}

		function index()
		{
			$this->view->load("welcome-to-codr");
		}
	}
?>
<?php


	class CODR_Loader {

		private $_model_paths;
		private $_library_paths;

		private $_loaded_models = array();
		private $_loaded_libraries = array();

		function __construct() {
			log_message('debug','Loader class initialized');

			$this->_model_paths = array(APPPATH);
			$this->_library_paths = array(APPPATH,BASEPATH);
		}

		public function model($model, $name = '', $db = false)
		{
			// Enable multi-loading of models
			if (is_array($model))
			{
				foreach ($model as $model_ident)
				{
					$this->model($model_ident);
				}
			}

			$model = strtolower($model);

			if ($name == '')
			{
				$name = $model;
			}

			if (in_array($name, $this->_loaded_models, true))
			{
				return;
			}

			$codr =& get_instance();

			if (isset($codr->$name))
			{
				exit('The requested model is already a resource: '.$name);
			}


			foreach ($this->_model_paths as $model_path)
			{
				if (!file_exists($model_path.'models/'.ENVIRONMENT.'/'.$model.EXT))
				{
					break;
				}

				if (!class_exists('CODR_Model'))
				{
					load_class('Model','core');
				}

				require_once $model_path.'models/'.ENVIRONMENT.'/'.$model.EXT;

				$model = ucfirst($model);

				$codr->$name = new $model();
				$this->_loaded_models[] = $name;

				return;
			}

			exit('The requested model does not exist: '.$model);
		}

		public function library($library, $name = '', $prefix = 'LIB_')
		{
			if (is_array($library))
			{
				foreach ($library as $lib)
				{
					$this->library($lib);
				}
			}

			$library = strtolower($library);

			if ($name == '')
			{
				$name = $library;
			}

			if (in_array($name, $this->_loaded_libraries))
			{
				return;
			}

			$codr =& get_instance();

			if (isset($codr->$name))
			{
				exit('The requested library is already a loaded resource: '.$library);
			}

			$library = ucfirst($library);

			foreach ($this->_library_paths as $lib_path)
			{
				if (!file_exists($lib_path.'library/'.$library.EXT))
				{
					continue;
				}

				$codr->$name =& load_class($library,'library',$prefix);
				$this->_loaded_libraries[] = $name;
			}
		}

		public function database()
		{
			$codr =& get_instance();

			if (class_exists('CODR_Database'))
			{
				return;
			}

			$codr->db =& load_class('DB','database');
		}

	}
?>
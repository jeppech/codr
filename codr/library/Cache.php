<?php

	class LIB_Cache
	{

		/**
		 * Contains information of active caches
		 * ------
		 * @var array
		 */
		private $cache;

		/**
		 * Is caching enabled?
		 * ------
		 * @var boolean
		 */
		private $_caching = true;

		/**
		 * Where are the cache files stored
		 * ------
		 * @var string
		 */
		private $_cache_path;

		/**
		 * Cache file extension
		 * ------
		 * @var string
		 */
		private $_cache_file_ext = '.cache';

		/**
		 * Filename for the system file
		 * that stores all the information of cached data.
		 * ------
		 * @var string
		 */
		private $_cache_system_file = 'cache_controller';

		/**
		 * Path for system file.
		 * ------
		 * @var string
		 */
		private $_cache_system_filepath;

		/**
		 * If a cache lifetime is not set,
		 * it will be assigned a lifetime of the
		 * default value in seconds
		 * ------
		 * @var integer
		 */
		private $_cache_default_lifetime = 300;

		/**
		 * If a cache not has been used for a while
		 * if will automatically be trashed, when
		 * the $_cache_auto_trash limit has been reached.
		 * ------
		 * @var integer
		 */
		private $_cache_auto_trash = 600;

		function __construct()
		{
			if ($this->_caching)
			{
				$this->fs =& get_instance()->fs;

				$this->_cache_path = APPPATH.'cache/';
				$this->_cache_system_filepath = $this->_cache_path.$this->_cache_system_file.$this->_cache_file_ext;

				$this->_fetch_cached_info();
			}
		}

		public function write($index, $cache, $lifetime = 0, $datatype = 'array')
		{
			switch ($datatype) {
				case 'array':
				case 'object':
				case 'json':
				case 'string':
				case 'text':
					continue;

				default:
					show_error('Unsupported datatype','Cache: Unsupported datatype, '.$datatype);
			}

			$lifetime = (!$lifetime ? $this->_cache_default_lifetime : $lifetime);

			$this->cache[$index]['cache_expire'] = (time()+$lifetime);
			$this->cache[$index]['cache_lifetime'] = $lifetime;
			$this->cache[$index]['cache_datatype'] = $datatype;

			$this->_write_cache($index, $cache);
		}

		/**
		 * Return the cached data if available,
		 * or if the cache needs to be refreshed it returns false
		 * ------
		 * @param string $index	Cache index
		 * @param string $data_type Set the format of the return value (array/object/json/text)
		 * 
		 * @return mixed			Cached data or false
		 */
		public function fetch($index, $datatype = 'array')
		{
			$_cache_filepath = $this->_cache_path.$index.$this->_cache_file_ext;

			if (!isset($this->cache[$index]) || !file_exists($_cache_filepath))
			{
				return false;
			}

			if ($cache = $this->_can_use_cache($index,$_cache_filepath))
			{
				return $cache;
			}

			return false;
		}

		private function _write_cache($index, $cache)
		{
			$_cache_filepath = $this->_cache_path.$index.$this->_cache_file_ext;

			$_buffer;

			switch ($this->cache[$index]['cache_datatype']) {
				case 'array':
				case 'object':
				case 'json':
					$_buffer = json_encode($cache);
					break;
				
				case 'text':
				case 'string':
					$_buffer = $cache;
					break;
				
				default:
					$_buffer = json_encode($cache);
					break;
			}

			$this->fs->put_contents($_cache_filepath, $_buffer);

			$this->_update_cached_info();

			log_message('debug',"Cache->_write_cache: Added cache '".$index."'");
		}

		private function _can_use_cache($index, &$_cache_filepath)
		{

			// If the timestamp, is greater than the cache expire time
			// proceed to check the actual file.
			if (time() >= $this->cache[$index]['cache_expire'])
			{
				$_cache_file_time = filemtime($_cache_filepath);

				// If the cache file last moderation time is less than
				// the cache expire time, we'll assume the cache is valid.
				if ($_cache_file_time < $this->cache[$index]['cache_expire'])
				{
					$_buffer = $this->_fetch_cached_index($index, $_cache_filepath);
					$_cache_lifetime = (!isset($this->cache[$index]['cache_lifetime']) ? $this->_cache_default_lifetime : $this->cache[$index]['cache_lifetime']);
					
					$this->write($index, $_buffer, $_cache_lifetime);
					log_message('debug',"Cache->_can_use_cache: Renewed cache expiration for '".$index."'");
					return $_buffer;
				}
				return false;
			}
			log_message('debug',"Cache->_can_use_cache: Updated cache '".$index."'");
			return $this->_fetch_cached_index($index, $_cache_filepath);
		}

		private function _fetch_cached_index($index, &$_cache_filepath)
		{
			$_cache_filepath = $this->_cache_path.$index.$this->_cache_file_ext;

			return json_decode(file_get_contents($_cache_filepath),true);
		}

		private function _fetch_cached_info()
		{
			$_cache_filepath =& $this->_cache_system_filepath;

			if (!file_exists($_cache_filepath))
			{
				$this->fs->put_contents($_cache_filepath, null);
			}

			$_buffer = json_decode($this->fs->get_contents($_cache_filepath),true);

			foreach ($_buffer as $k => $v)
			{
				if (($v['cache_expire']+$this->_cache_auto_trash) > time())
				{
					$this->cache[$k] = $v;
					continue;
				}
				$_expire_time = (time()-($v['cache_expire']+$this->_cache_auto_trash));
				log_message('debug',"Cache->_fetch_cached_info: Trashed cache '".$k."'. Expired with ".$_expire_time." seconds");
			}

			unset($_buffer);
		}

		private function _update_cached_info()
		{
			$_cache_filepath =& $this->_cache_system_filepath;

			$this->fs->put_contents($_cache_filepath, json_encode($this->cache));
		}
	}

?>
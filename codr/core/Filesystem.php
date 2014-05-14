<?php
	class CODR_Filesystem
	{
		public function put_contents($file, $contents)
		{
			if (!file_exists($file))
			{
				file_put_contents($file, $contents);
				log_message('debug','Filesystem->put_contents: '.basename($file).' not found, creating file in: '.$file);
				return;
			}

			@file_put_contents($file, $contents);
			return;
		}

		public function get_contents($file)
		{
			if (!file_exists($file))
			{
				log_message('error', 'Filesystem->get_contents: Could not get contents of '.$file.' - The file does not exist.');
				show_error('Could not get contents of file', 'Could not file: <b>'.basename($file).'</b> - The file does not exist.');
			}
			else
			{
				return @file_get_contents($file);
			}
		}

		public function write($file,$contents)
		{
			if (!is_writable($file) && file_exists($file))
			{
				log_message('error','Filesystem->write: File is not writable at '.$file);
				show_error('File is not writable','The file <b>'.basename($file).'</b> is not writeable');
			}

			$_handle = $this->_open_file($file,'a');
			
			fwrite($_handle, $contents);

			$this->_close_file($_handle);
		}

		private function _open_file($file,$mode)
		{
			if (!file_exists($file))
			{
				log_message('error','Filesystem->_open_file: Could not open file at '.$file.' - The file does not exist.');
				show_error('Could not open file', 'Could not open file: <b>'.basename($file).'</b> - The file does not exist.');
			}
			return fopen($file, $mode);
		}

		private function _close_file($handle)
		{
			fclose($handle);
		}
	}
?>
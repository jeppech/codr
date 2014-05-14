<?php

	class CODR_Error {

		function _constuct()
		{
			log_message('debug','Error class initialized');
		}

		function show_error($heading, $message, $template = 'error_default', $code = 500)
		{
			$message = '<p>'.implode('</p><p>', (is_array($message) ? $message : array($message))).'</p>';

			ob_start();
			include APPPATH.'errors/'.$template.EXT;
			$buffer = ob_get_contents();
			ob_end_clean();

			return $buffer;
		}

		function show_404($page)
		{
			$heading = '404 - Page not found';
			$message = 'The requested page was not found <b>'.strtolower($page).'</b>';

			ob_start();
			include APPPATH.'errors/error_404.php';
			$buffer = ob_get_contents();
			ob_end_clean();

			return $buffer;
		}
	}
?>
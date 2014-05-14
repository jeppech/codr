<?php

	// .po to .mo converter.
	require_once BASEPATH."library/php-mo.php";

	require_once BASEPATH."library/poparser.php";

	class CODR_Locale
	{
		public $cfg;
		public $locale;
		public $editable = false;

		private $po;
		private $domain;

		private static $_buffer_entries = array();
		private static $_DOMAIN_PATH;

		function __construct()
		{
			$this->cfg = include 'app/config/locale_cfg.php';
			$this->po = new PoParser();
			if (isset($_SESSION["t_locale"]))
			{
				$this->set_locale($_SESSION["t_locale"]);
			} else {
				$this->set_locale($this->cfg["locale_default"]);
			}

			$this->set_domain($this->cfg["locale_domain_default"]);

			self::$_DOMAIN_PATH = APPPATH."locale/%s/LC_MESSAGES/%s.po";

			if (isset($_SESSION["t_editable"]) && $_SESSION["t_editable"])
			{
				$this->editable = true;

				// Holding registered translations, so we dont have to read from the po file on every page request.
				if (!isset($_SESSION["t_buffer_registered"]))
				{
					$_SESSION["t_buffer_registered"] = array();
				}
			}
			log_message('debug','Locale class initialized');

		}

		/**
		 * Change locale.
		 * @param string $locale eg. en_IE, da_DK etc
		 * @return boolean
		 */
		public function set_locale($locale)
		{
			if (in_array($locale,$this->cfg["locales"]))
			{
				$this->locale = $locale;

				putenv("LANG=".$this->locale.".UTF-8");
				putenv("LANGUAGE=".$this->locale.".UTF-8");

				setlocale(LC_TIME, 		$this->locale.".utf8",
										$this->locale.".UTF8",
										$this->locale.".utf-8",
										$this->locale.".UTF-8",
										$this->locale);

				setlocale(LC_MESSAGES, 	$this->locale.".utf8",
										$this->locale.".UTF8",
										$this->locale.".utf-8",
										$this->locale.".UTF-8",
										$this->locale);

				return true;
			}
			return false;
		}

		/**
		 * Return locale from the short language ident. eg da = da_DK, en = en_IE
		 * If not match can be found, the default locale will be returned.
		 * @param  string $lang 	da, en, etc.
		 * @return string        	da_DK, en_IE etc.
		 */
		public function get($lang = "")
		{
			if (!empty($lang))
			{
				if (isset($this->cfg["languages"][$lang]))
				{
					return $this->cfg["languages"][$lang];
				} else {
					return $this->cfg["locale_default"];
				}
			}
			return $this->cfg["locale_default"];
		}

		/**
		 * Flip the $cfg["languages"] array to get the short value of the given locale
		 * eg da_DK will return da
		 * @param  string $locale 	Locale eg, en_IE, da_DK
		 * @return string         	Short language eg, da, en, de
		 */
		public function get_reverse($locale)
		{
			$language = array_flip($this->cfg["languages"]);
			if (isset($language[$locale]))
			{
				return $language[$locale];
			}
			return false;
		}

		public function set_domain($domain)
		{
			$this->domain = $domain;

			bindtextdomain($domain, APPPATH."locale");
			textdomain($domain);
		}

		public function editable($editable)
		{
			if ($editable)
			{
				$this->editable = true;
			}
		}

		public function get_settings()
		{
			return array(
				"locales"	=> $this->cfg["locales"],
				"locale"	=> $this->locale,
				"editable"	=> $this->editable,
				"domain"	=> $this->domain
			);
		}

		/**
		 * Translates the given string, and replaces any arguments
		 * passed with the appropriate values.
		 * @param  string $string String to translate
		 * @param  array 		$args   	Arguments to be replace with variables
		 * @param  bool 		$decode 	Should the string be decoded to HTML
		 * @param  bool 		$editable
		 * @return string 		Rendered string
		 */
		public function t($msgid, $args = array(), $decode = false, $editable = true)
		{
			if ($msgid != "" && !empty($msgid))
			{
				$msgid 		= $this->sanitized($msgid);
				$msgstr 	= str_replace("[BR]","<br />",gettext($msgid));
				$string 	= null;
				$vars 		= array();

				// Check if the msgid is registerd in the language files.
				// Notice. increases page load significantly
				if ($msgid == $msgstr && $this->editable)
				{
					$this->is_entry_registered($msgid);
				}

				// Render string, if any arguments is passed.
				if (!empty($args))
				{
					$search 	= array();
					$replace 	= array();

					foreach ($args as $k => $v)
					{
						array_push($search, $k);
						array_push($replace, $v);
					}

					$vars = $search;
					// Do the actual search and replace.
					$string = str_replace($search, $replace, $msgstr);
				}

				$string = (empty($string) ? $msgstr : $string);

				// If $decode == true, we'll decode the string, the same way it was encoded.
				$string = ($decode ? html_entity_decode($string, ENT_QUOTES, "UTF-8") : $string);

				if ($this->editable && $editable)
				{
					return $this->editable_string($msgid, $msgstr, $string, $vars, $decode);
				}
				return $string;
			}
			return null;
		}

		/**
		 * Wrap the string in span with certain attributes,
		 * that should be used by the JS Library.
		 * @param  string $string
		 * @return string
		 */
		private function editable_string($msgid, $msgstr, $string, $vars, $decode)
		{
			return "<span class='t_editable' t-msgstr-raw='$msgstr' t-msgid='$msgid' t-locale='{$this->locale}' t-html='$decode'>$string</span>";
		}

		/**
		 * Check if the requested msgid exists in the default locale,
		 * if not it will be added.
		 *
		 * All msgids should be registered in the default locale,
		 * otherwise we cannot check which languages needs to be translated.
		 * @param  string  $msgid msgid entry
		 * @return void
		 */
		private function is_entry_registered($msgid)
		{
			if (isset($_SESSION["t_buffer_registered"]))
			{
				if (!isset($_SESSION["t_buffer_registered"][md5($msgid)]))
				{
					if (!$this->entry_exists($msgid, $this->cfg["locale_default"]))
					{
						$this->add_entry($msgid, $msgid, $this->cfg["locale_default"], null, false);
					}

					if (!$this->entry_exists($msgid, $this->locale))
					{
						$this->add_entry($msgid, $msgid, $this->locale, null, false);
					}

					$_SESSION["t_buffer_registered"][md5($msgid)] = true;
				}
			}
		}

		/**
		 * Adds translation entry to the current active domain,
		 * if the entry already exists, it will be updated with the new changes.
		 * @param string  $msgid   Message id.
		 * @param string  $msgstr  Translated text
		 * @param string  $comment Optional, comment for translation
		 * @return boolean
		 */
		public function add_entry($msgid, $msgstr, $locale, $comment = null, $sanitize = true)
		{
			if (!in_array($locale, $this->cfg["locales"]))
			{
				return false;
			}

			if ($msgid == "" || empty($msgid))
			{
				return false;
			}

			$comment 	= (empty($comment) ? html_entity_decode($msgid, ENT_QUOTES, "UTF-8") : $comment);
			$msgid_san 	= ($sanitize ? $this->sanitized($msgid) : $msgid);
			$msgstr_san = ($sanitize ? $this->sanitized($msgstr) : $msgstr);

			$file 		= APPPATH."locale/$locale/LC_MESSAGES/".$this->domain.".po";

			if ($this->entry_exists($msgid_san, $locale))
			{
				// If the entry already exists, let PoParser update the entry.
				$this->po->update_entry($msgid_san, $msgstr_san);
				$this->po->write($file);
			} else {
				// If the entry does not exists, we simply add it to the end of the file.
				$fh 	= fopen($file, "a");

				$entry 		= 	PHP_EOL.
							"# $comment".PHP_EOL.
							"msgid \"$msgid_san\"".PHP_EOL.
							"msgstr \"$msgstr_san\"".PHP_EOL;

				fwrite($fh, $entry);
				fclose($fh);

				self::$_buffer_entries[$locale][$msgid_san] = array(
					"tcomment" 	=> $comment,
					"msgid" 	=> $msgid_san,
					"msgstr" 	=> $msgstr_san
				);
			}

			return true;
		}

		/**
		 * Sanitizes a string
		 * @param  string $string Plain string
		 * @return string         Sanitized string
		 */
		private function sanitized($string)
		{
			return htmlentities($string, ENT_QUOTES, "UTF-8");
		}

		/**
		 * Checks if a entry already exists in the .po file,
		 * if this is true.
		 * @param  string  $msgid  Sanitized message id.
		 * @param  string  $file   Path to .po file
		 * @return boolean
		 */
		private function entry_exists($msgid, $locale)
		{
			$this->load_entries($locale);

			if (isset(self::$_buffer_entries[$locale][$msgid]))
			{
				return true;
			}
			return false;
		}

		/**
		 * Load all entries to a givin locale.
		 * @param  string $locale eg. en-us, da-dk etc.
		 * @return void
		 */
		public function load_entries($locale = false)
		{
			$locale = (!$locale ? $this->locale : $locale);

			$file = APPPATH."locale/$locale/LC_MESSAGES/".$this->domain.".po";

			if (file_exists($file))
			{
				self::$_buffer_entries[$locale] = $this->po->read($file);
			}
			return self::$_buffer_entries[$locale];
		}

		/**
		 * Search all available domains for a given entry,
		 * and return the domains that is missing this entry.
		 * @param  string $msgid
		 * @return array 			Domains not having the msgid
		 */
		public function missing_entries($msgid)
		{
			$_missing 	= array();
			$msgid 		= $this->sanitized($msgid);

			foreach ($this->locales as $locale)
			{
				$this->load_entries($locale);

				if (!isset(self::$_buffer_entries[$locale][$msgid]))
				{
					$_missing[] = $locale;
				}
			}
			return (empty($_missing) ? false : $_missing);
		}

		/**
		 * Converts all .po files to .mo files.
		 * If $locale is set, domains in the given
		 * locale is only updated.
		 * @param  string $locale 	eg. en-us, da-dk etc
		 * @return boolean
		 */
		public function update_domains($locale = null)
		{
			$pattern = "/[0-9a-zA-Z-_]+\.po/";
			if (empty($locale))
			{

				foreach ($this->cfg["locales"] as $locale)
				{
					$dir 	= APPPATH."locale/$locale/LC_MESSAGES/";

					$files 	= scandir($dir);

					foreach ($files as $file)
					{
						if (preg_match($pattern, $file, $pofile))
						{
							// If the directory contains any .po files,
							// let php-mo convert them to .mo files.
							$pofile = array_pop($pofile);
							phpmo_convert($dir.$pofile);
						}
					}
				}
				return true;
			} else {

				return true;
			}
			return false;
		}
	}
?>
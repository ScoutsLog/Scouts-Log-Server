<?php

class php_language {

	private $supported;
	private $default;
	
	private $accepted;
	private $display;


	public function __construct($display='') {
		$this->supported = array();
		$this->accepted = array();

		$this->init();

		if (!empty($display)) {
			if (in_array($display, $this->supported) === true) {
				$this->display = $display;
			}
		}
	}


	private function init() {
		// Get site configuration settings
		$cfg = Globals::getInstance('config');

		$this->supported = $cfg['language_supported'];
		$this->default = $cfg['language_default'];

		// Get list of accepted languages for browser
		$this->accepted = array();

		$_accept = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
		$_accepted = array();

		foreach ($_accept as $_pref) {
			list($_lang, $_q) = explode(';', $_pref);

			$_lang = preg_replace("/[^a-zA-Z\-]+/", '', trim($_lang));
			$_q = preg_replace("/[^0-9\.]+/", '', trim($_q));
			$_q = ($_q === '') ? 1 : floatval($_q);

			list($_prefix, $_special) = explode('-', $_lang);

			$_accepted[] = array(
				'language' => $_lang,
				'prefix' => $_prefix,
				'special' => $_special,
				'priority' => $_q
			);
		}

		// Sort language list based on priority
		$sorter = new php_sorter( $_accepted, array(array('priority', 'DESC', 'NUMERIC')) );
		$this->accepted = $sorter->sort();

		$this->display = $this->default;;

		// Match up available languages to supported languages
		foreach ($this->accepted as $_lang) {
			if (in_array($_lang['prefix'], $this->supported) === true) {
				$available[] = $_lang;
			}
		}

		// Sort available languages based on priority
		$sorter = new php_sorter( $available, array(array('priority', 'DESC', 'NUMERIC')) );
		$available = $sorter->sort();

		// Select best supported language
		if (!empty($available)) {
			$this->display = $available[0]['prefix'];
		}
	}



	public function Accepted() {
		return $this->accepted;
	}

	public function DefaultLanguage() {
		return $this->default;
	}

	public function DisplayLanguage() {
		return $this->display;
	}

	public function Supported() {
		return $this->supported;
	}
}


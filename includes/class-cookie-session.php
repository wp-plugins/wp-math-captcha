<?php
if(!defined('ABSPATH'))	exit;

new Math_Captcha_Cookie_Session();

class Math_Captcha_Cookie_Session
{
	public $session_ids;


	public function __construct()
	{
		// sets instance
		Math_Captcha()->cookie_session = $this;

		// actions
		add_action('plugins_loaded', array(&$this, 'init_session'), 1);
	}


	/**
	 * Initializes cookie-session
	*/
	public function init_session()
	{
		if(is_admin())
			return;

		if(isset($_COOKIE['mc_session_ids']))
			$this->session_ids = $_COOKIE['mc_session_ids'];
		else
		{
			foreach(array('default', 'multi') as $place)
			{
				switch($place)
				{
					case 'multi':
						for($i = 0; $i < 5; $i++)
						{
							$this->session_ids[$place][$i] = sha1($this->generate_password(64, false, false));
						}
						break;

					case 'default':
						$this->session_ids[$place] = sha1($this->generate_password(64, false, false));
						break;
				}
			}
		}

		if(!isset($_COOKIE['mc_session_ids']))
		{
			setcookie('mc_session_ids[default]', $this->session_ids['default'], current_time('timestamp', true) + apply_filters('math_captcha_time', Math_Captcha()->options['general']['time']), COOKIEPATH, COOKIE_DOMAIN, (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? true : false), true);

			for($i = 0; $i < 5; $i++)
			{
				setcookie('mc_session_ids[multi]['.$i.']', $this->session_ids['multi'][$i], current_time('timestamp', true) + apply_filters('math_captcha_time', Math_Captcha()->options['general']['time']), COOKIEPATH, COOKIE_DOMAIN);
			}
		}
	}

	
	/**
	 * Generate password helper, without wp_rand() call
	*/
	private function generate_password($length = 12, $special_chars = true, $extra_special_chars = false)
	{
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		if ($special_chars)
			$chars .= '!@#$%^&*()';
		if ($extra_special_chars)
			$chars .= '-_ []{}<>~`+=,.;:/?|';
	
		$password = '';
		
		for ($i = 0; $i < $length; $i++)
		{
			$password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}

		return $password;
	}
}
?>
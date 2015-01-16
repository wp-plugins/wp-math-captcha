<?php
/*
Plugin Name: Math Captcha
Description: Math Captcha is a <strong>100% effective CAPTCHA for WordPress</strong> that integrates into login, registration, comments, Contact Form 7 and bbPress.
Version: 1.2.2
Author: dFactory
Author URI: http://www.dfactory.eu/
Plugin URI: http://www.dfactory.eu/plugins/math-captcha/
License: MIT License
License URI: http://opensource.org/licenses/MIT
Text Domain: math-captcha
Domain Path: /languages

Math Captcha
Copyright (C) 2013, Digital Factory - info@digitalfactory.pl

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/


if(!defined('ABSPATH'))	exit;

define('MATH_CAPTCHA_URL', plugins_url('', __FILE__));
define('MATH_CAPTCHA_PATH', plugin_dir_path(__FILE__));
define('MATH_CAPTCHA_REL_PATH', dirname(plugin_basename(__FILE__)).'/');

include_once(MATH_CAPTCHA_PATH.'includes/class-cookie-session.php');
include_once(MATH_CAPTCHA_PATH.'includes/class-update.php');
include_once(MATH_CAPTCHA_PATH.'includes/class-core.php');
include_once(MATH_CAPTCHA_PATH.'includes/class-settings.php');


class Math_Captcha
{
	private static $_instance;
	public $core;
	public $cookie_session;
	public $options;
	public $defaults = array(
		'general' => array(
			'enable_for' => array(
				'login_form' => false,
				'registration_form' => true,
				'reset_password_form' => true,
				'comment_form' => true,
				'bbpress' => false,
				'contact_form_7' => false
			),
			'block_direct_comments' => false,
			'hide_for_logged_users' => true,
			'title' => 'Math Captcha',
			'mathematical_operations' => array(
				'addition' => true,
				'subtraction' => true,
				'multiplication' => false,
				'division' => false
			),
			'groups' => array(
				'numbers' => true,
				'words' => false
			),
			'time' => 300,
			'deactivation_delete' => false,
			'flush_rules' => false
		),
		'version' => '1.2.2'
	);


	public static function instance()
	{
		if(self::$_instance === null)
			self::$_instance = new self();

		return self::$_instance;
	}


	private function __clone() {}
	private function __wakeup() {}


	/**
	 * 
	*/
	public function __construct()
	{
		register_activation_hook(__FILE__, array(&$this, 'activation'));
		register_deactivation_hook(__FILE__, array(&$this, 'deactivation'));

		// settings
		$this->options = array(
			'general' => array_merge($this->defaults['general'], get_option('math_captcha_options', $this->defaults['general']))
		);

		// actions
		add_action('plugins_loaded', array(&$this, 'load_textdomain'));
		add_action('admin_enqueue_scripts', array(&$this, 'admin_comments_scripts_styles'));
		add_action('wp_enqueue_scripts', array(&$this, 'frontend_comments_scripts_styles'));
		add_action('login_enqueue_scripts', array(&$this, 'frontend_comments_scripts_styles'));

		// filters
		add_filter('plugin_action_links', array(&$this, 'plugin_settings_link'), 10, 2);
		add_filter('plugin_row_meta', array(&$this, 'plugin_extend_links'), 10, 2);
	}


	/**
	 * Activation
	*/
	public function activation()
	{
		add_option('math_captcha_options', $this->defaults['general'], '', 'no');
		add_option('math_captcha_version', $this->defaults['version'], '', 'no');
	}


	/**
	 * Deactivation
	*/
	public function deactivation()
	{
		if($this->options['general']['deactivation_delete'])
			delete_option('math_captcha_options');
	}


	/**
	 * Loads textdomain
	*/
	public function load_textdomain()
	{
		load_plugin_textdomain('math-captcha', false, MATH_CAPTCHA_REL_PATH.'languages/');
	}


	/**
	 * Enqueues scripts and styles (admin)
	*/
	public function admin_comments_scripts_styles($page)
	{
		if($page === 'settings_page_math-captcha')
		{
			wp_register_style(
				'math-captcha-admin',
				MATH_CAPTCHA_URL.'/css/admin.css'
			);

			wp_enqueue_style('math-captcha-admin');

			wp_register_script(
				'math-captcha-admin-settings',
				MATH_CAPTCHA_URL.'/js/admin-settings.js',
				array('jquery')
			);

			wp_enqueue_script('math-captcha-admin-settings');

			wp_localize_script(
				'math-captcha-admin-settings',
				'mcArgsSettings',
				array(
					'resetToDefaults' => __('Are you sure you want to reset these settings to defaults?', 'math-captcha')
				)
			);
		}
	}


	/**
	 * Enqueues scripts and styles (frontend)
	*/
	public function frontend_comments_scripts_styles($page)
	{
		wp_register_style(
			'math-captcha-frontend',
			MATH_CAPTCHA_URL.'/css/frontend.css'
		);

		wp_enqueue_style('math-captcha-frontend');
	}


	/**
	 * Add links to Support Forum
	*/
	public function plugin_extend_links($links, $file) 
	{
		if(!current_user_can('install_plugins'))
			return $links;

		$plugin = plugin_basename(__FILE__);

		if($file == $plugin) 
		{
			return array_merge(
				$links,
				array(sprintf('<a href="http://www.dfactory.eu/support/forum/math-captcha/" target="_blank">%s</a>', __('Support', 'math-captcha')))
			);
		}

		return $links;
	}


	/**
	 * Add links to Settings page
	*/
	function plugin_settings_link($links, $file) 
	{
		if(!is_admin() || !current_user_can('manage_options'))
			return $links;

		static $plugin;

		$plugin = plugin_basename(__FILE__);

		if($file == $plugin) 
		{
			$settings_link = sprintf('<a href="%s">%s</a>', admin_url('options-general.php').'?page=math-captcha', __('Settings', 'math-captcha'));
			array_unshift($links, $settings_link);
		}

		return $links;
	}
}


function Math_Captcha()
{
	static $instance;

  	// first call to instance() initializes the plugin
  	if($instance === null || !($instance instanceof Math_Captcha))
    	$instance = Math_Captcha::instance();

  	return $instance;
}


Math_Captcha();
?>
<?php
/**
*
* @package Knowlege base
* @copyright (c) 2014 Sheer
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace Sheer\knowlegebase\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	protected $user;

/**
* Assign functions defined in this class to event listeners in the core
*
* @return array
* @static
* @access public
*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'						=> 'load_language_on_setup',
			'core.page_header'						=> 'add_page_header_link',
			'overall_header_stylesheets_after'		=> 'add_page_header_link',
			'core.viewonline_overwrite_location'	=> 'viewonline_location',
		);
	}

	/**
	* Constructor
	*/
	public function __construct(\phpbb\template\template $template, \phpbb\user $user, $phpbb_root_path)
	{
		$this->template = $template;
		$this->user = $user;
		$this->phpbb_root_path = $phpbb_root_path;
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'Sheer/knowlegebase',
			'lang_set' => 'knowlegebase_lng',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function add_page_header_link($event)
	{
		$this->template->assign_vars(array(
			'U_LIBRARY'		=> append_sid("{$this->phpbb_root_path}knowlegebase"),
			'KB_STYLESHEET'	=> append_sid("{$this->phpbb_root_path}ext/Sheer/knowlegebase/styles/" . rawurlencode($this->user->style['style_path']) . "/theme/kb.css"),
			'KB_THEME_PATH'	=> append_sid("{$this->phpbb_root_path}ext/Sheer/knowlegebase/styles/" . rawurlencode($this->user->style['style_path']) . "/theme"),
		));
	}

	public function viewonline_location($event)
	{
		$on_page = $event['row'];
		if ($on_page['session_page'] == 'app.php/knowledgebase')
		{
			$event['location'] = $this->user->lang['LIBRARY'];
			$event['location_url'] = append_sid("{$this->phpbb_root_path}knowledgebase");
		}
	}
}

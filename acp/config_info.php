<?php
/**
*
* @package phpBB Extension - Knowlege Base
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace sheer\knowlegebase\acp;

class config_info
{
	function module()
	{
		return array(
			'filename'	=> '\sheer\knowlegebase\acp\config_module',
			'version'	=> '1.0.0',
			'title' => 'ACP_KNOWLEGE_BASE_CONFIGURE',
			'modes'		=> array(
				'settings'	=> array(
					'title' => 'ACP_KNOWLEGE_BASE_CONFIGURE',
					'auth' => 'ext_sheer/knowlegebase && acl_a_board && acl_a_manage_kb',
					'cat' => array('ACP_KNOWLEGE_BASE')
				),
			),
		);
	}
}

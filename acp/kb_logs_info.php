<?php
/**
*
* @package phpBB Extension - Knowlege Base
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace Sheer\knowlegebase\acp;

class kb_logs_info
{
	function module()
	{
		return array(
			'filename'	=> '\Sheer\knowlegebase\acp\kb_logs_info',
			'version'	=> '1.0.0',
			'title' => 'ACP_LIBRARY_LOGS',
			'modes'		=> array(
				'settings'	=> array(
					'title' => 'ACP_LIBRARY_LOGS',
					'auth' => 'ext_Sheer/knowlegebase && acl_a_board && acl_a_manage_kb',
					'cat' => array('ACP_KNOWLEGE_BASE')
				),
			),
		);
	}
}

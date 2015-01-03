<?php
/**
*
* @package phpBB Extension - Knowlege Base
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace Sheer\knowlegebase\migrations;

class version_0_0_2 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['knowlege_base_version']) && version_compare($this->config['knowlege_base_version'], '0.0.2', '>=');
	}

	static public function depends_on()
	{
		return array('\Sheer\knowlegebase\migrations\version_0_0_1');
	}

	public function update_schema()
	{
		return array(
			'add_tables'		=> array(
				$this->table_prefix . 'kb_log'	=> array(
					'COLUMNS'		=> array(
						'log_id'			=> array('UINT', null, 'auto_increment'),
						'log_type'			=> array('TINT:4', 0),
						'user_id'			=> array('UINT', 0),
						'forum_id'			=> array('UINT', 0),
						'reportee_id'		=> array('UINT', 0),
						'topic_id'			=> array('UINT', 0),
						'log_ip'			=> array('VCHAR:40', ''),
						'log_time'			=> array('UINT:11', 0),
						'log_operation'		=> array('TEXT', ''),
						'log_data'			=> array('MTEXT_UNI', ''),
					),
					'PRIMARY_KEY'	=> 'log_id',
						'KEYS'			=> array(
							'log_type'		=> array('INDEX', 'log_type'),
							'forum_id'		=> array('INDEX', 'forum_id'),
							'topic_id'		=> array('INDEX', 'topic_id'),
							'reportee_id'	=> array('INDEX', 'reportee_id'),
							'user_id'		=> array('INDEX', 'user_id'),
						),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables'		=> array(
				$this->table_prefix . 'kb_log',
			),
		);
	}

	public function update_data()
	{
		return array(
			// Current version
			array('config.add', array('knowlege_base_version', '0.0.2')),
			// ACP

			array('module.add', array('acp', 'KNOWLEGE_BASE', array(
				'module_basename'	=> '\Sheer\knowlegebase\acp\kb_logs',
				'module_langname'	=> 'ACP_LIBRARY_LOGS',
				'module_mode'		=> 'view_logs',
				'module_auth'		=> 'ext_Sheer/knowlegebase && acl_a_board && acl_a_manage_kb',
			))),
		);
	}
}

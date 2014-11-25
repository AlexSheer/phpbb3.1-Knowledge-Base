<?php
/**
*
* @package phpBB Extension - My test
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace Sheer\knowlegebase\migrations;

class version_0_0_1 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return;
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\dev');
	}

	public function update_schema()
	{
		return array(
			'add_tables'		=> array(
				$this->table_prefix . 'kb_articles'	=> array(
					'COLUMNS'		=> array(
						'article_id'			=> array('UINT', NULL, 'auto_increment'),
						'article_category_id'	=> array('UINT', 0),
						'approved'				=> array('BOOL', 0),
						'article_title'			=> array('VCHAR:255', ''),
						'article_description'	=> array('VCHAR:255', ''),
						'article_date'			=> array('UINT:11', 0),
						'edit_date'				=> array('UINT:11', 0),
						'author_id'				=> array('UINT', 0),
						'author'				=> array('VCHAR:255', ''),
						'bbcode_uid'			=> array('VCHAR:10', ''),
						'bbcode_bitfield'		=> array('VCHAR:32', ''),
						'article_body'			=> array('MTEXT_UNI', ''),
						'topic_id'				=> array('UINT', 0),
						'views'					=> array('BINT', 0),
					),
					'PRIMARY_KEY'	=> 'article_id',
						'KEYS'			=> array(
							'topic_id'			=> array('INDEX', 'topic_id'),
							'author_id'			=> array('INDEX', 'author_id'),
							'author'			=> array('INDEX', 'author'),
						),
				),

				$this->table_prefix . 'kb_config'	=> array(
					'COLUMNS'		=> array(
						'config_name'	=> array('VCHAR:255', ''),
						'config_value'	=> array('VCHAR:255', ''),
						'is_dynamic'	=> array('BOOL', 0),
					),
					'PRIMARY_KEY'	=> 'config_name',
				),

				$this->table_prefix . 'kb_categories'	=> array(
					'COLUMNS'		=> array(
						'category_id'		=> array('UINT', NULL, 'auto_increment'),
						'parent_id'			=> array('UINT', 0),
						'left_id'			=> array('UINT', 0),
						'right_id'			=> array('UINT', 0),
						'category_parents'	=> array('MTEXT_UNI', ''),
						'category_name'		=> array('VCHAR:255', ''),
						'category_details'	=> array('VCHAR:255', ''),
						'category_type'		=> array('BOOL', 0),
						'number_articles'	=> array('USINT', 0),
					),
					'PRIMARY_KEY'	=> 'category_id',
						'KEYS'	=> array(
							'left_id'	=> array('INDEX', 'left_id'),
							'right_id'	=> array('INDEX', 'right_id'),
						),
				),

				$this->table_prefix . 'kb_options'	=> array(
					'COLUMNS'	=> array(
						'auth_option_id'=> array('UINT', NULL, 'auto_increment'),
						'auth_option'	=> array('VCHAR:50', ''),
						'is_global'		=> array('BOOL', 0),
						'is_local'		=> array('BOOL', 1),
					),
					'PRIMARY_KEY'	=> 'auth_option_id',
						'KEYS'	=> array(
							'auth_option' 	=> array('UNIQUE', 'auth_option'),
						),
				),

				$this->table_prefix . 'kb_src_wrdlist'	=> array(
					'COLUMNS'		=> array(
						'word_id'			=> array('UINT', NULL, 'auto_increment'),
						'word_text'			=> array('VCHAR_UNI', ''),
						'word_common'		=> array('BOOL', 0),
						'word_count'		=> array('UINT', 0),
					),
					'PRIMARY_KEY'	=> 'word_id',
						'KEYS'			=> array(
							'word_text'			=> array('UNIQUE', 'word_text'),
							'word_count'		=> array('INDEX', 'word_count'),
						),
				),

				$this->table_prefix . 'kb_src_wrdmtch'	=> array(
					'COLUMNS'		=> array(
						'article_id'		=> array('UINT', 0),
						'reply_id'			=> array('UINT', 0),
						'word_id'			=> array('UINT', 0),
						'title_match'		=> array('BOOL', 0),
					),
						'KEYS'			=> array(
							'un_mtch'			=> array('UNIQUE', array('article_id', 'word_id', 'title_match')),
							'word_id'			=> array('INDEX', 'word_id'),
							'article_id'		=> array('INDEX', 'article_id'),
						),
				),

				$this->table_prefix . 'kb_groups'	=> array(
					'COLUMNS'	=> array(
						'group_id'		=> array('UINT', 0),
						'category_id'	=> array('UINT', 0),
						'auth_option_id'=> array('UINT', 0),
						'auth_setting'	=> array('TINT:2'),
					),
						'KEYS'	=> array(
							'group_id'		=> array('INDEX', 'group_id'),
							'auth_option_id'=> array('INDEX', 'auth_option_id'),
						),
				),

				$this->table_prefix . 'kb_users'	=> array(
					'COLUMNS'	=> array(
						'user_id'		=> array('UINT', 0),
						'category_id'	=> array('UINT', 0),
						'auth_option_id'=> array('UINT', 0),
						'auth_setting'	=> array('TINT:2'),
					),
						'KEYS'	=> array(
							'user_id'		=> array('INDEX', 'user_id'),
							'auth_option_id'=> array('INDEX', 'auth_option_id'),
						),
				),

				$this->table_prefix . 'kb_search_results'	=> array(
					'COLUMNS'	=> array(
						'search_key'		=> array('VCHAR:32', 0),
						'search_time'		=> array('UINT:11', 0),
						'search_keywords'	=> array('MTEXT_UNI', ''),
						'search_authors'	=> array('MTEXT_UNI', ''),
					),
					'PRIMARY_KEY'	=> 'search_key',
				),

			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables'		=> array(
				$this->table_prefix . 'kb_articles',
				$this->table_prefix . 'kb_config',
				$this->table_prefix . 'kb_categories',
				$this->table_prefix . 'kb_options',
				$this->table_prefix . 'kb_src_wrdlist',
				$this->table_prefix . 'kb_src_wrdmtch',
				$this->table_prefix . 'kb_groups',
				$this->table_prefix . 'kb_users',
				$this->table_prefix . 'kb_search_results',
			),
		);
	}

	public function update_data()
	{
		return array(
			// Current version
			array('config.add', array('knowlege_base_version', '0.0.1')),

			// Search in Knowlege Base
			array('config.add', array('kb_search', '1')),
			array('config.add', array('kb_search_type', 'kb_fulltext_native')),
			array('config.add', array('kb_per_page_search', '10')),

			// Add permossions
			array('permission.add', array('a_manage_kb', true, 'a_board')),
			// Update kb_options table
			array('custom', array(array($this, 'update_kb_options_table'))),

			// ACP
			array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'KNOWLEGE_BASE')),
			array('module.add', array('acp', 'KNOWLEGE_BASE', array(
				'module_basename'	=> '\Sheer\knowlegebase\acp\config_module',
				'module_langname'	=> 'ACP_KNOWLEGE_BASE_CONFIGURE',
				'module_mode'		=> 'manage',
				'module_auth'		=> 'ext_Sheer/knowlegebase && acl_a_board && acl_a_manage_kb',
			))),
			array('module.add', array('acp', 'KNOWLEGE_BASE', array(
				'module_basename'	=> '\Sheer\knowlegebase\acp\manage_module',
				'module_langname'	=> 'ACP_LIBRARY_MANAGE',
				'module_mode'		=> 'manage',
				'module_auth'		=> 'ext_Sheer/knowlegebase && acl_a_board && acl_a_manage_kb',
			))),
			array('module.add', array('acp', 'KNOWLEGE_BASE', array(
				'module_basename'	=> '\Sheer\knowlegebase\acp\articles_module',
				'module_langname'	=> 'ACP_LIBRARY_ARTICLES',
				'module_mode'		=> 'articles',
				'module_auth'		=> 'ext_Sheer/knowlegebase && acl_a_board && acl_a_manage_kb',
			))),
			array('module.add', array('acp', 'KNOWLEGE_BASE', array(
				'module_basename'	=> '\Sheer\knowlegebase\acp\permissions_module',
				'module_langname'	=> 'ACP_LIBRARY_PERMISSIONS',
				'module_mode'		=> 'permissions',
				'module_auth'		=> 'ext_Sheer/knowlegebase && acl_a_board && acl_a_manage_kb',
			))),
			array('module.add', array('acp', 'KNOWLEGE_BASE', array(
				'module_basename'	=> '\Sheer\knowlegebase\acp\search_module',
				'module_langname'	=> 'ACP_LIBRARY_SEARCH',
				'module_mode'		=> 'index',
				'module_auth'		=> 'ext_Sheer/knowlegebase && acl_a_board && acl_a_manage_kb',
			))),
		);
	}

	public function update_kb_options_table()
	{
		if (!defined('KB_OPTIONS_TABLE'))
		{
			define('KB_OPTIONS_TABLE', $this->table_prefix . 'kb_options');
		}

		$options = array(
			1 => 'kb_u_add',
			2 => 'kb_u_edit',
			3 => 'kb_u_delete',
			4 => 'kb_u_add_noapprove',
			5 => 'kb_m_edit',
			6 => 'kb_m_delete',
			7 => 'kb_m_approve'
		);

		foreach ($options as $key => $value)
		{
			$sql_ary[] = array(
				'auth_option_id'	=> $key,
				'auth_option'		=> $value,
				'is_global'			=> 0,
				'is_local'			=> 1,
			);
		}
		$this->db->sql_multi_insert(KB_OPTIONS_TABLE, $sql_ary);
	}
}

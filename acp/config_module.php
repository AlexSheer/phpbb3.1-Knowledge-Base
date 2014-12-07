<?php
/**
*
* @package phpBB Extension - Knowlege Base
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace Sheer\knowlegebase\acp;

class config_module
{
	var $u_action;

	function main($id, $mode)
	{
		global $db, $template, $request, $table_prefix, $user, $phpbb_log;
		if (!defined('KB_CONFIG_TABLE')) define ('KB_CONFIG_TABLE', $table_prefix.'kb_config');
		if (!defined('KB_LOG_TABLE')) define ('KB_LOG_TABLE', $table_prefix.'kb_log');

		$default_config = array();

		$this->tpl_name = 'acp_knowlegebase_body';
		$this->page_title = $user->lang('ACP_KNOWLEGE_BASE_CONFIGURE');
		$phpbb_log->set_log_table(KB_LOG_TABLE);

		$sql = 'SELECT *
			FROM ' . KB_CONFIG_TABLE;
		$result = $db->sql_query($sql);
		while($row = $db->sql_fetchrow($result))
		{
			$config_name = $row['config_name'];
			$config_value = $row['config_value'];
			$default_config[$config_name] = isset($_POST['submit']) ? str_replace("'", "\'", $config_value) : $config_value;
			$new[$config_name] = $request->variable($config_name, $default_config[$config_name]);
		}
		$db->sql_freeresult($result);
		$new['anounce'] = $request->variable('anounce', 0);
		if (empty($new['articles_per_page']))
		{
			// To do
			// Make is_dinamic 0 and purge the cache after change settings
			$sql = 'INSERT INTO ' .KB_CONFIG_TABLE.' (config_name, config_value, is_dynamic)
				VALUES (\'forum_id\', 0, 1)';
			$db->sql_query($sql);
			$sql = 'INSERT INTO ' .KB_CONFIG_TABLE.' (config_name, config_value, is_dynamic)
				VALUES (\'articles_per_page\', 10, 1)';
			$db->sql_query($sql);
			$sql = 'INSERT INTO ' .KB_CONFIG_TABLE.' (config_name, config_value, is_dynamic)
				VALUES (\'anounce\', 1, 1)';
			$db->sql_query($sql);
		}
		add_form_key('Sheer/knowlegebase');
		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('Sheer/knowlegebase'))
			{
				trigger_error('FORM_INVALID');
			}

			$new[$config_name] = str_replace(",",".", $new[$config_name]);
			foreach($new as $key => $value)
			{
				// To do sql_ecape
				$sql = 'UPDATE ' . KB_CONFIG_TABLE . '
					SET config_value = ' . str_replace("\'", "''", $value) . '
					WHERE config_name = \''.$key.'\'';
				$db->sql_query($sql);
			}
			add_log('admin', 'LOG_LIBRARY_CONFIG');
			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'S_CONFIGURE'		=> true,
			'ADVANCED_FORM_ON'	=> (isset($default_config['anounce']) && $default_config['anounce']) ? 'checked="checked"' : '',
			'ADVANCED_FORM'		=> (isset($default_config['anounce']) && $default_config['anounce']) ? '' : 'none',
			'PER_PAGE'			=> (isset($new['articles_per_page'])) ? $new['articles_per_page'] : 10,
			'S_FORUM_POST'		=> (isset($new['articles_per_page'])) ? make_forum_select($new['forum_id'], 0, true, true, false) : make_forum_select(0, false, true, true, false),
			'S_ACTION'			=> $this->u_action,
		));
	}
}

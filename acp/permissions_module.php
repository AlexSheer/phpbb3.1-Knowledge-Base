<?php
/**
*
* @package phpBB Extension - Knowlege Base
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace Sheer\knowlegebase\acp;

class permissions_module
{
	var $u_action;

	function main($id, $mode)
	{
		global $config, $db, $template, $request, $cache, $phpbb_root_path, $table_prefix, $phpEx, $auth, $user, $phpbb_ext_kb;
		$user->add_lang('acp/permissions');

		define ('KB_CAT_TABLE', $table_prefix.'kb_categories');
		define ('KB_OPTIONS_TABLE', $table_prefix.'kb_options');
		define ('KB_USERS_TABLE', $table_prefix.'kb_users');
		define ('KB_GROUPS_TABLE', $table_prefix.'kb_groups');

		include_once($phpbb_root_path . 'includes/functions_user.' . $phpEx);

		$phpbb_ext_kb = new \Sheer\knowlegebase\inc\functions_kb($config, $db, $cache, $user, $template, $auth, $phpbb_root_path, $phpEx, $table_prefix);

		$this->tpl_name = 'acp_permissions_body';
		$this->page_title = $user->lang('ACP_LIBRARY_PERMISSIONS');

		$category_id 	= (isset($category_id)) ? $request->variable('category_id', $category_id) : $request->variable('category_id', array(0));
		$user_id 		= $request->variable('user_id', array(0));
		$group_id 		= $request->variable('group_id', array(0));
		$username 		= $request->variable('username', array(''), true);
		$usernames 		= $request->variable('usernames', '', true);
		$all_cats 		= $request->variable('all_cats', 0);
		$mode 			= $request->variable('p_mode', '');
		$submit			= $request->variable('submit', false);
		$action			= (isset($action)) ? $request->variable('action', $action) : $request->variable('action', '');

		if ($all_cats)
		{
			$sql = 'SELECT category_id
				FROM '.KB_CAT_TABLE.'';
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$category_id[] = $row['category_id'];
			}
			$db->sql_freeresult($result);
		}

		// Map usernames to ids and vice versa
		if ($usernames)
		{
			$username = explode("\n", $usernames);
		}
		unset($usernames);

		if (sizeof($username) && !sizeof($user_id))
		{
			user_get_id_name($user_id, $username);

			if (!sizeof($user_id))
			{
				trigger_error($user->lang['SELECTED_USER_NOT_EXIST'] . adm_back_link($this->u_action), E_USER_WARNING);
			}
		}
		unset($username);

		switch ($action)
		{
			case 'settings':
				$settings = $this->get_mask($group_id, $category_id, $user_id, $mode);
				$delete_permissions	= $request->variable('delete', false);
				if ($delete_permissions)
				{
					$this->delete_permissions($group_id, $user_id, $category_id);
				}

			break;

			case 'setting_group_local':
				$items = $this->permissions_v_mask($category_id, $user_id);
				$submit_edit_options = $request->variable('submit_edit_options', false);
				if ($submit_edit_options)
				{
					$action = 'settings';
				}

			break;

			case 'apply_permissions':
				$this->apply_permissions();
			break;

			default:
				$cats_box = $phpbb_ext_kb->make_category_select(0, false, false, false, false);
				$template->assign_vars(array(
					'L_TITLE'					=> $user->lang['ACP_LIBRARY_PERMISSIONS'],
					'L_EXPLAIN'					=> $user->lang['ACP_LIBRARY_PERMISSIONS_EXPLAIN'],
					'S_SELECT_CATEGORY'			=> true, //($cats_box) ? true : false,
					'CATS_BOX'					=> $cats_box,
					'S_KB_PERMISSIONS_ACTION' 	=> $this->u_action . '&amp;action=setting_group_local',
					)
				);
			break;
		}
	}

	function permissions_v_mask($category_id, $user_id)
	{
		global $db, $user, $template, $phpbb_root_path, $phpEx;

		$items = $this->retrieve_defined_user_groups('local', 0, 'b_');

		if (empty($category_id))
		{
			$template->assign_vars(array(
				'L_TITLE'			=> $user->lang['ACP_LIBRARY_PERMISSIONS'],
				'L_EXPLAIN'			=> $user->lang['ACP_LIBRARY_PERMISSIONS_EXPLAIN'],
				'S_SELECT_CATEGORY'	=> true)
			);
			return array();
		}

		$s_defined_group_options = $items['group_ids_options'];
		$s_defined_user_options = $items['user_ids_options'];
		$this->page_title = 'ACP_LIBRARY_PERMISSIONS';

		$s_hidden_fields = array(
			'category_id'		=> $category_id,
			'user_id'			=> $user_id,
		);

		$template->assign_vars(array(
			'L_TITLE'					=> $user->lang['ACP_LIBRARY_PERMISSIONS'],
			'L_EXPLAIN'					=> $user->lang['ACP_LIBRARY_PERMISSIONS_EXPLAIN'],
			'S_SELECT'					=> true,
			'S_CAN_SELECT_USER'			=> true,
			'S_CAN_SELECT_GROUP'		=> true,
			'S_ADD_GROUP_OPTIONS'		=> group_select_options(false, $items['group_ids'], false),	// Show all groups
			'U_FIND_USERNAME'			=> append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=searchuser&amp;form=add_user&amp;field=username&amp;select_single=true'),
			'S_DEFINED_GROUP_OPTIONS'	=> $s_defined_group_options,
			'S_DEFINED_USER_OPTIONS'	=> $s_defined_user_options,
			'S_KB_PERMISSIONS_ACTION' 	=> $this->u_action . '&amp;action=settings',
			'S_HIDDEN_FIELDS'			=> build_hidden_fields($s_hidden_fields),
			)
		);
		return $items;
	}

	/**
	* Get already assigned users/groups
	*/
	function retrieve_defined_user_groups($permission_scope, $forum_id, $permission_type)
	{
		global $db, $user;
		$sql_where = '';

		$sql_forum_id = ($permission_scope == 'global') ? 'AND a.category_id = 0' : ((sizeof($forum_id)) ? 'AND ' . $db->sql_in_set('a.category_id', $forum_id) : 'AND a.category_id <> 0');

		// Permission options are only able to be a permission set... therefore we will pre-fetch the possible options and also the possible roles
		$option_ids = array();

		$sql = 'SELECT auth_option_id
			FROM ' . KB_OPTIONS_TABLE . '
			WHERE auth_option ' . $db->sql_like_expression($permission_type . $db->get_any_char());
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$option_ids[] = (int) $row['auth_option_id'];
		}
		$db->sql_freeresult($result);

		if (sizeof($option_ids))
		{
			$sql_where = 'AND ' . $db->sql_in_set('a.auth_option_id', $option_ids);
		}

		// Not ideal, due to the filesort, non-use of indexes, etc.
		$sql = 'SELECT DISTINCT u.user_id, u.username, u.username_clean, u.user_regdate
			FROM ' . USERS_TABLE . ' u, ' . KB_USERS_TABLE . " a
			WHERE u.user_id = a.user_id
				$sql_where
			ORDER BY u.username_clean, u.user_regdate ASC";
		$result = $db->sql_query($sql);

		$s_defined_user_options = '';
		$defined_user_ids = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$s_defined_user_options .= '<option value="' . $row['user_id'] . '">' . $row['username'] . '</option>';
			$defined_user_ids[] = $row['user_id'];
		}
		$db->sql_freeresult($result);

		$sql = 'SELECT DISTINCT g.group_type, g.group_name, g.group_id
			FROM ' . GROUPS_TABLE . ' g, ' . KB_GROUPS_TABLE . " a
			WHERE g.group_id = a.group_id
				$sql_where
			ORDER BY g.group_type DESC, g.group_name ASC";
		$result = $db->sql_query($sql);

		$s_defined_group_options = '';
		$defined_group_ids = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$s_defined_group_options .= '<option' . (($row['group_type'] == GROUP_SPECIAL) ? ' class="sep"' : '') . ' value="' . $row['group_id'] . '">' . (($row['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $row['group_name']] : $row['group_name']) . '</option>';
			$defined_group_ids[] = $row['group_id'];
		}
		$db->sql_freeresult($result);

		return array(
			'group_ids'			=> $defined_group_ids,
			'group_ids_options'	=> $s_defined_group_options,
			'user_ids'			=> $defined_user_ids,
			'user_ids_options'	=> $s_defined_user_options
		);
	}


	function get_mask($group_id, $category_id, $user_id, $mode)
	{
		global $db, $template, $user, $request;

		if (empty($group_id) && empty($user_id))
		{
			$this->permissions_v_mask($category_id, $user_id);
			return;
		}

		(isset($type)) ? $type = request_var('type', $type) : $type = request_var('type', 'u_');
		$types = array('u_' => $user->lang['ACL_TYPE_U_'], 'm_' => $user->lang['ACL_TYPE_M_']);
		$s_type = '';
		foreach($types as $key => $value)
		{
			$selected = ($key == $type) ? 'selected="selected"' : '';
			$s_type .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
		}

		$apply_all_permissions = $request->variable('apply_all_permissions', false);

		if (!empty($user_id) && $mode != 'group')
		{
			$where = $db->sql_in_set('user_id', $user_id, false);
			if ($where == 'user_id = 0')
			{
				$where = 'user_id = 1';
			}
			$sql = 'SELECT user_id, username
				FROM '.USERS_TABLE.'
				WHERE '.$where.'';
			$result = $db->sql_query($sql);
			while ($users = $db->sql_fetchrow($result))
			{
				$user_name = $users['username'];
				$group_ids[] = $groups[$user_name] = $users['user_id'];
			}
			if (!$mode) $mode = 'user';
		}
		else
		{
			$sql = 'SELECT group_id, group_name
				FROM '.GROUPS_TABLE.'
				WHERE '.$db->sql_in_set('group_id', $group_id, false).'';
			$result = $db->sql_query($sql);
			while ($group = $db->sql_fetchrow($result))
			{
				$group_name = $user->lang['G_'.$group['group_name']];
				$group_ids[] = $groups[$group_name] = $group['group_id'];
			}
			if (!$mode) $mode = 'group';
		}
		$db->sql_freeresult($result);

		$sql = 'SELECT *
			FROM '.KB_OPTIONS_TABLE.'
			WHERE auth_option_id <> 0
				AND auth_option LIKE \'%'.$type.'%\'';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$auth_option =  $row['auth_option'];
			$options[$auth_option] = $row['auth_option_id'];
		}
		$db->sql_freeresult($result);

		$sql = 'SELECT *
			FROM '.KB_CAT_TABLE.'
			WHERE '.$db->sql_in_set('category_id', $category_id, false).'';
		$result = $db->sql_query($sql);

		$table = ($mode == 'user') ? KB_USERS_TABLE : KB_GROUPS_TABLE;
		$id_field = $mode . '_id';

		while ($row = $db->sql_fetchrow($result)) // categories
		{
			$cat_id = $row['category_id'];
			$template->assign_block_vars('p_mask', array(
				'CATEGORY_ID'	=> $cat_id,
				'CATEGORY_NAME'	=> $row['category_name'],
				)
			);

			foreach ($groups as $key => $group_id) // groups
			{
				$template->assign_block_vars('p_mask.g_mask', array(
					'GROUP_ID'		=> $group_id,
					'GROUP_NAME'	=> $key,
					)
				);

				$submit = $request->variable('submit', array(array(0)));
				$inherit = $request->variable('inherit', array(array(0)));

				foreach ($options as $name => $option)
				{
					$sql1 = 'SELECT auth_setting
						FROM '.$table.'
						WHERE '.$id_field.' = '.$group_id.'
							AND auth_option_id = '.$option.'
							AND category_id = '.$cat_id.'';
					$result1 = $db->sql_query($sql1);
					$auth = $db->sql_fetchrow($result1);
					$auth_setting[$option] = $auth['auth_setting'];

					$option_settings = $request->variable('setting', array(0 => array(0 => array('' => 0))));

					if ($auth)
					{
						switch ($auth_setting[$option])
						{
							case 1:
								$_yes = true;
								$_no = false;
								$_never = false;
							break;
							case 0:
								$_yes = false;
								$_no = false;
								$_never = true;
							break;
							default:
						}
					}
					else
					{
						$_yes = false;
						$_no = true;
						$_never = false;
					}

					if (!isset($auth['auth_setting']))
					{
						$auth['auth_setting'] = -1; // permission not set
					}

					$_options[$name] = $auth['auth_setting'];

					$template->assign_block_vars('p_mask.g_mask.o_mask', array(
						'S_FIELD_NAME'	=> $name,
						'L_FIELD_NAME'	=> $user->lang[$name],
						'S_YES'			=> ($_yes) ? true : false,
						'S_NO'			=> ($_no) ? true : false,
						'S_NEVER'		=> ($_never) ? true : false,
						)
					);
				}
				$groups_ary[$group_id] =  $_options;
				$hold_ary[$cat_id] = $groups_ary;
			}
		}
		$db->sql_freeresult($result);

		if ($submit)
		{
			foreach ($submit as $key => $value)
			{
				foreach ($submit[$key] as $second => $val)
				{
					$select[$key][$second] = $option_settings[$key][$second];
				}
			}
			$this->apply_all_permissions($select, $mode);
		}

		if($apply_all_permissions && !empty($inherit))
		{
			foreach ($inherit as $key => $value)
			{
				foreach ($inherit[$key] as $second => $val)
				{
					$select[$key][$second] = $option_settings[$key][$second];
				}
			}
			$this->apply_all_permissions($select, $mode);
		}

		$s_hidden_fields = array(
			'category_id'	=> $category_id,
			'group_id'		=> $group_ids,
			'user_id'		=> $group_ids,
			'p_mode'		=> $mode,
		);

		$template->assign_vars(array(
			'L_TITLE'					=> $user->lang['ACL_SET'],
			'L_EXPLAIN'					=> $user->lang['ACL_SET_EXPLAIN'],
			'S_VIEWING_PERMISSIONS'		=> true,
			'S_TYPE'					=> $s_type,
			'S_HIDDEN_FIELDS'			=> build_hidden_fields($s_hidden_fields),
			)
		);
		return;
	}

	function apply_all_permissions($hold_ary, $mode)
	{
		global $db, $user;

		$sql = 'SELECT auth_option, auth_option_id
			FROM '.KB_OPTIONS_TABLE.'';
		$result = $db->sql_query($sql);
		while($row = $db->sql_fetchrow($result))
		{
			$auth_option = $row['auth_option'];
			$auth_option_ids[$auth_option] = $row['auth_option_id'];
		}

		$table = ($mode == 'user') ? KB_USERS_TABLE : KB_GROUPS_TABLE;
		$id_field = $mode . '_id';

		foreach($hold_ary as $cat => $value)
		{
			foreach($value as $group => $settings)
			{
				foreach($settings as $opt_name => $option)
				{
					if ($option == -1)
					{
						$sql = 'DELETE FROM '.$table.'
							WHERE '.$id_field.' = '.$group.'
							AND category_id = '.$cat.'
							AND auth_option_id = '.$auth_option_ids[$opt_name].'';
						$db->sql_query($sql);
					}
					else
					{
						$sql = 'SELECT * FROM '.$table.'
							WHERE '.$id_field.' = '.$group.'
							AND category_id = '.$cat.'
							AND auth_option_id = '.$auth_option_ids[$opt_name].'';

						$result = $db->sql_query($sql);
						$row = $db->sql_fetchrow($result);
						if ($row)
						{
							$sql = 'UPDATE '.$table.'
								SET auth_setting = '.$option.'
								WHERE '.$id_field.' = '.$group.'
									AND category_id = '.$cat.'
									AND auth_option_id = '.$auth_option_ids[$opt_name].'';
							$db->sql_query($sql);
						}
						else
						{
							$sql = 'INSERT INTO '.$table.' ('.$id_field.', category_id, auth_option_id, auth_setting)
								VALUES ('.$group.', '.$cat.', '.$auth_option_ids[$opt_name].', '.$option.')';
							$db->sql_query($sql);
						}
					}
				}
			}
		}
		trigger_error($user->lang['AUTH_UPDATED'] . adm_back_link($this->u_action));
		return;
	}

	function delete_permissions($group_id, $user_id, $category_id)
	{
		global $db, $user;

		if (empty($group_id) && empty($user_id))
		{
			return;
		}
		(empty($group_id)) ? $mode = 'user' : $mode = 'group';
		$table = ($mode == 'user') ? KB_USERS_TABLE : KB_GROUPS_TABLE;
		$id_field = $mode . '_id';
		$where = ($mode == 'user') ? $user_id : $group_id;

		$sql = 'DELETE FROM '.$table.'
			WHERE '.$id_field.' IN ('.implode(',', $where).')
				AND category_id IN ('.implode(',', $category_id).')';
		$db->sql_query($sql);
		trigger_error($user->lang['AUTH_UPDATED'] . adm_back_link($this->u_action));
	}
}

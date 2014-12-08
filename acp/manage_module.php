<?php
/**
*
* @package phpBB Extension - Knowlege Base
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace Sheer\knowlegebase\acp;

class manage_module
{
	var $u_action;

	function main($id, $mode)
	{
		global $config, $db, $template, $request, $cache, $phpbb_root_path, $table_prefix, $phpEx, $auth, $user, $phpbb_ext_kb, $phpbb_admin_path, $phpbb_log;

		define ('ARTICLES_TABLE', $table_prefix.'kb_articles');
		define ('KB_CAT_TABLE', $table_prefix.'kb_categories');
		define ('KB_USERS_TABLE', $table_prefix.'kb_users');
		define ('KB_GROUPS_TABLE', $table_prefix.'kb_groups');
		if (!defined('KB_LOG_TABLE')) define ('KB_LOG_TABLE', $table_prefix.'kb_log');

		$phpbb_ext_kb = new \Sheer\knowlegebase\inc\functions_kb($config, $db, $cache, $user, $template, $auth, $phpbb_log, $phpbb_root_path, $phpEx, $table_prefix);

		$this->tpl_name = 'acp_knowlegebase_body';
		$this->page_title = $user->lang('ACP_LIBRARY_MANAGE');
		$category_data = $errors = array();

		$action				= $request->variable('action', '');
		$update				= (isset($_POST['update'])) ? true : false;
		$category_id		= $request->variable('f', '');
		$this->parent_id	= $request->variable('parent_id', 0);

		$phpbb_log->set_log_table(KB_LOG_TABLE);

		if ($update)
		{
			switch ($action)
			{
				case 'delete':
					$action_sub_cats	= $request->variable('action_sub_cats', '');
					$sub_cats_to_id		= $request->variable('sub_cats_to_id', 0);
					$action_posts		= $request->variable('action_posts', '');
					$posts_to_id		= $request->variable('posts_to_id', 0);
					$errors = $this->delete_category($category_id, $action_posts, $action_sub_cats, $posts_to_id, $sub_cats_to_id);
					if (sizeof($errors))
					{
						break;
					}
					$auth->acl_clear_prefetch();
					$cache->destroy('sql', KB_CAT_TABLE);
					meta_refresh(3, $this->u_action . '&amp;parent_id=' . $this->parent_id);
					trigger_error($user->lang['CATEGORY_DELETED'] . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id));
				break;
				case 'edit':
					$category_data = array(
						'category_id'		=>	$category_id
					);
				// No break here
				case 'add':
					$category_data += array(
						'parent_id'				=> $request->variable('parent_id', $this->parent_id),
						'type_action'			=> $request->variable('type_action', ''),
						'category_parents'		=> '',
						'category_name'			=> utf8_normalize_nfc($request->variable('category_name', '', true)),
						'category_details'		=> utf8_normalize_nfc($request->variable('category_details', '', true)),
					);
					$errors = $this->update_category_data($category_data);
					if (!sizeof($errors))
					{
						$cache->destroy('sql', KB_CAT_TABLE);
						$message = ($action == 'add') ? sprintf($user->lang['CATEGORY_ADDED'], '<a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-Sheer-knowlegebase-acp-permissions_module&mode=permissions&action=setting_group_local&category_id[]='. $category_data['category_id'] .'') . '">', '</a>') : $user->lang['CATEGORY_EDITED'];
						meta_refresh(3, $this->u_action . '&amp;parent_id=' . $this->parent_id);
						trigger_error($message . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id));
					}
				break;
			}
		}

		switch ($action)
		{
			case 'move_up':
			case 'move_down':
				if (!$category_id)
				{
					trigger_error($user->lang['CAT_NO_EXISTS'] . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
				}
				$sql = 'SELECT *
					FROM ' . KB_CAT_TABLE . "
					WHERE category_id = $category_id";
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
				if (!$row)
				{
					trigger_error($user->lang['CAT_NO_EXISTS'] . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
				}
				$move_category_name = $this->move_category_by($row, $action, 1);
				if ($move_category_name !== false)
				{
					$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'],'LOG_CATS_' . strtoupper($action), time(), array($row['category_name'], $move_category_name));
					$cache->destroy('sql', KB_CAT_TABLE);
				}
			break;
			case 'add':
				// No break here
			case 'edit':
				// Show form to create/modify a category
				if ($action == 'edit')
				{
					$this->page_title = 'LIBRARY_EDIT_CAT';
					$row = $phpbb_ext_kb->get_cat_info($category_id);

					if (!$update)
					{
						$category_data = $row;
					}
					else
					{
						$category_data['left_id'] = $row['left_id'];
						$category_data['right_id'] = $row['right_id'];
					}

					// Make sure no direct child cats are able to be selected as parents.
					$exclude_cats = array();
					foreach ($phpbb_ext_kb->get_category_branch($category_id, 'children') as $row)
					{
						$exclude_cats[] = $row['category_id'];
					}

					$parents_list = $phpbb_ext_kb->make_category_select($category_data['parent_id'], $exclude_cats, false, false, false);
				}
				else
				{
					$this->page_title = 'ADD_CATEGORY';
					$category_id = $this->parent_id;
					$parents_list = $phpbb_ext_kb->make_category_select($this->parent_id, false, false, false, false);

					// Fill category data with default values
					if (!$update)
					{
						$category_data = array(
							'parent_id'				=> $this->parent_id,
							'category_name'			=> utf8_normalize_nfc($request->variable('category_name', '', true)),
							'category_details'		=> '',
						);
					}
				}

				$category_desc_data = array(
					'text'			=> $category_data['category_details'],
				);

				$sql = 'SELECT category_id
					FROM ' . KB_CAT_TABLE . '
					WHERE  category_id <> '.$category_id.'';
				$result = $db->sql_query_limit($sql, 1);
				$postable_category_exists = false;
				if ($db->sql_fetchrow($result))
				{
					$postable_category_exists = true;
				}
				$db->sql_freeresult($result);

				// Subcat move options
				if ($postable_category_exists)
				{
					$template->assign_vars(array(
						'S_MOVE_CATEGORY_OPTIONS'		=> $phpbb_ext_kb->make_category_select($category_data['parent_id'], $category_id, false, true, false))
					);
				}

				$template->assign_vars(array(
					'S_EDIT'				=> true,
					'S_ERROR'				=> (sizeof($errors)) ? true : false,
					'S_PARENT_ID'			=> $this->parent_id,
					'S_CATEGORY_PARENT_ID'	=> $category_data['parent_id'],
					'S_ADD_ACTION'			=> ($action == 'add') ? true : false,
					'U_BACK'				=> $this->u_action . '&amp;parent_id=' . $this->parent_id,
					'U_EDIT_ACTION'			=> $this->u_action . "&amp;parent_id={$this->parent_id}&amp;action=$action&amp;f=$category_id",
					'L_TITLE'				=> $user->lang[$this->page_title],
					'ERROR_MSG'				=> (sizeof($errors)) ? implode('<br />', $errors) : '',
					'CATEGORY_NAME'			=> $category_data['category_name'],
					'CATEGORY_DESCR'		=> $category_desc_data['text'],
					'S_PARENT_OPTIONS'		=> $parents_list,
				));

			break;
			case 'delete':
				if (!$category_id)
				{
					trigger_error($user->lang['CAT_NO_EXISTS'] . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
				}
				$category_data = $phpbb_ext_kb->get_cat_info($category_id);
				$sub_cats_id = array();
				$sub_cats = $phpbb_ext_kb->get_category_branch($category_id, 'children');
				foreach ($sub_cats as $row)
				{
					$sub_cats_id[] = $row['category_id'];
				}
				$cats_list = $phpbb_ext_kb->make_category_select($category_data['parent_id'], $sub_cats_id);
				$sql = 'SELECT category_id
					FROM ' . KB_CAT_TABLE . '
					WHERE  category_id <> '.$category_id.'';
				$result = $db->sql_query_limit($sql, 1);
				if ($db->sql_fetchrow($result))
				{
					$template->assign_vars(array(
						'S_MOVE_CATEGORY_OPTIONS'	=> $phpbb_ext_kb->make_category_select($category_data['parent_id'], $sub_cats_id, false, true))
					);
				}
				$db->sql_freeresult($result);
				$parent_id = ($this->parent_id == $category_id) ? 0 : $this->parent_id;
				$template->assign_vars(array(
					'S_DELETE_CATEGORY'	=> true,
					'U_ACTION'			=> $this->u_action . "&amp;parent_id={$parent_id}&amp;action=delete&amp;f=$category_id",
					'U_BACK'			=> $this->u_action . '&amp;parent_id=' . $this->parent_id,
					'CATEGORY_NAME'		=> $category_data['category_name'],
					'S_HAS_SUBCATS'		=> ($category_data['right_id'] - $category_data['left_id'] > 1) ? true : false,
					'S_CATS_LIST'		=> $cats_list,
					'S_ERROR'			=> (sizeof($errors)) ? true : false,
					'ERROR_MSG'			=> (sizeof($errors)) ? implode('<br />', $errors) : ''
				));
			break;
			case 'sync':
				$errors = $this->sync($category_id);
				$category_data = $phpbb_ext_kb->get_cat_info($category_id);
				if (!sizeof($errors))
				{
					$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_' . strtoupper($action), time(), array($category_data['category_name']));
					$cache->destroy('sql', KB_CAT_TABLE);
					meta_refresh(3, $this->u_action . '&amp;parent_id=' . $this->parent_id);
					trigger_error('SYNC_OK');
				}
			break;
		}

		// Default management page
		if (!$this->parent_id)
		{
			$navigation = $user->lang['CATEGOTY_LIST'];
			$kb_config = $phpbb_ext_kb->obtain_kb_config();
			if (empty($kb_config) || (!$kb_config['forum_id'] && $kb_config['anounce']))
			$errors[] = $user->lang['WARNING_DEFAULT_CONFIG'];
		}
		else
		{
			$navigation = '<a href="' . $this->u_action . '">' . $user->lang['CATEGOTY_LIST'] . '</a>';
			$cats_nav = $phpbb_ext_kb->get_category_branch($this->parent_id, 'parents', 'descending');
			foreach ($cats_nav as $row)
			{
				if ($row['category_id'] == $this->parent_id)
				{
					$navigation .= ' -&gt; ' . $row['category_name'];
				}
				else
				{
					$navigation .= ' -&gt; <a href="' . $this->u_action . '&amp;parent_id=' . $row['category_id'] . '">' . $row['category_name'] . '</a>';
				}
			}
		}

		// Jumpbox
		$cats_box = $phpbb_ext_kb->make_category_select($this->parent_id, false, false, false, false);
		$sql = 'SELECT *
			FROM ' . KB_CAT_TABLE . "
			WHERE parent_id = $this->parent_id
			ORDER BY left_id";
		$result = $db->sql_query($sql);
		if ($row = $db->sql_fetchrow($result))
		{
			do
			{
				$url = $this->u_action . "&amp;parent_id=$this->parent_id&amp;f={$row['category_id']}";

				$template->assign_block_vars('categories', array(
					'ID'				=> $row['category_id'],
					'CATEGORY_NAME'		=> $row['category_name'],
					'CATEGORY_DESCR'	=> $row['category_details'],
					'ARTICLES'			=> $row['number_articles'],
					'U_CATEGORY'		=> $this->u_action . '&amp;parent_id=' . $row['category_id'],
					'U_MOVE_UP'			=> $url . '&amp;action=move_up',
					'U_MOVE_DOWN'		=> $url . '&amp;action=move_down',
					'U_EDIT'			=> $url . '&amp;action=edit',
					'U_DELETE'			=> $url . '&amp;action=delete',
					'U_SYNC'			=> $url . '&amp;action=sync',
					)
				);
			}
			while ($row = $db->sql_fetchrow($result));
		}
		else if ($this->parent_id)
		{
			$row = $phpbb_ext_kb->get_cat_info($this->parent_id);
			if (empty($row))
			{
				$errors[] = $user->lang['CAT_NO_EXISTS'];
			}

			$url = $this->u_action . '&amp;parent_id=' . $this->parent_id . '&amp;f=' . $row['category_id'];

			$template->assign_vars(array(
				'S_NO_CATS'		=> true,
				'U_EDIT'		=> $url . '&amp;action=edit',
				'U_DELETE'		=> $url . '&amp;action=delete',
				)
			);
		}

		$db->sql_freeresult($result);

		$template->assign_vars(array(
			'ERROR_MSG'		=> (sizeof($errors)) ? implode('<br />', $errors) : '',
			'NAVIGATION'	=> $navigation,
			'CATS_BOX'		=> (isset($cats_box)) ? $cats_box : '',
			'S_MANAGE'		=> true,
			'S_ACTION'		=> $this->u_action . "&amp;parent_id={$this->parent_id}&amp;action=$action&amp;f=$category_id",
			'U_ACTION'		=> $this->u_action . '&amp;parent_id=' . $this->parent_id,
		));
	}

	/**
	* Update category data
	*/
	function update_category_data(&$category_data)
	{
		global $db, $template, $request, $cache, $phpbb_root_path, $table_prefix, $phpEx, $auth, $user, $phpbb_ext_kb, $phpbb_log;
		$errors = array();

		if ($category_data['category_name'] == '')
		{
			$errors[] = $user->lang['NO_CAT_NAME'];
		}

		if ($category_data['category_details'] == '')
		{
			$errors[] = $user->lang['NO_CAT_DESCR'];
		}

		$category_data_sql = $category_data;

		if (sizeof($errors))
		{
			return $errors;
		}

		if (!isset($category_data_sql['category_id']))
		{
			// no category_id means we're creating a new category
			unset($category_data_sql['type_action']);

			if ($category_data_sql['parent_id'])
			{
				$sql = 'SELECT left_id, right_id
					FROM ' . KB_CAT_TABLE . '
					WHERE category_id = ' . $category_data_sql['parent_id'];
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
				if (!$row)
				{
					trigger_error($user->lang['PARENT_NOT_EXIST'] . adm_back_link($this->u_action . '&amp;parent_id=' . $this->parent_id), E_USER_WARNING);
				}
				$db->sql_freeresult($result);

				$sql = 'UPDATE ' . KB_CAT_TABLE . '
					SET left_id = left_id + 2, right_id = right_id + 2
					WHERE left_id > ' . $row['right_id'];
				$db->sql_query($sql);

				$sql = 'UPDATE ' . KB_CAT_TABLE . '
					SET right_id = right_id + 2
					WHERE ' . $row['left_id'] . ' BETWEEN left_id AND right_id';
				$db->sql_query($sql);

				$category_data_sql['left_id'] = $row['right_id'];
				$category_data_sql['right_id'] = $row['right_id'] + 1;
			}
			else
			{
				$sql = 'SELECT MAX(right_id) AS right_id
					FROM ' . KB_CAT_TABLE;
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);

				$category_data_sql['left_id'] = $row['right_id'] + 1;
				$category_data_sql['right_id'] = $row['right_id'] + 2;
			}

			$sql = 'INSERT INTO ' . KB_CAT_TABLE . ' ' . $db->sql_build_array('INSERT', $category_data_sql);
			$db->sql_query($sql);
			$category_data['category_id'] = $db->sql_nextid();
			$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_ADD', time(), array($category_data['category_name']));
		}
		else
		{
			$row = $phpbb_ext_kb->get_cat_info($category_data_sql['category_id']);

			if (sizeof($errors))
			{
				return $errors;
			}

			if ($row['parent_id'] != $category_data_sql['parent_id'])
			{
				if ($row['category_id'] != $category_data_sql['parent_id'])
				{
					$errors = $this->move_category($category_data_sql['category_id'], $category_data_sql['parent_id']);
				}
				else
				{
					$category_data_sql['parent_id'] = $row['parent_id'];
				}

				($category_data_sql['parent_id']) ? $dest = $this->get_category_info($category_data_sql['parent_id']) : $dest['category_name'] = $user->lang['KB_ROOT'];
				$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_CAT_MOVED_TO', time(), array($category_data_sql['category_name'], $dest['category_name']));
			}

			if (sizeof($errors))
			{
				return $errors;
			}

			unset($category_data_sql['type_action']);

			if ($row['category_name'] != $category_data_sql['category_name'])
			{
				// the category name has changed, clear the parents list of all cats (for safety)
				$sql = 'UPDATE ' . KB_CAT_TABLE . "
					SET category_parents = ''";
				$db->sql_query($sql);
			}

			// Setting the category id to the category id is not really received well by some dbs. ;)
			$category_id = $category_data_sql['category_id'];
			unset($category_data_sql['category_id']);

			$sql = 'UPDATE ' . KB_CAT_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', $category_data_sql) . '
				WHERE category_id = ' . $category_id;
			$db->sql_query($sql);

			// Add it back
			$category_data['category_id'] = $category_id;
			$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_EDIT', time(), array($category_data['category_name']));
		}
		return $errors;
	}

	/**
	* Move category content from one to another category
	*/
	function move_category_content($from_id, $to_id, $sync = true)
	{
		global $db, $user;
		$errors = array();
		// count the number of articles in the sender
		$sql = 'SELECT number_articles
			FROM ' . KB_CAT_TABLE . '
			WHERE category_id = '.$from_id.'';
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		if (empty($row))
		{
			$errors[] = $user->lang['CAT_NO_EXISTS'];
			return $errors;
		}
		$db->sql_freeresult($result);
		$from_id_articles = $row['number_articles'];
		// and recipient
		$sql = 'SELECT number_articles FROM ' . KB_CAT_TABLE . '
			WHERE category_id = '.$to_id.'';
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		if (empty($row))
		{
			$errors[] = $user->lang['CAT_NO_EXISTS'];
			return $errors;
		}
		$db->sql_freeresult($result);
		$to_id_articles = $row['number_articles'];
		// change the id of articles
		$sql = 'UPDATE '. ARTICLES_TABLE .'
			SET article_category_id = '.$to_id.'
			WHERE article_category_id = '.$from_id.'';
		$db->sql_query($sql);
		// change the number of articles in the receiver
		$to_id_articles = $to_id_articles + $from_id_articles;
		$sql = 'UPDATE ' . KB_CAT_TABLE . '
			SET number_articles = '.$to_id_articles.'
			WHERE category_id = '.$to_id.'';
		$db->sql_query($sql);

		return array();
	}

	// Complete removal category
	function delete_category($category_id, $action_posts = 'delete', $action_sub_cats = 'delete', $posts_to_id = 0, $sub_cats_to_id = 0)
	{
		global $db, $user, $cache, $phpbb_ext_kb, $phpbb_log;

		$category_data = $phpbb_ext_kb->get_cat_info($category_id);
		$errors = array();
		$log_action_posts = $log_action_cats = $posts_to_name = $sub_cats_to_name = '';
		$category_ids = array($category_id);

		if ($action_posts == 'delete')
		{
			$log_action_posts = 'POSTS';
			$errors = array_merge($errors, $this->delete_category_content($category_id));
		}
		else if ($action_posts == 'move')
		{
			if (!$posts_to_id)
			{
				$errors[] = $user->lang['NO_DESTINATION_CATEGORY'];
			}
			else
			{
				$log_action_posts = 'MOVE_POSTS';

				$sql = 'SELECT category_name
					FROM ' . KB_CAT_TABLE . '
					WHERE category_id = ' . $posts_to_id;
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);

				if (!$row)
				{
					$errors[] = $user->lang['CAT_NO_EXISTS'];
				}
				else
				{
					$posts_to_name = $row['category_name'];
					$errors = array_merge($errors, $this->move_category_content($category_id, $posts_to_id));
				}
			}
		}

		if (sizeof($errors))
		{
			return $errors;
		}

		if ($action_sub_cats == 'delete')
		{
			$log_action_cats = 'CATS';
			$rows = $phpbb_ext_kb->get_category_branch($category_id, 'children', 'descending', false);

			foreach ($rows as $row)
			{
				$category_ids[] = $row['category_id'];
				$errors = array_merge($errors, $this->delete_category_content($row['category_id']));
			}

			if (sizeof($errors))
			{
				return $errors;
			}

			$diff = sizeof($category_ids) * 2;

			$sql = 'DELETE FROM ' . KB_CAT_TABLE . '
				WHERE ' . $db->sql_in_set('category_id', $category_ids);
			$db->sql_query($sql);
		}
		else if ($action_sub_cats == 'move')
		{
			if (!$sub_cats_to_id)
			{
				$errors[] = $user->lang['NO_DESTINATION_CATEGORY'];
			}
			else
			{
				$log_action_cats = 'MOVE_CATS';

				$sql = 'SELECT category_name
					FROM ' . KB_CAT_TABLE . '
					WHERE category_id = ' . $sub_cats_to_id;
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);

				if (!$row)
				{
					$errors[] = $user->lang['CAT_NO_EXISTS'];
				}
				else
				{
					$sub_cats_to_name = $row['category_name'];

					$sql = 'SELECT category_id
						FROM ' . KB_CAT_TABLE . "
						WHERE parent_id = $category_id";
					$result = $db->sql_query($sql);

					while ($row = $db->sql_fetchrow($result))
					{
						$this->move_category($row['category_id'], $sub_cats_to_id);
					}
					$db->sql_freeresult($result);

					$category_data = $phpbb_ext_kb->get_cat_info($category_id);

					$sql = 'UPDATE ' . KB_CAT_TABLE . "
						SET parent_id = $sub_cats_to_id
						WHERE parent_id = $category_id";
					$db->sql_query($sql);

					$diff = 2;
					$sql = 'DELETE FROM ' . KB_CAT_TABLE . "
						WHERE category_id = $category_id";
					$db->sql_query($sql);
				}
			}

			if (sizeof($errors))
			{
				return $errors;
			}
		}
		else
		{
			$diff = 2;
			$sql = 'DELETE FROM ' . KB_CAT_TABLE . "
				WHERE category_id = $category_id";
			$db->sql_query($sql);
		}

		// Resync tree
		$sql = 'UPDATE ' . KB_CAT_TABLE . "
			SET right_id = right_id - $diff
			WHERE left_id < {$category_data['right_id']} AND right_id > {$category_data['right_id']}";
		$db->sql_query($sql);

		$sql = 'UPDATE ' . KB_CAT_TABLE . "
			SET left_id = left_id - $diff, right_id = right_id - $diff
			WHERE left_id > {$category_data['right_id']}";
		$db->sql_query($sql);

		$log_action = implode('_', array($log_action_posts, $log_action_cats));

		switch ($log_action)
		{
			case 'POSTS_MOVE_CATS':
				$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_DEL_POSTS_MOVE_CATS', time(), array($sub_cats_to_name, $category_data['category_name']));
			break;

			case '_MOVE_CATS':
				$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_DEL_MOVE_CATS', time(), array($sub_cats_to_name, $category_data['category_name']));
			break;

			case 'MOVE_POSTS_':
				$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_DEL_MOVE_POSTS', time(), array($posts_to_name, $category_data['category_name']));
			break;

			case 'POSTS_CATS':
				$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_DEL_POSTS_CATS', time(), array($category_data['category_name']));
			break;

			case '_CATS':
				$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_DEL_CAT', time(), array($category_data['category_name']));
			break;

			case 'POSTS_':
				$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_DEL_ARTICLES', time(), array($category_data['category_name']));
			break;

			case 'MOVE_POSTS_MOVE_CATS':
				$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_DEL_MOVE_POSTS_MOVE_CATS', time(), array($posts_to_name, $sub_cats_to_name, $category_data['category_name']));
			break;

			case 'MOVE_POSTS_CATS':
				$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_DEL_MOVE_POSTS_CATS', time(), array($posts_to_name, $category_data['category_name']));
			break;

			default:
				$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_DEL_CAT', time(), array($category_data['category_name']));
			break;
		}

		// delete permissions
		$sql = 'DELETE
			FROM ' . KB_USERS_TABLE . '
			WHERE category_id = '. $category_id;
		$db->sql_query($sql);

		$sql = 'DELETE
			FROM ' . KB_GROUPS_TABLE . '
			WHERE category_id = '. $category_id;
		$db->sql_query($sql);

		return $errors;
	}

	// Move category
	function move_category($from_id, $to_id)
	{
		global $db, $user, $phpbb_ext_kb;

		$to_data = $moved_ids = $errors = array();

		$moved_cats = $phpbb_ext_kb->get_category_branch($from_id, 'children', 'descending');
		$from_data = $moved_cats[0];
		$diff = sizeof($moved_cats) * 2;

		$moved_ids = array();
		for ($i = 0; $i < sizeof($moved_cats); ++$i)
		{
			$moved_ids[] = $moved_cats[$i]['category_id'];
		}

		// Resync parents
		$sql = 'UPDATE ' . KB_CAT_TABLE . "
			SET right_id = right_id - $diff, category_parents = ''
			WHERE left_id < " . $from_data['right_id'] . "
				AND right_id > " . $from_data['right_id'];
		$db->sql_query($sql);

		// Resync righthand side of tree
		$sql = 'UPDATE ' . KB_CAT_TABLE . "
			SET left_id = left_id - $diff, right_id = right_id - $diff, category_parents = ''
			WHERE left_id > " . $from_data['right_id'];
		$db->sql_query($sql);

		if ($to_id > 0)
		{
			// Retrieve $to_data again, it may have been changed...
			$to_data = $phpbb_ext_kb->get_cat_info($to_id);

			// Resync new parents
			$sql = 'UPDATE ' . KB_CAT_TABLE . "
				SET right_id = right_id + $diff, category_parents = ''
				WHERE " . $to_data['right_id'] . ' BETWEEN left_id AND right_id
					AND ' . $db->sql_in_set('category_id', $moved_ids, true);
			$db->sql_query($sql);

			// Resync the righthand side of the tree
			$sql = 'UPDATE ' . KB_CAT_TABLE . "
				SET left_id = left_id + $diff, right_id = right_id + $diff, category_parents = ''
				WHERE left_id > " . $to_data['right_id'] . '
					AND ' . $db->sql_in_set('category_id', $moved_ids, true);
			$db->sql_query($sql);

			// Resync moved branch
			$to_data['right_id'] += $diff;

			if ($to_data['right_id'] > $from_data['right_id'])
			{
				$diff = '+ ' . ($to_data['right_id'] - $from_data['right_id'] - 1);
			}
			else
			{
				$diff = '- ' . abs($to_data['right_id'] - $from_data['right_id'] - 1);
			}
		}
		else
		{
			$sql = 'SELECT MAX(right_id) AS right_id
				FROM ' . KB_CAT_TABLE . '
				WHERE ' . $db->sql_in_set('category_id', $moved_ids, true);
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			$diff = '+ ' . ($row['right_id'] - $from_data['left_id'] + 1);
		}

		$sql = 'UPDATE ' . KB_CAT_TABLE . "
			SET left_id = left_id $diff, right_id = right_id $diff, category_parents = ''
			WHERE " . $db->sql_in_set('category_id', $moved_ids);
		$db->sql_query($sql);

		return $errors;
	}

	/**
	* Delete category content
	*/
	function delete_category_content($cat_id)
	{
		global $db, $config, $phpbb_root_path, $phpEx, $phpbb_ext_kb, $kb_search;

		$errors = array();
		$kb_search = false;
		if (!isset($kb_search))
		{
			$kb_search = $phpbb_ext_kb->setup_kb_search();
		}

		include_once($phpbb_root_path . 'includes/functions_posting.' . $phpEx);

		// remove topics
		$topics = array();
		$sql = 'SELECT topic_id, article_id
			FROM '. ARTICLES_TABLE .'
			WHERE article_category_id = '.$cat_id.'';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$topics[] = $row['topic_id'];
			$articles[] = $row['article_id'];
		}
		delete_topics('topic_id', $topics, true, true, true);

		// remove articles
		$sql = 'DELETE
			FROM '. ARTICLES_TABLE .'
			WHERE article_category_id = '.$cat_id.'';
		$db->sql_query($sql);

		// remove index
		if ($kb_search)
		{
			$kb_search->index_remove($articles);
		}

		return ($errors);
	}

	// Move cat position by $steps up/down
	function move_category_by($category_row, $action = 'move_up', $steps = 1)
	{
		global $db, $table_prefix;

		$sql = 'SELECT category_id, category_name, left_id, right_id
			FROM ' . KB_CAT_TABLE . "
			WHERE parent_id = {$category_row['parent_id']}
				AND " . (($action == 'move_up') ? "right_id < {$category_row['right_id']} ORDER BY right_id DESC" : "left_id > {$category_row['left_id']} ORDER BY left_id ASC");
		$result = $db->sql_query_limit($sql, $steps);

		$target = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$target = $row;
		}
		$db->sql_freeresult($result);

		if (!sizeof($target))
		{
			return false;
		}

		if ($action == 'move_up')
		{
			$left_id = $target['left_id'];
			$right_id = $category_row['right_id'];

			$diff_up = $category_row['left_id'] - $target['left_id'];
			$diff_down = $category_row['right_id'] + 1 - $category_row['left_id'];

			$move_up_left = $category_row['left_id'];
			$move_up_right = $category_row['right_id'];
		}
		else
		{
			$left_id = $category_row['left_id'];
			$right_id = $target['right_id'];

			$diff_up = $category_row['right_id'] + 1 - $category_row['left_id'];
			$diff_down = $target['right_id'] - $category_row['right_id'];

			$move_up_left = $category_row['right_id'] + 1;
			$move_up_right = $target['right_id'];
		}

		$sql = 'UPDATE ' . KB_CAT_TABLE . "
			SET left_id = left_id + CASE
				WHEN left_id BETWEEN {$move_up_left} AND {$move_up_right} THEN -{$diff_up}
				ELSE {$diff_down}
			END,
			right_id = right_id + CASE
				WHEN right_id BETWEEN {$move_up_left} AND {$move_up_right} THEN -{$diff_up}
				ELSE {$diff_down}
			END,
			category_parents = ''
			WHERE
				left_id BETWEEN {$left_id} AND {$right_id}
				AND right_id BETWEEN {$left_id} AND {$right_id}";
		$db->sql_query($sql);
		return $target['category_name'];
	}

	function sync($cat_id)
	{
		global $db, $user;

		$errors = array();
		$sql = 'SELECT category_id, number_articles
			FROM ' . KB_CAT_TABLE .'
			WHERE category_id = '.$cat_id.'';
		$result = $db->sql_query($sql);
		$is = $articles = 0;
		while ($row = $db->sql_fetchrow($result))
		{
			$is = true;
			$number_articles = $row['number_articles'];
		}
		$db->sql_freeresult($result);
		if (!$is)
		{
			$errors[] = $user->lang['CAT_NO_EXISTS'];
			return $errors;
		}

		$sql = 'SELECT article_category_id
			FROM '. ARTICLES_TABLE .'
			WHERE article_category_id = '.$cat_id.'';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
		 	$articles++;
		}
		$db->sql_freeresult($result);

		$sql = 'UPDATE ' . KB_CAT_TABLE .'
			SET number_articles = '.$articles.'
			WHERE category_id = '.$cat_id.'';
		$db->sql_query($sql);
		return array();
	}
}

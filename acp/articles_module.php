<?php
/**
*
* @package phpBB Extension - Knowlege Base
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace Sheer\knowlegebase\acp;

class articles_module
{
	var $u_action;

	function main($id, $mode)
	{
		global $config, $db, $template, $request, $cache, $phpbb_root_path, $table_prefix, $phpEx, $auth, $user, $phpbb_container, $phpbb_ext_kb, $phpbb_log;
		$phpbb_admin_path = (defined('PHPBB_ADMIN_PATH')) ? PHPBB_ADMIN_PATH : './';

		define ('ARTICLES_TABLE', $table_prefix.'kb_articles');
		define ('KB_CAT_TABLE', $table_prefix.'kb_categories');

		$phpbb_ext_kb = new \Sheer\knowlegebase\inc\functions_kb($config, $db, $cache, $user, $template, $auth, $phpbb_log, $phpbb_root_path, $phpEx, $table_prefix);

		$this->tpl_name = 'acp_articles_body';
		$this->page_title = $user->lang('ACP_LIBRARY_ARTICLES');

		$action = $request->variable('action', '');
		$article_count = 0;
		$errors = array();
		$kb_config = $phpbb_ext_kb->obtain_kb_config();
		$per_page = $kb_config['articles_per_page'];

		// Sort keys
		$sort_days	= $request->variable('st', 0);
		$sort_key	= $request->variable('sk', 'd');
		$sort_dir	= $request->variable('sd', 'd');
		$start		= $request->variable('start', 0);
		$sort		= $request->variable('sort', false);

		// Sorting
		$limit_days = array();
		$sort_by_text = array('u' => $user->lang['ARTICLE_DATE'], 'd' => $user->lang['SORT_DATE'], 'c' => $user->lang['CATEGORY'], 'e' => $user->lang['EDIT_DATE']);
		$sort_by_sql = array('u' => 'article_title', 'd' => 'article_date', 'c' => 'article_category_id', 'e' => 'edit_date');

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		$keywords_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);
		// Sorting
		$sql_sort = (($sort_dir == 'd') ? 'DESC' : 'ASC');
		$order_by = $sort_by_sql[$sort_key];

		$sql = 'SELECT COUNT(article_id) as article_count
			FROM '. ARTICLES_TABLE .'';
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$article_count = $row['article_count'];
		$db->sql_freeresult($result);

		if (empty($per_page)) $per_page = 10;
		$sql = 'SELECT *
			FROM '. ARTICLES_TABLE .'
			ORDER BY '.$order_by.' '.$sql_sort.'
			LIMIT '.$start.', '.$per_page.'';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$category_data = $phpbb_ext_kb->get_cat_info($row['article_category_id']);
			$template->assign_block_vars('articles', array(
				'ID'				=> $row['article_id'],
				'ARTICLE_TITLE'		=> $row['article_title'],
				'CATEGORY_ID'		=> $row['article_category_id'],
				'CATEGORY'			=> ($category_data['category_name']) ? : $user->lang['CAT_NO_EXISTS'],
				'U_CATEGORY'		=> append_sid("{$phpbb_admin_path}index.$phpEx",'i=-Sheer-knowlegebase-acp-manage_module&amp;mode=manage&amp;parent_id='.$row['article_category_id'].''),
				'U_ARTICLE'			=> append_sid("{$phpbb_root_path}knowlegebase/article",'k='.$row['article_id'].'"'),
				'U_ARTICLE_EDIT'	=> append_sid("{$phpbb_root_path}knowlegebase/edit",'k='.$row['article_id'].'"'),
				'AUTHOR'			=> $row['author'],
				'TIME'				=> $user->format_date($row['article_date']),
				'EDIT_TIME'			=> ($row['edit_date']) ? $user->format_date($row['edit_date']) : 0,
				'U_MOVE'			=> $this->u_action . '&amp;action=move&amp;aid='.$row['article_id'].'',
				'U_DELETE'			=> $this->u_action . '&amp;action=delete&amp;aid='.$row['article_id'].'',
				)
			);
		}
		$db->sql_freeresult($result);

		$pagination_url = $this->u_action .'&amp;' . $u_sort_param .'&amp;' . $keywords_param;
		$pagination = $phpbb_container->get('pagination');
		if ($article_count)
		{
			$pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $article_count, $per_page, $start);
		}

		$template->assign_vars(array(
			'S_SORT_KEY'	=> $s_sort_key,
			'S_SORT_DIR'	=> $s_sort_dir,
			'S_ARTICLES'	=> true,
			'S_ACTION'		=> $this->u_action . "&amp;$u_sort_param$keywords_param&amp;start=$start",
			'ICON_MOVE'		=> '<img src="' . $phpbb_admin_path . 'images/icon_sync.gif" alt="' . $user->lang['MOVE'] . '" title="' . $user->lang['MOVE'] . '" />',
			'TOTAL_ITEMS'		=> $user->lang('TOTAL_ITEMS', (int) $article_count),
			'PAGE_NUMBER'		=> $pagination->on_page($article_count, $per_page, $start),
			)
		);

		switch ($action)
		{
			case 'move':
				$this->move_article();
			break;
			case 'delete':
				$this->delete_article();
			break;
			default:
			break;
		}
	}

	function move_article()
	{
		global $db, $template, $user, $request, $phpbb_ext_kb;

		$article_id = $request->variable('aid', 0);
		$submit		= (isset($_POST['submit'])) ? true : false;

		// move to
		$to_id = $request->variable('to_id', 0);
		// move from
		$info = $phpbb_ext_kb->get_kb_article_info($article_id);

		if ($submit)
		{
			$phpbb_ext_kb->kb_move_article($article_id, $info['article_title'], $info['article_category_id'], $to_id);
			meta_refresh(3, $this->u_action);
			trigger_error($user->lang['ARTICLE_MOVED']);
		}

		$template->assign_vars(array(
			'S_MOVE_ART'				=> true,
			'S_MOVE_CATEGORY_OPTIONS'	=> $phpbb_ext_kb->make_category_select(0, $info['article_category_id'], false, true, false),
			'S_ACTION'				=> ''.$this->u_action.'&amp;action=move&amp;aid='.$article_id.'',
			)
		);
	}

	function delete_article()
	{
		global $db, $user, $request, $phpbb_ext_kb, $template;
		$article_id = $request->variable('aid', 0);
		$submit = $request->variable('submit', false);
		$kb_search = false;
		$kb_search = $phpbb_ext_kb->setup_kb_search();
		$article = $phpbb_ext_kb->get_kb_article_info($article_id);

		$template->assign_vars(array(
			'S_ACTION'	=> ''.$this->u_action.'&amp;action=delete&amp;aid='.$article_id.'',
			)
		);

		$s_hidden_fields = build_hidden_fields(array(
			'aid'		=> $article_id,
			'action'	=> 'delete'
		));
		if (confirm_box(true))
		{
			$phpbb_ext_kb->kb_delete_article($article_id, $article['article_title']);
			if ($kb_search)
			{
				$author_ids[] = $article['author_id'];
				$kb_search->index_remove($article_id, $author_ids);
			}
			meta_refresh(3, $this->u_action);
			trigger_error($user->lang['ARTICLE_DELETED']);
		}
		else
		{
			confirm_box(false, $user->lang['CONFIRM_DELETE_ARTICLE'], $s_hidden_fields);
		}
	}
}

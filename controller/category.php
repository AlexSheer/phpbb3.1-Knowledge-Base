<?php
/**
*
* @package phpBB Extension - Knowlege base
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace sheer\knowlegebase\controller;

use Symfony\Component\HttpFoundation\Response;

class category
{
	protected $config;
	protected $db;
	protected $auth;
	protected $template;
	protected $user;
	protected $helper;
	protected $phpbb_root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\pagination $pagination, \phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, $phpbb_root_path, $php_ext, $table_prefix, \sheer\knowlegebase\inc\functions_kb $kb)
	{
		$this->config = $config;
		$this->request = $request;
		$this->pagination = $pagination;
		$this->db = $db;
		$this->auth = $auth;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->table_prefix = $table_prefix;
		$this->kb = $kb;
	}

	public function cat()
	{
		$cat_id = $this->request->variable('id', 0);
		$start = $this->request->variable('start', 0);

		if (!$cat_id)
		{
			redirect(append_sid("{$this->phpbb_root_path}knowlegebase"));
		}
		$sql_where = ($this->auth->acl_get('a_manage_kb') || $this->kb->acl_kb_get($cat_id, 'kb_m_approve')) ? '' : 'AND approved = 1';

		$kb_config = $this->kb->obtain_kb_config();
		$per_page = $kb_config['articles_per_page'];

		$sql = 'SELECT category_id, category_name
			FROM '. KB_CAT_TABLE .'
			WHERE category_id = '.$cat_id.'';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (empty($row))
		{
			trigger_error ('CAT_NO_EXISTS');
		}

		$this->template->assign_block_vars('par_cat_row', array( 'PAR_CAT_NAME' => $row['category_name']) );

		$sql = 'SELECT COUNT(article_id) as article_count
			FROM '. ARTICLES_TABLE .'
			WHERE article_category_id = '.$cat_id.'
			'. $sql_where .'';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$article_count = $row['article_count'];
		$this->db->sql_freeresult($result);

		$pagination_url = append_sid("{$this->phpbb_root_path}knowlegebase/category", 'id='.$cat_id.'');
		if ($article_count)
		{
			$this->pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $article_count, $per_page, $start);
		}

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $this->user->lang['LIBRARY'],
			'U_VIEW_FORUM'	=> append_sid("{$this->phpbb_root_path}knowlegebase"),
			)
		);

		$parents_cats = array();
		foreach ($this->kb->get_category_branch($cat_id, 'parents') as $row)
		{
			$parents_cats[] = $row['category_id'];
			$this->template->assign_block_vars('navlinks', array(
				'FORUM_NAME'	=> $row['category_name'],
				'U_VIEW_FORUM'	=> append_sid("{$this->phpbb_root_path}knowlegebase/category?id=$row[category_id]"),
				)
			);
		}

		$sql = 'SELECT *
			FROM ' . KB_CAT_TABLE . '
			WHERE parent_id = ' . $cat_id . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql);
		while ($cat_row = $this->db->sql_fetchrow($result))
		{
			$exclude_cats = array();
			foreach ($this->kb->get_category_branch($cat_row['category_id'], 'children') as $row)
			{
				$exclude_cats[] = $row['category_id'];
			}
			array_shift($exclude_cats);

			$this->template->assign_block_vars('cat_row', array(
				'CAT_ID'	=> $cat_row['category_id'],
				'CAT_NAME'	=> $cat_row['category_name'],
				'CAT_DESCRIPTION' => $cat_row['category_details'],
				'U_CAT'		=> append_sid("{$this->phpbb_root_path}knowlegebase/category", "id=$cat_row[category_id]"),
				'ARTICLES'	=> $cat_row['number_articles'],
				'SUBCATS'	=> $this->kb->get_cat_list ($cat_row['parent_id'], $exclude_cats),
				)
			);
		}

		if (!isset($per_page))
		{
			$per_page = 10;
		}

		$sql = 'SELECT *
			FROM '. ARTICLES_TABLE .'
			WHERE article_category_id = '.$cat_id.'
			'. $sql_where .'
			ORDER BY article_date DESC';
		$result = $this->db->sql_query_limit($sql, $per_page, $start);
		while($art_row = $this->db->sql_fetchrow($result))
		{
			$art_id		= $art_row['article_id'];
			$author		= $art_row['author'];
			$author_id	= $art_row['author_id'];
			$temp_url = append_sid("{$this->phpbb_root_path}memberlist.".$this->php_ext."", 'mode=viewprofile&amp;u=' . $art_row['author_id']);
			$author_kb_art = '<a href="' . $temp_url . '" class="gensmall">' . $art_row['author'] . '</a>';
			$this->template->assign_block_vars('art_row', array(
				'ARTICLE'				=> '<b><a href="' . append_sid("{$this->phpbb_root_path}knowlegebase/article", 'k=' . $art_row['article_id'] .'') . '">' . $art_row['article_title'] . '</a></b>',
				'ARTICLE_AUTHOR'		=> $author_kb_art,
				'ARTICLE_DESCRIPTION'	=> $art_row['article_description'],
				'ARTICLE_DATE'			=> $this->user->format_date($art_row['article_date']),
				'ART_VIEWS'				=> $art_row['views'],
				'U_DELETE'				=> append_sid("{$this->phpbb_root_path}knowlegebase/edit", "id=$cat_id&amp;mode=delete&amp;k=$art_id"),
				'U_EDIT_ART'			=> append_sid("{$this->phpbb_root_path}knowlegebase/edit", "id=$cat_id&amp;mode=edit&amp;k=$art_id"),
				'S_CAN_DELETE'			=> ($this->auth->acl_get('a_manage_kb') || $this->kb->acl_kb_get($cat_id, 'kb_m_delete') || ($this->kb->acl_kb_get($cat_id, 'kb_u_delete') && $this->user->data['user_id'] == $author_id)) ? true : false,
				'S_CAN_EDIT'			=> ($this->auth->acl_get('a_manage_kb') || $this->kb->acl_kb_get($cat_id, 'kb_m_edit')   || ($this->kb->acl_kb_get($cat_id, 'kb_u_edit')   && $this->user->data['user_id'] == $author_id)) ? true : false,
				'S_APPROVED'			=> ($art_row['approved']) ? true : false,
				)
			);
		}
		$this->db->sql_freeresult($result);

		if (empty($art_id))
		{
			$this->template->assign_block_vars('no_articles', array( 'COMMENT' => $this->user->lang['NO_ARTICLES']) );
		}

		$this->template->assign_vars(array(
			'DELETE_IMG' 			=> $this->user->img('icon_post_delete', 'DELETE'),
			'EDIT_IMG'				=> $this->user->img('icon_post_edit', 'EDIT'),
			'CATS_DROPBOX'			=> $this->kb->make_category_dropbox($cat_id, false, true, false, false),
			'CATS_BOX'				=> $this->kb->make_category_select($cat_id, false, true, false, false),
			'CATEGORY'				=> $row['category_name'],
			'CATEGORY_ID'			=> $row['category_id'],
			'TOTAL_ITEMS'			=> $this->user->lang('TOTAL_ITEMS', (int) $article_count),
			'PAGE_NUMBER'			=> $this->pagination->on_page($article_count, $per_page, $start),
			'U_ADD_ARTICLE'			=> append_sid("{$this->phpbb_root_path}knowlegebase/post", 'id=' . $cat_id .' '),
			'U_KB'					=> append_sid("{$this->phpbb_root_path}knowlegebase/"),
			'U_KB_SEARCH'			=> append_sid("{$this->phpbb_root_path}knowlegebase/library_search"),
			'S_CAN_ADD'				=> ($this->auth->acl_get('a_manage_kb') || $this->kb->acl_kb_get($cat_id, 'kb_u_add')) ? true : false,
			'S_ACTION'				=> append_sid("{$this->phpbb_root_path}knowlegebase/category", 'id=' . $cat_id .' '),
			'S_IS_SEARCH'			=> ($this->config['kb_search']) ? true : false,
			'S_KB_SEARCH_ACTION'	=> append_sid("{$this->phpbb_root_path}knowlegebase/library_search"),
			)
		);

		$this->kb->gen_kb_auth_level($cat_id);

		page_header(''. $this->user->lang('LIBRARY'). ' &raquo; ' . $this->user->lang('CATEGORY') . '');
		$this->template->set_filenames(array(
			'body' => 'kb_cat_body.html'));

		page_footer();
		return new Response($this->template->return_display('body'), 200);
	}
}

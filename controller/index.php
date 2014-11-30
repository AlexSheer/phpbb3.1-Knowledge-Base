<?php
/**
*
* @package phpBB Extension - Knowlege base
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace Sheer\knowlegebase\controller;

use Symfony\Component\HttpFoundation\Response;

class index
{
	protected $config;
	protected $db;
	protected $auth;
	protected $template;
	protected $user;
	protected $helper;
	protected $phpbb_root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\pagination $pagination, \phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, $phpbb_root_path, $php_ext, $table_prefix, \Sheer\knowlegebase\inc\functions_kb $kb)
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

	public function main()
	{
		$category_id = $this->request->variable('id', 0);
		$sql = 'SELECT category_id, category_name, category_details, parent_id
			FROM '.KB_CAT_TABLE.'
			WHERE parent_id = 0
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql);
		while ($catrow = $this->db->sql_fetchrow($result))
		{
			$exclude_cats = array();
			foreach ($this->kb->get_category_branch($catrow['category_id'], 'children') as $row)
			{
				$exclude_cats[] = $row['category_id'];
			}
			array_shift($exclude_cats);

			$sql_where = ($this->auth->acl_get('a_manage_kb') || $this->kb->acl_kb_get($catrow['category_id'], 'kb_m_approve')) ? '' : 'AND approved = 1';
			$sql = 'SELECT COUNT(article_id) AS articles
				FROM ' . ARTICLES_TABLE . '
				WHERE article_category_id = '. $catrow['category_id'] .'
					' . $sql_where;
			$res = $this->db->sql_query($sql);
			$art_row = $this->db->sql_fetchrow($res);
			$this->db->sql_freeresult($res);

			$this->template->assign_block_vars('catrow', array(
				'U_CATEGORY'		=> append_sid("{$this->phpbb_root_path}knowlegebase/category", 'id='.$catrow['category_id'].''),
				'CAT_NAME'			=> $catrow['category_name'],
				'CAT_ARTICLES'		=> $art_row['articles'],
				'CAT_DESCRIPTION' 	=> $catrow['category_details'],
				'SUBCATS'			=> $this->kb->get_cat_list ($catrow['parent_id'], $exclude_cats),
				)
			);
		}
		$this->db->sql_freeresult($result);


// Output the page
		$this->template->assign_vars(array(
			'LIBRARY_TITLE'	=> $this->user->lang('LIBRARY'),
		));

		$this->template->assign_vars(array(
			'S_ACTION'				=> append_sid("{$this->phpbb_root_path}knowlegebase/category", 'id='.$category_id.''),
			'U_KB_SEARCH'			=> append_sid("{$this->phpbb_root_path}knowlegebase/library_search"),
			'S_IS_SEARCH'			=> ($this->config['kb_search']) ? true : false,
			'S_KB_SEARCH_ACTION'	=> append_sid("{$this->phpbb_root_path}knowlegebase/library_search"),
			'CATS_DROPBOX'			=> $this->kb->make_category_dropbox(0, false, true, false, false),
			)
		);

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $this->user->lang['LIBRARY'],
			'U_VIEW_FORUM'	=> append_sid("{$this->phpbb_root_path}knowlegebase"),
			)
		);

		page_header($this->user->lang('LIBRARY'));
		$this->template->set_filenames(array(
			'body' => 'kb_index_body.html'));

		page_footer();
		return new Response($this->template->return_display('body'), 200);
	}
}

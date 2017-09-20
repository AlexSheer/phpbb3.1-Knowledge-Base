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

class article
{
	protected $config;
	protected $request;
	protected $db;
	protected $auth;
	protected $template;
	protected $user;
	protected $phpbb_root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\user $user, $phpbb_root_path, $php_ext, $table_prefix, \sheer\knowlegebase\inc\functions_kb $kb)
	{
		$this->config = $config;
		$this->request = $request;
		$this->db = $db;
		$this->auth = $auth;
		$this->template = $template;
		$this->user = $user;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->table_prefix = $table_prefix;
		$this->kb = $kb;
	}

	public function show()
	{
		$art_id = $this->request->variable('k', 0);
		$mode = $this->request->variable('mode', '');

		if (empty($art_id))
		{
			trigger_error ($this->user->lang['NO_ID_SPECIFIED']);
		}

		$sql = 'SELECT *
			FROM '. ARTICLES_TABLE .'
			WHERE article_id = '.$art_id.'';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if(empty($row))
		{
			trigger_error('ARTICLE_NO_EXISTS');
		}
		$kb_data = $this->kb->obtain_kb_config();
		$fid = $kb_data['forum_id'];

		$cat_id = $row['article_category_id'];
		$catrow = $this->kb->get_cat_info($row['article_category_id']);
		if (empty($catrow))
		{
			trigger_error($this->user->lang['CAT_NO_EXISTS']);
		}
		$path = $catrow['category_name'];

		$this->template->assign_vars(array(
			'ARTICLE_CATEGORY'	=>  '<a href="' . append_sid("{$this->phpbb_root_path}knowlegebase/category", 'id='.$catrow['category_id'].'') . '">' . $catrow['category_name'] . '</a>',
			'CATS_BOX'	=> '<option value="0">'.$this->user->lang['CATEGORIES_LIST'].'</option>'.$this->kb->make_category_select($cat_id, false, true, false, false).'',
			'S_ACTION'	=> append_sid("{$this->phpbb_root_path}knowlegebase/category", 'id='.$cat_id.''),
			)
		);

		$author = $row['author'];
		$temp_url = append_sid("{$this->phpbb_root_path}memberlist.".$this->php_ext."", 'mode=viewprofile&amp;u=' . $row['author_id']);
		$author_kb_art = '<a href="' . $temp_url . '" class="gensmall">' . $author . '</a></span>';
		$comment_topic_id = $row['topic_id'];

		// Get comments
		if ($comment_topic_id)
		{
			$count = -1;
			$sql = 'SELECT DISTINCT p.poster_id, p.post_time, p.post_subject, p.post_text, p.bbcode_uid, p.bbcode_bitfield, u.user_id, u.username
				FROM '. POSTS_TABLE .' p, '. USERS_TABLE .' u
				WHERE p.topic_id = '.$comment_topic_id.'
				AND (p.poster_id = u.user_id)
				ORDER BY p.post_time ASC';
			$res = $this->db->sql_query($sql);
			while($postrow = $this->db->sql_fetchrow($res))
			{
				$count++;
				if ($count > 0)
				{
					$this->template->assign_block_vars('postrow', array(
						'POSTER_NAME'	=> $postrow['username'],
						'POST_DATE'		=> $this->user->format_date ($postrow['post_time']),
						'POST_SUBJECT'	=> $postrow['post_subject'],
						'MESSAGE'		=> generate_text_for_display($postrow['post_text'], $postrow['bbcode_uid'], $postrow['bbcode_bitfield'], 3, true),
						)
					);
				}
			}
			$this->db->sql_freeresult($res);

			$temp_url = append_sid("{$this->phpbb_root_path}viewtopic.".$this->php_ext."", 'f='.$fid.'&amp;t='.$row['topic_id']);
		}
		$views = $row['views'];
		$article = $row['article_id'];
		$text = generate_text_for_display($row['article_body'], $row['bbcode_uid'], $row['bbcode_bitfield'], 3, true);

		$this->template->assign_vars(array(
			'ARTICLE_AUTHOR'		=> $author_kb_art,
			'ARTICLE_DESCRIPTION' 	=> $row['article_description'],
			'ARTICLE_DATE'			=> $this->user->format_date($row['article_date']),
			'ART_VIEWS'				=> $row['views'],
			'ARTICLE_TITLE'			=> $row['article_title'],
			'ARTICLE_TEXT'			=> $text,
			'VIEWS'					=> $views,
			'U_EDIT_ART'			=> append_sid("{$this->phpbb_root_path}knowlegebase/edit", "id=$cat_id&amp;k=$art_id"),
			'U_DELETE_ART'			=> append_sid("{$this->phpbb_root_path}knowlegebase/edit", "id=$cat_id&amp;k=$art_id&amp;mode=delete"),
			'U_APPROVE_ART'			=> append_sid("{$this->phpbb_root_path}knowlegebase/approve", "id=$art_id"),
			'U_PRINT'				=> append_sid("{$this->phpbb_root_path}knowlegebase/article", 'k=' . $row['article_id'] .'&amp;mode=print'),
			'COMMENTS'				=> ($comment_topic_id) ? ''. $this->user->lang['COMMENTS'] .': '. $count . '' : '',
			'U_COMMENTS'			=> $temp_url,
			'S_CAN_EDIT'			=> ($this->kb->acl_kb_get($cat_id, 'kb_m_edit')   || ($this->user->data['user_id'] == $row['author_id'] && $this->kb->acl_kb_get($cat_id, 'kb_u_edit')   || $this->auth->acl_get('a_manage_kb'))) ? true : false,
			'S_CAN_DELETE'			=> ($this->kb->acl_kb_get($cat_id, 'kb_m_delete') || ($this->user->data['user_id'] == $row['author_id'] && $this->kb->acl_kb_get($cat_id, 'kb_u_delete') || $this->auth->acl_get('a_manage_kb'))) ? true : false,
			'S_CAN_APPROOVE'		=> ($this->auth->acl_get('a_manage_kb') || $this->kb->acl_kb_get($cat_id, 'kb_m_approve')) ? true : false,
			'COUNT_COMMENTS'		=> ($comment_topic_id) ? '['. $this->user->lang['LEAVE_COMMENTS'] .']' : '',
			'U_FORUM'				=> generate_board_url() . '/',
			'S_APPROVED'			=> $row['approved'],
			)
		);

		if ($mode != 'print' && $row['approved'])
		{
		// Increase the number of views
			++$views;
			$sql = 'UPDATE '. ARTICLES_TABLE .'
				SET views = '.$views.'
				WHERE article_id = '.$article.'';
			$this->db->sql_query($sql);
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

		$html_template = (($mode != 'print')) ? 'kb_article_body.html' : 'kb_article_body_print.html';

		page_header($this->user->lang('LIBRARY'));
		$this->template->set_filenames(array(
			'body' => $html_template));

		page_footer();
		return new Response($this->template->return_display('body'), 200);
	}
}

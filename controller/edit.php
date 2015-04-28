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

class edit
{
	protected $config;
	protected $request;
	protected $db;
	protected $auth;
	protected $template;
	protected $user;
	protected $phpbb_root_path;
	protected $php_ext;
	protected $log;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\user $user, \phpbb\cache\service $cache, \phpbb\log\log_interface $log, $phpbb_root_path, $php_ext, $table_prefix, \Sheer\knowlegebase\inc\functions_kb $kb)
	{
		$this->config = $config;
		$this->request = $request;
		$this->db = $db;
		$this->auth = $auth;
		$this->template = $template;
		$this->user = $user;
		$this->phpbb_cache = $cache;
		$this->phpbb_log = $log;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->table_prefix = $table_prefix;
		$this->kb = $kb;
	}

	public function edit_article()
	{
		$this->user->add_lang('posting');
		$art_id = $this->request->variable('k', 0);
		$mode = $this->request->variable('mode', '');
		$kb_search = false;

		if (empty($art_id))
		{
			trigger_error ($this->user->lang['NO_ID_SPECIFIED']);
		}

		// Setup search engine
		$kb_search = $this->kb->setup_kb_search();
		$this->phpbb_log->set_log_table(KB_LOG_TABLE);

		$kb_data = $this->kb->obtain_kb_config();
		$fid = $kb_data['forum_id'];
		$sql = 'SELECT forum_id
			FROM '.FORUMS_TABLE.'
			WHERE forum_id = '.$fid.'';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$is_forum = (!empty($row['forum_id'])) ? true : false;
		$this->db->sql_freeresult($result);

		$submit = (isset($_POST['submit'])) ? true : false;
		$preview = (isset($_POST['preview'])) ? true : false;
		$delete = (isset($_POST['delete'])) ? true : false;
		$cancel = (isset($_POST['cancel'])) ? true : false;
		$error= array();

		$bbcode_status	= true;
		$smilies_status	= true;
		$img_status 	= true;
		$url_status		= true;
		$flash_status	= true;

		$allowed_bbcode = $allowed_smilies = $allowed_urls = true;

		$sql = 'SELECT DISTINCT a.author_id, a.article_category_id, a.topic_id, a.article_description, a.article_title, a.article_body, a.bbcode_uid, a.bbcode_bitfield, c.category_name, c.category_id
			FROM '. ARTICLES_TABLE .' a, ' . KB_CAT_TABLE . ' c
			WHERE article_id = '.$art_id.'
				AND (c.category_id = a.article_category_id)';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);

		if (empty($row))
		{
			trigger_error('ARTICLE_NO_EXISTS');
		}

		$author_id				= $row['author_id'];
		$cat_id					= $row['article_category_id'];
		$category_name			= $row['category_name'];
		$topic_id 				= $row['topic_id'];
		$article_description 	= $row['article_description'];
		$article_title 			= $row['article_title'];
		$message 				= $row['article_body'];

		$edit_allowed = ($this->kb->acl_kb_get($cat_id, 'kb_m_edit') || (
			$this->user->data['user_id'] == $author_id &&
			$this->kb->acl_kb_get($cat_id, 'kb_u_edit') ||
			$this->auth->acl_get('a_manage_kb')
		));

		$delete_allowed = ($this->kb->acl_kb_get($cat_id, 'kb_m_delete') || (
			$this->user->data['user_id'] == $author_id &&
			$this->kb->acl_kb_get($cat_id, 'kb_u_delete') ||
			$this->auth->acl_get('a_manage_kb')
		));

		if (!$edit_allowed)
		{
			trigger_error('RULES_KB_MOD_EDIT_CANNOT');
		}

		$article_text = $this->decode_message($message, $row['bbcode_uid']);

		$article_title			= $this->request->variable('subject', $article_title, true);
		$article_text			= $this->request->variable('message', $article_text, true);
		$article_description	= $this->request->variable('descr', $article_description, true);
		$id						= $this->request->variable('to_id', 0);

		if (!$article_title)
		{
			$error[] = $this->user->lang['NO_TITLE'];
		}

		if (!$article_description)
		{
			$error[] = $this->user->lang['NO_DESCR'];
		}

		if (!$article_text)
		{
			$error[] = $this->user->lang['NO_TEXT'];
		}

		if ($mode == 'delete' || $delete)
		{
			if (!$delete_allowed)
			{
				trigger_error('RULES_KB_MOD_DELETE_CANNOT');
			}

			$s_hidden_fields = build_hidden_fields(array(
				'mode'	=> 'delete',
				'k'		=> $art_id)
			);

			if (confirm_box(true))
			{
				$this->kb->kb_delete_article($art_id, $article_title);
				if ($kb_search)
				{
					$author_ids[] = $author_id;
					$kb_search->index_remove($art_id, $author_ids);
				}
				$msg = $this->user->lang['ARTICLE_DELETED'];
				$root = append_sid("{$this->phpbb_root_path}knowlegebase/category",'id='. $cat_id .'');
				$msg .= '<br /><br />' . sprintf($this->user->lang['RETURN_CAT'], '<a href="' . $root . '">', '</a>');
				$this->phpbb_cache->destroy('sql', KB_CAT_TABLE);
				$this->phpbb_cache->destroy('sql', ARTICLES_TABLE);
				meta_refresh(3, $root);
				trigger_error($msg);
			}
			else
			{
				confirm_box(false, $this->user->lang['CONFIRM_DELETE_ARTICLE'], $s_hidden_fields);
			}
		}

		if ($cancel)
		{
			redirect(append_sid("{$this->phpbb_root_path}knowlegebase/category",'id=' . $cat_id . ''));
		}

		if ($submit and !sizeof($error))
		{
			$uid = $bitfield = $options = '';
			generate_text_for_storage($article_text, $uid, $bitfield, $options, true, true, true);
			if (!$bitfield)
			{
				$bitfield = 'QA==';
			}

			$sql_data = array(
				'article_title'			=> $article_title,
				'article_description'	=> $article_description,
				'bbcode_uid'			=> $uid,
				'bbcode_bitfield'		=> $bitfield,
				'article_body'			=> $article_text,
				'edit_date'				=> time()
			);

			$sql = 'UPDATE ' . ARTICLES_TABLE . '
				SET ' . $this->db->sql_build_array('UPDATE', $sql_data) . '
				WHERE article_id = ' . $art_id . '';
			$this->db->sql_query($sql);

			// Upd search index
			if ($kb_search)
			{
				$kb_search->index('edit', $art_id, $article_text, $article_title, $author_id);
			}

			$redirect = append_sid("{$this->phpbb_root_path}knowlegebase/article",'k='. $art_id .'');
			$root = append_sid("{$this->phpbb_root_path}knowlegebase/category",'id='. $cat_id .'');
			$msg = $this->user->lang['ARTICLE_EDITED'];
			$msg .= '<br /><br />' . sprintf($this->user->lang['RETURN_ARTICLE'], '<a href="' . $redirect . '">', '</a>');
			$msg .= '<br /><br />' . sprintf($this->user->lang['RETURN_CAT'], '<a href="' . $root . '">', '</a>');
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_LIBRARY_EDIT_ARTICLE', time(), array($article_title, $category_name));

			meta_refresh(3, $redirect);
			trigger_error($msg);
		}

		if ($preview and !sizeof($error))
		{
			$uid = $bitfield = $options = '';
			$allowed_bbcode = $allowed_smilies = $allowed_urls = true;
			$preview_title = $article_title;
			$preview_text = $article_text;
			generate_text_for_storage($preview_text, $uid, $bitfield, $options, true, true, true);
			$preview_text = generate_text_for_display($preview_text, $uid, $bitfield, $options);
		}
		include($this->phpbb_root_path . 'includes/functions_posting.' . $this->php_ext);
		generate_smilies('inline', 0);
		include($this->phpbb_root_path . 'includes/functions_display.' . $this->php_ext);
		display_custom_bbcodes();

		$this->template->assign_vars(array(
			'L_POST_A'				=> $this->user->lang['EDIT_ARTICLE'],
			'CATEGORY_NAME'			=> $category_name,
			'L_DESCR' 				=> $this->user->lang['DESCR'],
			'DESCR'					=> $article_description,
			'SUBJECT'				=> $article_title,
			'MESSAGE'				=> $article_text,
			'ERROR'					=> (sizeof($error)) ? implode('<br />', $error) : '',
			'S_DISPLAY_PREVIEW'		=> (!sizeof($error) && $preview) ? true : false,
			'PREVIEW_MESSAGE'		=> (isset($preview_text)) ? $preview_text : '',
			'PREVIEW_SUBJECT'		=> (isset($preview_title)) ? $preview_title : '',
			'POST_DATE'				=> $this->user->format_date(time()),
			'S_EDIT_POST'			=> true,

			'S_BBCODE_ALLOWED'		=> ($bbcode_status) ? 1 : 0,
			'BBCODE_STATUS'			=> ($bbcode_status) ? sprintf($this->user->lang['BBCODE_IS_ON'], '<a href="' . append_sid("{$this->phpbb_root_path}faq.$this->php_ext", 'mode=bbcode') . '">', '</a>') : sprintf($this->user->lang['BBCODE_IS_OFF'], '<a href="' . append_sid("{$phpbb_root_path}faq.$phpEx", 'mode=bbcode') . '">', '</a>'),
			'IMG_STATUS'			=> ($img_status) ? $this->user->lang['IMAGES_ARE_ON'] : $this->user->lang['IMAGES_ARE_OFF'],
			'FLASH_STATUS'			=> ($flash_status) ? $this->user->lang['FLASH_IS_ON'] : $this->user->lang['FLASH_IS_OFF'],
			'SMILIES_STATUS'		=> ($smilies_status) ? $this->user->lang['SMILIES_ARE_ON'] : $this->user->lang['SMILIES_ARE_OFF'],
			'URL_STATUS'			=> ($bbcode_status && $url_status) ? $this->user->lang['URL_IS_ON'] : $this->user->lang['URL_IS_OFF'],
			'S_LINKS_ALLOWED'		=> $url_status,

			'S_BBCODE_IMG'			=> $img_status,
			'S_BBCODE_URL'			=> $url_status,
			'S_BBCODE_FLASH'		=> $flash_status,
			'S_BBCODE_QUOTE'		=> true,
			'S_CAN_DELETE'			=> ($delete_allowed) ? true : false,
			'U_KB'					=> append_sid("{$this->phpbb_root_path}knowlegebase/"),
			'CATS_BOX'				=> '<option value="0" disabled="disabled">'.$this->user->lang['CATEGORIES_LIST'].'</option>'.$this->kb->make_category_select($cat_id, false, true, false, false).'',
			'S_POST_ACTION'			=> ($delete) ? append_sid("{$this->phpbb_root_path}knowlegebase/edit",'id='. $cat_id .'&amp;k='.$k.'&amp;mode=delete') : '',
			)
		);

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $this->user->lang['LIBRARY'],
			'U_VIEW_FORUM'	=> append_sid("{$this->phpbb_root_path}knowlegebase"),
			)
		);

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $category_name,
			'U_VIEW_FORUM'	=> append_sid("{$this->phpbb_root_path}knowlegebase/category?id=$cat_id"),
			)
		);

		page_header($this->user->lang('LIBRARY'));
		$this->template->set_filenames(array(
			'body' => 'kb_post_body.html'));

		page_footer();
		return new Response($this->template->return_display('body'), 200);
	}

	public function decode_message($message, $bbcode_uid = '')
	{
		if ($bbcode_uid)
		{
			$match = array('<br />', "[/*:m:$bbcode_uid]", ":u:$bbcode_uid", ":o:$bbcode_uid", ":$bbcode_uid");
			$replace = array("\n", '', '', '', '');
		}
		else
		{
			$match = array('<br />');
			$replace = array("\n");
		}

		$message = str_replace($match, $replace, $message);
		$match = get_preg_expression('bbcode_htm');
		$replace = array('\1', '\1', '\2', '\1', '', '');
		return preg_replace($match, $replace, $message);
	}
}

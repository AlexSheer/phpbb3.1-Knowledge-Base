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

class post
{
	protected $config;
	protected $request;
	protected $db;
	protected $auth;
	protected $template;
	protected $user;
	protected $phpbb_cache;
	protected $phpbb_root_path;
	protected $php_ext;
	protected $log;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\user $user, \phpbb\cache\service $cache, \phpbb\log\log_interface $log, \phpbb\notification\manager $notification_manager, $phpbb_root_path, $php_ext, $table_prefix, \Sheer\knowlegebase\inc\functions_kb $kb, $helper)
	{
		$this->config = $config;
		$this->request = $request;
		$this->db = $db;
		$this->auth = $auth;
		$this->template = $template;
		$this->user = $user;
		$this->phpbb_cache = $cache;
		$this->phpbb_log = $log;
		$this->notification_manager = $notification_manager;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->table_prefix = $table_prefix;
		$this->kb = $kb;
		$this->helper = $helper;
	}

	public function post_article()
	{
		$this->user->add_lang('posting');
		$this->phpbb_log->set_log_table(KB_LOG_TABLE);

		$kb_data = $this->kb->obtain_kb_config();
		$fid = $kb_data['forum_id'];

		if (empty($kb_data['forum_id']) && $kb_data['anounce'])
		{
			trigger_error('WARNING_DEFAULT_CONFIG');
		}

		$cat_id	= $this->request->variable('id', 0);

		if(!$this->auth->acl_get('a_manage_kb') && !$this->kb->acl_kb_get($cat_id, 'kb_u_add'))
		{
			trigger_error('RULES_KB_ADD_CANNOT');
		}

		if ($this->config['kb_search_type'])
		{
			if (preg_match('#^\w+$#', $this->config['kb_search_type']) || file_exists($this->phpbb_root_path . 'ext/Sheer/knowlegebase/search/' . $this->config['kb_search_type'] . '.' . $this->php_ext))
			{
				include($this->phpbb_root_path . 'ext/Sheer/knowlegebase/search/' . $this->config['kb_search_type'] . '.' . $this->php_ext);
				$class = '\Sheer\knowlegebase\search\\' . $this->config['kb_search_type'] . '';
				if (class_exists($class))
				{
					$error = false;
					$kb_search = new $class($error, $this->phpbb_root_path, $this->php_ext, $this->auth, $this->config, $this->db, $this->user);
				}
			}
		}

		$error = array();
		$submit		= (isset($_POST['submit']))   ? true : false;
		$preview	= (isset($_POST['preview'])) ? true : false;
		$cancel		= (isset($_POST['cancel']))  ? true : false;

		$bbcode_status	= true;
		$smilies_status	= true;
		$img_status 	= true;
		$url_status		= true;
		$flash_status	= true;

		$allowed_bbcode = $allowed_smilies = $allowed_urls = true;

		$article_title			= $this->request->variable('subject', '', true);
		$article_text			= $this->request->variable('message', '', true);
		$article_description	= $this->request->variable('descr', '', true);

		if ($row = $this->kb->get_cat_info($cat_id))
		{
			$articles_count	= $row['number_articles'];
			$category_name	= $row['category_name'];
		}
		else
		{
			trigger_error( $user->lang['CAT_NO_EXISTS']);
		}

		include($this->phpbb_root_path . 'includes/functions_posting.' . $this->php_ext);
		generate_smilies('inline', 0);
		include($this->phpbb_root_path . 'includes/functions_display.' . $this->php_ext);
		display_custom_bbcodes();

		if ($submit)
		{
			if ($article_title && $article_text && $article_description)
			{
				/* to enable bbcode, urls and smilies parsing, be enable it when using
				generate_text_for_stoarge function */
				generate_text_for_storage($article_text, $bbcode_uid, $bbcode_bitfield, $options, true, true, true);
				if (!$bbcode_bitfield)
				{
					$bbcode_bitfield = 'QA==';
				}

				$sql_data = array(
					'article_category_id'	=> $cat_id,
					'article_title'			=> $article_title,
					'article_description'	=> $article_description,
					'article_date'			=> time(),
					'author_id'				=> $this->user->data['user_id'],
					'bbcode_uid'			=> $bbcode_uid,
					'bbcode_bitfield'		=> $bbcode_bitfield,
					'article_body'			=> $article_text,
					'views'					=> 0,
					'author'				=> $this->user->data['username'],
					'approved'				=> ($this->auth->acl_get('a_manage_kb') || $this->kb->acl_kb_get($cat_id, 'kb_u_add_noapprove')) ? 1 : 0,
				);

				$sql = 'INSERT INTO ' . ARTICLES_TABLE . '
					' . $this->db->sql_build_array('INSERT', $sql_data);
				$this->db->sql_query($sql);
				$new = $this->db->sql_nextid();

				$articles_count++;
				$sql = 'UPDATE '. KB_CAT_TABLE .'
					SET number_articles = '. $articles_count .'
					WHERE category_id = '.$cat_id.'';
				$this->db->sql_query($sql);

				$root = append_sid("{$this->phpbb_root_path}knowlegebase/category",'id='. $cat_id .'');
				$redirect = append_sid("{$this->phpbb_root_path}knowlegebase/article",'k=' . $new .' ');

				if ($this->auth->acl_get('a_manage_kb') || $this->auth->acl_get('kb_u_add_noapprove'))
				{
					if (isset($kb_search))
					{
						// Add search index
						$kb_search->index('add', $new, $article_text, $article_title, $this->user->data['user_id']);
					}

					if (!empty($kb_data['forum_id']) && $kb_data['anounce'])
					{
						$this->kb->submit_article($cat_id, $fid, $article_title, $article_description, $category_name, $new);
					}

					$msg = $this->user->lang['ARTICLE_SUBMITTED'];
					$msg .= '<br /><br />' . sprintf($this->user->lang['RETURN_ARTICLE'], '<a href="' . $redirect . '">', '</a>');
				}
				else
				{
					$msg = $this->user->lang['ARTICLE_NEED_APPROVE'];
					// Add notification
					$sql_data['article_id'] = $new;
					$this->helper->add_notification($sql_data, 'sheer.knowlegebase.notification.type.need_approval');
				}

				$this->phpbb_cache->destroy('sql', KB_CAT_TABLE);
				$this->phpbb_cache->destroy('sql', ARTICLES_TABLE);

				$msg .= '<br /><br />' . sprintf($this->user->lang['RETURN_CAT'], '<a href="' . $root . '">', '</a>');
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_LIBRARY_ADD_ARTICLE', time(), array($article_title, $category_name));

				meta_refresh(3, $redirect);
				trigger_error($msg);
			}
			else
			{
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
			}
		}
		if ($cancel)
		{
			redirect(append_sid("{$this->phpbb_root_path}knowlegebase/category",'id=' . $cat_id . ''));
		}

		if ($preview)
		{
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

			$uid = $bitfield = $options = '';
			$preview_text = $article_text;
			generate_text_for_storage($preview_text, $uid, $bitfield, $options, true, true, true);
			$preview_text = generate_text_for_display($preview_text, $uid, $bitfield, $options);

			$this->template->assign_vars(array(
				'PREVIEW_MESSAGE'	=> $preview_text,
				'PREVIEW_SUBJECT'	=> $article_title,
				)
			);
		}

		$this->template->assign_vars(array(
			'L_POST_A'				=> $this->user->lang['ADD_ARTICLE'],
			'CATEGORY_NAME'			=> $category_name,
			'DESCR'					=> $article_description,
			'TOPIC_TITLE'			=> $article_title,
			'SUBJECT'				=> $article_title,
			'MESSAGE'				=> $article_text,
			'ERROR'					=> (sizeof($error)) ? implode('<br />', $error) : '',
			'S_DISPLAY_PREVIEW'		=> (!sizeof($error) && $preview) ? true : false,
			'POST_DATE'				=> $this->user->format_date(time()),
			'PREVIEW_SUBJECT'		=> (isset($preview_title)) ? $preview_title : '',
			'PREVIEW_MESSAGE'		=> (isset($preview_text)) ? $preview_text : '',
			'S_BBCODE_ALLOWED'		=> ($bbcode_status) ? 1 : 0,
			'BBCODE_STATUS'			=> ($bbcode_status) ? sprintf($this->user->lang['BBCODE_IS_ON'], '<a href="' . append_sid("{$this->phpbb_root_path}faq.$this->php_ext", 'mode=bbcode') . '">', '</a>') : sprintf($this->user->lang['BBCODE_IS_OFF'], '<a href="' . append_sid("{$this->phpbb_root_path}faq.$this->php_ext", 'mode=bbcode') . '">', '</a>'),
			'IMG_STATUS'			=> ($img_status) ? $this->user->lang['IMAGES_ARE_ON'] : $this->user->lang['IMAGES_ARE_OFF'],
			'FLASH_STATUS'			=> ($flash_status) ? $this->user->lang['FLASH_IS_ON'] : $this->user->lang['FLASH_IS_OFF'],
			'SMILIES_STATUS'		=> ($smilies_status) ? $this->user->lang['SMILIES_ARE_ON'] : $this->user->lang['SMILIES_ARE_OFF'],
			'URL_STATUS'			=> ($bbcode_status && $url_status) ? $this->user->lang['URL_IS_ON'] : $this->user->lang['URL_IS_OFF'],
			'S_LINKS_ALLOWED'		=> $url_status,

			'S_BBCODE_IMG'			=> $img_status,
			'S_BBCODE_URL'			=> $url_status,
			'S_BBCODE_FLASH'		=> $flash_status,
			'S_BBCODE_QUOTE'		=> true,

			'U_KB'					=> append_sid("{$this->phpbb_root_path}knowlegebase/"),
			'S_POST_ACTION'			=> append_sid("{$this->phpbb_root_path}knowlegebase/post",'id=' . $cat_id .' '),
			)
		);

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $this->user->lang['LIBRARY'],
			'U_VIEW_FORUM'	=> append_sid("{$this->phpbb_root_path}knowlegebase/"),
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

		page_header(''. $this->user->lang('LIBRARY'). ' &raquo; ' . $this->user->lang('ADD_ARTICLE') . '');
		$this->template->set_filenames(array(
			'body' => 'kb_post_body.html'));

		page_footer();
		return new Response($this->template->return_display('body'), 200);
	}
}

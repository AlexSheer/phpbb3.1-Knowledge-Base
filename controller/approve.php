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

class approve
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

	public function __construct(
			\phpbb\config\config $config,
			\phpbb\request\request_interface $request,
			\phpbb\db\driver\driver_interface $db,
			\phpbb\auth\auth $auth,
			\phpbb\template\template $template,
			\phpbb\user $user,
			\phpbb\cache\service $cache,
			\phpbb\log\log_interface $log,
			$phpbb_root_path,
			$php_ext,
			$table_prefix,
			\Sheer\knowlegebase\inc\functions_kb $kb,
			$helper
		)
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
		$this->helper = $helper;
	}

	public function approve_article()
	{
		// If not logged in
		$dd = $this->user->data;
		if ($this->user->data['user_id'] == ANONYMOUS)
		{
			$mode = '';
			login_box('', ((isset($this->user->lang['LOGIN_EXPLAIN_' . strtoupper($mode)])) ? $this->user->lang['LOGIN_EXPLAIN_' . strtoupper($mode)] : $this->user->lang['LOGIN_EXPLAIN_APPROVE']));
		}

		$art_id = $this->request->variable('id', 0);
		$approve = $this->request->variable('approve', false);
		$disapprove = $this->request->variable('disapprove', false);

		$kb_article_info = $this->kb->get_kb_article_info($art_id);
		$kb_category_info = $this->kb->get_cat_info($kb_article_info['article_category_id']);
		$category_name = $kb_category_info['category_name'];

		$redirect = append_sid("{$this->phpbb_root_path}knowlegebase/category",'id='. $kb_article_info['article_category_id'] .'');

		if (!$this->kb->acl_kb_get($kb_article_info['article_category_id'], 'kb_m_approve') && !$this->auth->acl_get('a_manage_kb'))
		{
			trigger_error('RULES_KB_APPROVE_MOD_CANNOT');
		}

		if($kb_article_info['approved'])
		{
			trigger_error('NO_NEED_APPROVE');
		}

		if($approve)
		{
			include_once($this->phpbb_root_path . 'includes/functions_posting.' . $this->php_ext);
			$kb_data = $this->kb->obtain_kb_config();

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

			$sql = 'UPDATE ' . ARTICLES_TABLE . '
				SET approved = 1
				WHERE article_id = ' .$art_id;
			$this->db->sql_query($sql);

			if (isset($kb_search))
			{
				// Add search index
				$this->phpbb_cache->purge();
				$kb_search->index('add', $art_id, $kb_article_info['article_body'], $kb_article_info['article_title'], $kb_article_info['author']);
			}

			if (!empty($kb_data['forum_id']) && $kb_data['anounce'])
			{
				$this->kb->submit_article($kb_article_info['article_category_id'], $kb_data['forum_id'], $kb_article_info['article_title'], $kb_article_info['article_description'], $category_name, $art_id);
			}
		}
		else if ($disapprove)
		{
			$sql = 'DELETE
				FROM ' . ARTICLES_TABLE . '
				WHERE article_id = '. $art_id;
			$this->db->sql_query($sql);
		}

		if ($approve || $disapprove)
		{
			// add log
			$log_type = ($approve) ? 'LOG_LIBRARY_APPROVED_ARTICLE' : 'LOG_LIBRARY_REJECTED_ARTICLE';
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], $log_type, time(), array($kb_article_info['article_title'], $kb_category_info['category_name'], $kb_article_info['author']));
			// Send notification
			$message = ($approve) ? 'ARTICLE_APPROVED_SUCESS' : 'ARTICLE_DISAPPROVED_SUCESS';
			$kb_article_info['moderator_id'] = $this->user->data['user_id'];
			$notification_type = ($approve) ? 'sheer.knowlegebase.notification.type.approve' : 'sheer.knowlegebase.notification.type.disapprove';
			$this->helper->add_notification($kb_article_info, $notification_type);
			$sql = 'SELECT notification_type_id
				FROM ' . NOTIFICATION_TYPES_TABLE . '
				WHERE notification_type_name
				LIKE \'sheer.knowlegebase.notification.type.need_approval\'';
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if ($row['notification_type_id'])
			{
				$sql = 'DELETE FROM ' . NOTIFICATIONS_TABLE . '
					WHERE item_id = ' . $kb_article_info['article_id'] . '
					AND notification_type_id = ' . $row['notification_type_id'] .' ';
				$this->db->sql_query($sql);
			}
			meta_refresh(3, $redirect);
			trigger_error($message);
		}

		$this->template->assign_vars(array(
			'ARTICLE_AUTHOR'		=> $kb_article_info['author'],
			'ARTICLE_DESCRIPTION' 	=> $kb_article_info['article_description'],
			'ARTICLE_DATE'			=> $this->user->format_date($kb_article_info['article_date']),
			'ARTICLE_TITLE'			=> $kb_article_info['article_title'],
			'S_ACTION'				=> append_sid("{$this->phpbb_root_path}knowlegebase/approve",'id='. $art_id .''),
			)
		);

		page_header(''. $this->user->lang('LIBRARY'). ' &raquo; ' . $this->user->lang('APPROVE_ARTICLE') . '');
		$this->template->set_filenames(array(
			'body' => 'kb_approve_body.html'));

		page_footer();
		return new Response($this->template->return_display('body'), 200);
	}
}

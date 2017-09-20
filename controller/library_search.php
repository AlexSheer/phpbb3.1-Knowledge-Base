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

class library_search
{
	protected $config;
	protected $request;
	protected $db;
	protected $auth;
	protected $template;
	protected $user;
	protected $phpbb_root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\user $user, \phpbb\cache\service $cache, \phpbb\pagination $pagination, $phpbb_root_path, $php_ext, $table_prefix, \sheer\knowlegebase\inc\functions_kb $kb)
	{
		$this->config = $config;
		$this->request = $request;
		$this->db = $db;
		$this->auth = $auth;
		$this->template = $template;
		$this->user = $user;
		$this->phpbb_cache = $cache;
		$this->pagination = $pagination;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->table_prefix = $table_prefix;
		$this->kb = $kb;
	}

	public function main()
	{
		include($this->phpbb_root_path . 'includes/bbcode.' . $this->php_ext);
		$this->user->add_lang(array('search'));

		if (!$this->config['kb_search'])
		{
			trigger_error('SEARCH_DISABLED');
		}

		// Is user able to search? Has search been disabled?
		if (!$this->auth->acl_get('u_search') || !$this->config['load_search'])
		{
			$this->template->assign_var('S_NO_SEARCH', true);
			trigger_error('NO_SEARCH');
		}

		// Check search load limit
		if ($this->user->load && $this->config['limit_search_load'] && ($this->user->load > doubleval($this->config['limit_search_load'])))
		{
			$this->template->assign_var('S_NO_SEARCH', true);
			trigger_error('NO_SEARCH_TIME');
		}

		$keywords 		= $this->request->variable('keywords', '', true);
		$author 		= $this->request->variable('author', '', true);
		$terms 			= $this->request->variable('terms', 'all');
		$sf 			= $this->request->variable('sf', '');
		$return_chars	= $this->request->variable('ch', 300);
		$start 			= $this->request->variable('start', 0);
		$submit 		= $this->request->variable('submit', false);
		$sort_days		= $this->request->variable('st', 0);
		$sort_key		= $this->request->variable('sk', 't');
		$sort_dir		= $this->request->variable('sd', 'd');
		$show_results	= $this->request->variable('show', 'posts');
		$category_id	= $this->request->variable('cid', 0);
		$categories		= $this->request->variable('cat_ids', array(0));

		$cat = '';
		$search_terms = 'all';
		$cat_ary = $ex_fid_ary = array();

		if(!empty($categories))
		{
			$sql = 'SELECT category_id
				FROM '. KB_CAT_TABLE;
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$cat_ary[] = $row['category_id'];
			}

			foreach($cat_ary as $key => $value)
			{
				if (!in_array($value, $categories))
				{
					$ex_fid_ary[] = $value;
				}
			}
		}

		$per_page = ($this->config['kb_per_page_search']) ? $this->config['kb_per_page_search'] : 5;

		$sort_by_sql = $id_ary = $author_ary = array();
		// Define some vars
		$limit_days		= array(0 => $this->user->lang['ALL_RESULTS'], 1 => $this->user->lang['1_DAY'], 7 => $this->user->lang['7_DAYS'], 14 => $this->user->lang['2_WEEKS'], 30 => $this->user->lang['1_MONTH'], 90 => $this->user->lang['3_MONTHS'], 180 => $this->user->lang['6_MONTHS'], 365 => $this->user->lang['1_YEAR']);
		$sort_by_text	= array('t' => $this->user->lang['SORT_TIME'], 'a' => $this->user->lang['SORT_AUTHOR'], 'c' => $this->user->lang['CATEGORY'], 's' => $this->user->lang['SORT_ARTICLE_TITLE']);

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);
		// define some variables needed for retrieving post_id/topic_id information
		$sort_by_sql = array('a' => 'author', 't' => (($show_results == 'posts') ? 'article_date' : 'topic_last_post_time'), 'c' => 'article_category_id', 's' => (($show_results == 'posts') ? 'article_title' : 'category_name'));

		$sql_sort = $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');
		$author_id_ary=array();
		$total_matches = 0;
		$type = 'posts';

		$error = array();

		if (empty($keywords) && empty($author) && $submit)
		{
			$error[] = $this->user->lang['EMPTY_QUERY'];
		}

		$u_show_results = '&amp;sr=' . $show_results;
		$search_url = append_sid("{$this->phpbb_root_path}knowlegebase/library_search", $u_sort_param . $u_show_results);
		$search_url .= ($search_terms != 'all') ? '&amp;terms=' . $search_terms : '';
		$search_url .= ($category_id) ? '&amp;cid=' . $category_id : '';
		$search_url .= ($author) ? '&amp;author=' . urlencode(htmlspecialchars_decode($author)) : '';
		$search_url .= ($return_chars != 300) ? '&amp;ch=' . $return_chars : '';
		$search_url .= ($keywords) ? '&amp;keywords=' . $keywords : '';
		if(sizeof($categories))
		{
			foreach($categories as $key => $value)
			{
				$cat .= '&amp;cat_ids[]='.$value.'';
			}
		}
		$search_url .= ($cat) ? $cat : '';
		$hilit = implode('|', explode(' ', preg_replace('#\s+#u', ' ', str_replace(array('+', '-', '|', '(', ')', '&quot;'), ' ', $keywords))));
		// Do not allow *only* wildcard being used for hilight
		$hilit = (strspn($hilit, '*') === strlen($hilit)) ? '' : $hilit;
		if ($hilit)
		{
			// Remove bad highlights
			$hilit_array = array_filter(explode('|', $hilit), 'strlen');
			foreach ($hilit_array as $key => $value)
			{
				$hilit_array[$key] = str_replace('\*', '\w*?', preg_quote($value, '#'));
				$hilit_array[$key] = preg_replace('#(^|\s)\\\\w\*\?(\s|$)#', '$1\w+?$2', $hilit_array[$key]);
			}
			$hilit = implode('|', $hilit_array);
		}

		if (($keywords && $keywords != $this->user->lang['SEARCH_MINI']) || $author)
		{
			$kb_search = $this->kb->setup_kb_search();
			$highlight_match = $highlight = '';
			$matches = array('(', ')', '|', '+', '-');
			$highlight_words = str_replace($matches, ' ', $keywords);
			foreach (explode(' ', trim($highlight_words)) as $word)
			{
				if (trim($word))
				{
					$highlight_match .= (($highlight_match != '') ? '|' : '') . str_replace('*', '\w*?', preg_quote($word, '#'));
				}
			}
			$highlight = urlencode($highlight_words);

			if ($author)
			{
				$author_id = $this->kb->get_id_by_username($author);
				if ($author_id)
				{
					$author_id_ary[] = $author_id;
				}
			}

			if ($author && $keywords)
			{
				$kb_search->split_keywords($keywords, $terms);
				$search_result = $kb_search->keyword_search($type, $sf, 'all', $sort_by_sql, $sort_key, $sort_dir, $sort_days, $ex_fid_ary, $category_id, $author_id_ary, $author, $id_ary, $start, $per_page);
			}
			else if ($author)
			{
				$search_result = $kb_search->author_search($type, $sort_by_sql, $sort_key, $sort_dir, $sort_days, $ex_fid_ary, $category_id, $author_id_ary, $author, $id_ary, $start, $per_page);
			}
			else
			{
				$kb_search->split_keywords($keywords, $terms);
				$search_result = $kb_search->keyword_search($type, $sf, 'all', $sort_by_sql, $sort_key, $sort_dir, $sort_days, $ex_fid_ary, $category_id, $author_id_ary, $author, $id_ary, $start, $per_page);
			}

			$total_matches = $search_result['total_matches'];
			$start = $search_result['start'];
			$id_ary = $search_result['id_ary'];

			if ($total_matches)
			{
				$sql = 'SELECT DISTINCT a.*, u.user_id, u.username, u.user_colour
					FROM ' . ARTICLES_TABLE . ' a, ' . USERS_TABLE . ' u
					WHERE ' . $this->db->sql_in_set('article_id', $id_ary).'
						AND (a.author_id = u.user_id)
						AND a.approved = 1';
				if ($author && $keywords)
				{
					$sql .= ' AND author_id = '.$author_id.'';
				}
				$sql .= ' ORDER BY '.$sql_sort.'';
				$result = $this->db->sql_query($sql);
				while ($row = $this->db->sql_fetchrow($result))
				{
					$article_id = $row['article_id'];
					$article_info = $this->kb->get_kb_article_info ($article_id);
					$category_id = $article_info['article_category_id'];
					$message = $row['article_body'];
					$message = generate_text_for_display($message, $row['bbcode_uid'], $row['bbcode_bitfield'], 3, true);
					$message = get_context($message, array_filter(explode('|', $hilit), 'strlen'), $return_chars);
					$message =  strtr($message, array('&lt;' => '<', '&gt;' => '>'));

					if ($hilit)
					{
						$message = preg_replace('#(?!<.*)(?<!\w)(' . $hilit . ')(?!\w|[^<>]*(?:</s(?:cript|tyle))?>)#is', '<span class="posthilit">\1</span>', $message);
					}

					$category = $this->kb->get_cat_info($row['article_category_id']);

					$this->template->assign_block_vars('searchrow', array(
						'MESSAGE'	=> $message,
						'DATE'		=> $this->user->format_date($row['article_date']),
						'TITLE'		=> $row['article_title'],
						'U_VIEW'	=> append_sid("{$this->phpbb_root_path}knowlegebase/article", "k=$article_id"),
						'CATEGORY'	=> $category['category_name'],
						'U_CAT'		=> append_sid("{$this->phpbb_root_path}knowlegebase/category", "id=$category_id"),
						'USER_FULL'	=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'ID'		=> $article_id,
						)
					);
				}

				if ($total_matches)
				{
					$this->pagination->generate_template_pagination($search_url, 'pagination', 'start', $total_matches, $per_page, $start);
				}

				$this->template->assign_vars(array(
					'TOTAL_ITEMS'		=> $this->user->lang('TOTAL_ITEMS', (int) $total_matches),
					'PAGE_NUMBER'		=> $this->pagination->on_page($total_matches, $per_page, $start),
					'TOTAL_MATCHES'		=> $total_matches,
					'SEARCH_MATCHES'	=> ($total_matches == 1) ? sprintf($this->user->lang['FOUND_KB_SEARCH_MATCH'], $total_matches) : sprintf($this->user->lang['FOUND_KB_SEARCH_MATCHES'], $total_matches),
					'U_SEARCH_WORDS'	=> $search_url,
					'SEARCH_WORDS'		=> $author . ' &bull; ' . $keywords,
				));
			}
		}

		page_header($this->user->lang('LIBRARY'));

		if (($keywords && $keywords != $this->user->lang['SEARCH_MINI']) || $author)
		{
			$this->template->set_filenames(array(
				'body' => 'kb_search_results.html'));
		}
		else
		{
			$this->template->set_filenames(array(
				'body' => 'kb_search_body.html'));
		}

		$this->template->assign_vars(array(
			'S_SELECT_SORT_DAYS'	=> $s_limit_days,
			'S_SELECT_SORT_KEY'		=> $s_sort_key,
			'S_SELECT_SORT_DIR'		=> $s_sort_dir,
			'S_SEARCH_ACTION'		=> $search_url,
			'U_KB_SEARCH'			=> append_sid("{$this->phpbb_root_path}knowlegebase/library_search"),
			'ERROR'					=> (sizeof($error)) ? implode('<br />', $error) : '',
			'CATS_BOX'				=> $this->kb->make_category_select(0, false, false, false, false),
			)
		);

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $this->user->lang['LIBRARY'],
			'U_VIEW_FORUM'	=> append_sid("{$this->phpbb_root_path}knowlegebase"),
			)
		);

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $this->user->lang['SEARCH'],
			'U_VIEW_FORUM'	=> append_sid("{$this->phpbb_root_path}knowlegebase/library_search"),
			)
		);

		page_footer();
		return new Response($this->template->return_display('body'), 200);
	}
}

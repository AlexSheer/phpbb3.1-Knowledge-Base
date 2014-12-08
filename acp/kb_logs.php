<?php
/**
*
* @package phpBB Extension - Knowlege Base
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace Sheer\knowlegebase\acp;

class kb_logs
{
	var $u_action;

	function main($id, $mode)
	{
		global $db, $template, $request, $table_prefix, $user, $phpbb_log, $phpbb_container, $config;

		$user->add_lang('mcp');
		define ('KB_LOG_TABLE', $table_prefix.'kb_log');
		$phpbb_log->set_log_table(KB_LOG_TABLE);

		$start		= $request->variable('start', 0);
		$deletemark	= $request->variable('delmarked', false, false, \phpbb\request\request_interface::POST);
		$deleteall	= $request->variable('delall', false, false, \phpbb\request\request_interface::POST);
		$marked		= $request->variable('mark', array(0));

		// Sort keys
		$sort_days	= $request->variable('st', 0);
		$sort_key	= $request->variable('sk', 't');
		$sort_dir	= $request->variable('sd', 'd');

		$pagination = $phpbb_container->get('pagination');

		// Delete entries if requested and able
		if (($deletemark || $deleteall))
		{
			if (confirm_box(true))
			{
				$conditions = array();

				if ($deletemark && sizeof($marked))
				{
					$sql = 'DELETE FROM ' . KB_LOG_TABLE . '
						WHERE ' . $db->sql_in_set('log_id', $marked) . '';
				}

				if ($deleteall)
				{
					$sql = 'DELETE FROM ' . KB_LOG_TABLE;
				}

				$db->sql_query($sql);
				$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CLEAR_KB', time());
			}
			else
			{
				confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
					'start'		=> $start,
					'delmarked'	=> $deletemark,
					'delall'	=> $deleteall,
					'mark'		=> $marked,
					'st'		=> $sort_days,
					'sk'		=> $sort_key,
					'sd'		=> $sort_dir,
					'i'			=> $id,
					'mode'		=> $mode,
					))
				);
			}
		}

		// Sorting
		$limit_days = array(0 => $user->lang['ALL_ENTRIES'], 1 => $user->lang['1_DAY'], 7 => $user->lang['7_DAYS'], 14 => $user->lang['2_WEEKS'], 30 => $user->lang['1_MONTH'], 90 => $user->lang['3_MONTHS'], 180 => $user->lang['6_MONTHS'], 365 => $user->lang['1_YEAR']);
		$sort_by_text = array('u' => $user->lang['SORT_USERNAME'], 't' => $user->lang['SORT_DATE'], 'i' => $user->lang['SORT_IP'], 'o' => $user->lang['SORT_ACTION']);
		$sort_by_sql = array('u' => 'u.username_clean', 't' => 'l.log_time', 'i' => 'l.log_ip', 'o' => 'l.log_operation');

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

		// Define where and sort sql for use in displaying logs
		$sql_where = ($sort_days) ? (time() - ($sort_days * 86400)) : 0;
		$sql_sort = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');

		$this->tpl_name = 'acp_knowlegebase_logs';
		$this->page_title = $user->lang('ACP_LIBRARY_LOGS');

		$log_data = array();
		$log_count = 0;
		$start = view_log('admin', $log_data, $log_count, $config['topics_per_page'], $start, 0, 0, 0, $sql_where, $sql_sort);

		$base_url = $this->u_action . "&amp;$u_sort_param";
		$pagination->generate_template_pagination($base_url, 'pagination', 'start', $log_count, $config['topics_per_page'], $start);

		foreach ($log_data as $row)
		{
			$template->assign_block_vars('log', array(
				'USERNAME'			=> $row['username_full'],
				'IP'				=> $row['ip'],
				'DATE'				=> $user->format_date($row['time']),
				'ACTION'			=> $row['action'],
				'ID'				=> $row['id'],
				)
			);
		}

		$template->assign_vars(array(
				'U_ACTION'		=> $this->u_action . "&amp;start=$start",

				'S_LIMIT_DAYS'	=> $s_limit_days,
				'S_SORT_KEY'	=> $s_sort_key,
				'S_SORT_DIR'	=> $s_sort_dir,
			)
		);
	}
}

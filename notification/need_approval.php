<?php
/**
*
* need_approval For Knowlege Base extension for the phpBB Forum Software package.
*
* @copyright (c) 2013 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace Sheer\knowlegebase\notification;

/**

*/

class need_approval extends \phpbb\notification\type\base
{
	/**
	* Get notification type name
	*
	* @return string
	*/
	public function get_type()
	{
		return 'sheer.knowlegebase.notification.type.need_approval';
	}

	/**
	* Language key used to output the text
	*
	* @var string
	*/
	protected $language_key = 'NOTIFICATION_NEED_APPROVAL';

	/**
	* Notification option data (for outputting to the user)
	*
	* @var bool|array False if the service should use it's default data
	* 					Array of data (including keys 'id', 'lang', and 'group')
	*/
	public static $notification_option = array(
		'lang'	=> 'NOTIFICATION_TYPE_NEED_APPROVAL',
		'group'	=> 'NOTIFICATION_GROUP_MODERATION',
	);

	/**
	* Permission to check for (in find_users_for_notification)
	*
	* @var string Permission name
	*/
	protected $permission = 'a_manage_kb';

	/**
	* Is available
	*/
	public function is_available()
	{
		$auth_approve = $this->auth->acl_get_list(false, $this->permission);
		$has_permission = $this->check_permisson(0, 'kb_m_approve');
		$users = array_merge($has_permission, $auth_approve[0]['a_manage_kb']);
		$users = array_unique($users);

		return ((in_array($this->user->data['user_id'], $users)));
	}

	/**
	* Get the id of the item
	*
	* @param array $need_approval_data
	*/
	public static function get_item_id($need_approval_data)
	{
		return (int) $need_approval_data['article_id'];
	}

	/**
	* Get the id of the parent
	*
	* @param array $need_approval_data
	*/
	public static function get_item_parent_id($need_approval_data)
	{
		return (int) $need_approval_data['article_category_id'];
	}

	/**
	* Find the users who want to receive notifications
	*
	* @param array $need_approval_data
	* @param array $options Options for finding users for notification
	*
	* @return array
	*/
	public function find_users_for_notification($need_approval_data, $options = array())
	{
		$options = array_merge(array(
			'ignore_users'		=> array(),
		), $options);

		$auth = 'kb_m_approve';

		$auth_approve = $this->auth->acl_get_list(false, $this->permission);
		$has_permission = $this->check_permisson($auth, $need_approval_data['article_category_id']);
		$users = array_merge($auth_approve[0]['a_manage_kb'], $has_permission);
		$users = array_unique($users);
		$usr = $this->check_user_notification_options($users, $options);
		return $this->check_user_notification_options($users, $options);
	}

	/**
	* Get the user's avatar
	*/
	public function get_avatar()
	{
		return $this->user_loader->get_avatar($this->get_data('author_id'));
	}

	/**
	* Get the HTML formatted title of this notification
	*
	* @return string
	*/
	public function get_title()
	{
		$username = $this->user_loader->get_username($this->get_data('author_id'), 'no_profile');
		return $this->user->lang('NOTIFICATION_NEED_APPROVAL', $username);
	}

	/**
	* Users needed to query before this notification can be displayed
	*
	* @return array Array of user_ids
	*/
	public function users_to_query()
	{
		return array($this->get_data('author_id'));
	}

	/**
	* Get the url to this item
	*
	* @return string URL
	*/
	public function get_url()
	{
		return append_sid($this->phpbb_root_path . 'knowlegebase/article?k=' . $this->item_id . '');
	}

	/**
	* {inheritDoc}
	*/
	public function get_redirect_url()
	{
		return $this->get_url();
	}

	/**
	* Get email template
	*
	* @return string|bool
	*/
	public function get_email_template()
	{
		return '@Sheer_knowlegebase/user_need_approval';
	}

	/**
	* Get the HTML formatted reference of the notification
	*
	* @return string
	*/
	public function get_reference()
	{
		return $this->user->lang(
			'NOTIFICATION_REFERENCE',
			censor_text($this->get_data('article_title'))
		);
	}

	/**
	* Trim the user array passed down to 3 users if the array contains
	* more than 4 users.
	*
	* @param array $users Array of users
	* @return array Trimmed array of user_ids
	*/
	public function trim_user_ary($users)
	{
		if (sizeof($users) > 4)
		{
			array_splice($users, 3);
		}
		return $users;
	}

	/**
	* Get email template variables
	*
	* @return array
	*/
	public function get_email_template_variables()
	{
		$username = $this->user_loader->get_username($this->get_data('author_id'), 'username');
		return array(
				//'USERNAME'			=> htmlspecialchars_decode($this->user->data['username']),
				'ARTICLE_TITLE'		=> htmlspecialchars_decode(censor_text($this->get_data('article_title'))),
				'POSTER_NAME'		=> htmlspecialchars_decode($username),
				'U_VIEW_ARTICLE'	=> generate_board_url() . '/knowlegebase/article?k=' . $this->item_id . '',
		);

		return array();
	}

	/**
	* Function for preparing the data for insertion in an SQL query
	* (The service handles insertion)
	*
	* @param array $need_approval_data Data from insert_need_approval
	* @param array $pre_create_data Data from pre_create_insert_array()
	*
	* @return array Array of data ready to be inserted into the database
	*/
	public function create_insert_array($need_approval_data, $pre_create_data = array())
	{
		$this->set_data('author_id', $need_approval_data['author_id']);
		$this->set_data('article_title', $need_approval_data['article_title']);
		return parent::create_insert_array($need_approval_data, $pre_create_data);
	}

	public function check_permisson($auth, $category_id = 0)
	{
		global $table_prefix;

		if (!defined ('KB_OPTIONS_TABLE')) define ('KB_OPTIONS_TABLE', $table_prefix.'kb_options');
		if (!defined ('KB_GROUPS_TABLE')) define ('KB_GROUPS_TABLE', $table_prefix.'kb_groups');
		if (!defined ('KB_USERS_TABLE')) define ('KB_USERS_TABLE', $table_prefix.'kb_users');

		$sql_where = ($category_id) ? ' AND category_id = ' . $category_id . '' : '';

		$moderators = $groups = $exclude = array();

		$sql = 'SELECT auth_option_id
			FROM ' . KB_OPTIONS_TABLE . '
			WHERE auth_option LIKE \'' . $auth . '\'
			AND is_local = 1';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$auth_option_id = $row['auth_option_id'];
		$this->db->sql_freeresult($result);

		$sql = 'SELECT user_id FROM ' . KB_USERS_TABLE . '
			WHERE auth_option_id = ' . $auth_option_id . '
				AND auth_setting = 1
				' . $sql_where . '';
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			$moderators[] = $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT group_id
			FROM ' . KB_GROUPS_TABLE . '
			WHERE auth_option_id = ' . $auth_option_id . '
				AND auth_setting = 1
				' . $sql_where . '';
		$result = $this->db->sql_query($sql);

		while($group_row = $this->db->sql_fetchrow($result))
		{
			$groups[] = $group_row['group_id'];
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT user_id
			FROM ' . KB_USERS_TABLE . '
			WHERE auth_setting = 0
				' . $sql_where . '';
		$result = $this->db->sql_query($sql);
		while($row = $this->db->sql_fetchrow($result))
		{
			$exclude[] = $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		if(sizeof($groups))
		{
			$sql = 'SELECT  user_id
				FROM ' . USERS_TABLE . '
				WHERE group_id IN('.implode(',', $groups).')';
			$result = $this->db->sql_query($sql);
			while($row = $this->db->sql_fetchrow($result))
			{
				if (!in_array($row['user_id'], $exclude))
				{
					$moderators[] = $row['user_id'];
				}
			}
			$this->db->sql_freeresult($result);
		}
		$moderators = array_unique($moderators);

		return $moderators;
	}
}

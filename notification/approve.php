<?php
/**
*
* approve For Knowlege Base extension for the phpBB Forum Software package.
*
* @copyright (c) 2013 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace Sheer\knowlegebase\notification;

/**

*/

class approve extends \phpbb\notification\type\base
{
	/**
	* Get notification type name
	*
	* @return string
	*/
	public function get_type()
	{
		return 'sheer.knowlegebase.notification.type.approve';
	}

	/**
	* Language key used to output the text
	*
	* @var string
	*/
	protected $language_key = 'NOTIFICATION_ARTICLE_APPROVE';

	/**
	* Notification option data (for outputting to the user)
	*
	* @var bool|array False if the service should use it's default data
	* 					Array of data (including keys 'id', 'lang', and 'group')
	*/
	public static $notification_option = array(
		'lang'	=> 'NOTIFICATION_TYPE_ARTICLE_APPROVE',
		'group'	=> 'NOTIFICATION_GROUP_MISCELLANEOUS',
	);

	/**
	* Is available
	*/
	public function is_available()
	{
		return true;
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

		$users = array((int) $need_approval_data['author_id']);
		$usr = $this->check_user_notification_options($users, $options);
		return $this->check_user_notification_options($users, $options);
	}

	/**
	* Get the user's avatar
	*/
	public function get_avatar()
	{
		return $this->user_loader->get_avatar($this->get_data('moderator_id'));
	}

	/**
	* Get the HTML formatted title of this notification
	*
	* @return string
	*/
	public function get_title()
	{
		$username = $this->user_loader->get_username($this->get_data('moderator_id'), 'no_profile');
		return $this->user->lang('NOTIFICATION_ARTICLE_APPROVE', $username);
	}

	/**
	* Users needed to query before this notification can be displayed
	*
	* @return array Array of user_ids
	*/
	public function users_to_query()
	{
		return array($this->get_data('moderator_id'));
	}

	/**
	* Get the url to this item
	*
	* @return string URL
	*/
	public function get_url()
	{
		return append_sid($this->phpbb_root_path . 'knowlegebase/article?k='.$this->item_id.'');
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
		return '@Sheer_knowlegebase/article_approve';
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
				'USERNAME'			=> htmlspecialchars_decode($username),
				'MODERATOR'			=> htmlspecialchars_decode($this->user_loader->get_username($this->get_data('moderator_id'), 'username')),
				'ARTICLE_TITLE'		=> htmlspecialchars_decode(censor_text($this->get_data('article_title'))),
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
		$this->set_data('moderator_id', $need_approval_data['moderator_id']);
		$this->set_data('author_id', $need_approval_data['author_id']);
		$this->set_data('article_title', $need_approval_data['article_title']);
		return parent::create_insert_array($need_approval_data, $pre_create_data);
	}
}

<?php
/**
*
* knowlegebase [Russian]
*
* @package My test
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	'KNOWLEGE_BASE'							=> 'Knowlege Base',
	'ACP_LIBRARY_MANAGE'					=> 'Knowlege Base Management',
	'ACP_LIBRARY_SEARCH'					=> 'Search',

	'CATEGORYES'							=> 'Categoryes',
	'LIBRARY_EDIT_CAT'						=> 'Edit the category',
	'LIBRARY_EDIT_CAT_EXPLAIN'				=> 'Here you can rename the category, give it a brief description and move to another category (with the content).',
	'ACP_LIBRARY_PERMISSIONS_EXPLAIN'		=> 'Here you can change for each user and group access to each category of the library. To assign moderators or administrator rights to use the definition of the relevant page.',
	'ACP_LIBRARY_MANAGE_EXPLAIN'			=> 'Each category can have an unlimited number of subcategories. Here you can add, edit, move, search places and move from one category to another. If the number of entries in the category does not coincide with the real, you can synchronize the category.',
	'ADD_CATEGORY'							=> 'Create a new category',
	'ADD_CATEGORY_EXPLAIN'					=> 'Create a new category',
	'CATEGOTY_LIST'							=> 'Categories list',
	'CAT_PARENT'							=> 'Parent Category',
	'NO_PARENT'								=> 'No parent',
	'SELECT_CAT'							=> 'Select a category',
	'DEL_CATEGORY'							=> 'Delete category',
	'DEL_CATEGORY_EXPLAIN'					=> 'This form below allows you delete a category. You can decide where to move all the articles in it or subcategory.',
	'DELETE_ALL_ARTICLES'					=> 'Delete article',
	'MOVE_ARTICLES_TO'						=> 'Move article',
	'DELETE_SUBCATS'						=> 'Delete categories and articles',
	'MOVE_SUBCATS_TO'						=> 'Move subcategories',
	'SYNC_OK'								=> 'Category successfully synchronized.',
	'NO_DESTINATION_CATEGORY'				=> 'Category recipient could not be found',
	'KB_ROOT'								=> 'Root category',
	'NO_CATS_IN_KB'							=> 'Knowlege Base has no categories.',
	'ADD_CATEGORY'							=> 'Add category',

	'CATEGORY_ADDED'						=> 'Category successfully added. Now you can s%set permission%s to this category.',
	'CATEGORY_DELETED'						=> 'Category deleted successfully.',
	'CONFIRM_DEL_CAT'						=> 'Are you sure you want to delete this category?',
	'CAT_NAME'								=> 'Category name',
	'NO_CAT_NAME'							=> 'You have not specified category name.',
	'NO_CAT_DESCR'							=> 'You have not created category description.',
	'CAT_DESCR'								=> 'Category description',
	'EDIT_CATEGORY'							=> 'Edit category',
	'CATEGORY_EDITED'						=> 'Category successfully edited',

	'ACP_LIBRARY_ARTICLES'					=> 'Articles',
	'ARTICLE_MANAGE'						=> 'Manage Articles',
	'ARTICLE_MANAGE_EXPLAIN'				=> 'Here you can delete articles or move them to other categories, as well as view or edit them (in a separate window).',
	'NUM_ARTICLES'							=> 'Articles',
	'NO_ARTICLES_IN_KB'						=> 'The Knowlege Base currently no articles.',
	'EDIT_DATE'								=> 'Edited',

	'ACP_KNOWLEGE_BASE_CONFIGURE'			=> 'Configuration',
	'ACP_KNOWLEGE_EXPLAIN'					=> 'Here you can configure the extension.',
	'KB_CONFIG_EXPLAIN'						=> 'Here you can set the basic settings.',
	'KB_CONFIG_UPDATED'						=> 'Settings successfully updated.',
	'KB_FORUM_EXPLAIN'						=> 'Select a forum in which to set up announcements of articles.',
	'PER_PAGE'								=> 'The number of articles on the page',
	'PER_PAGE_EXPLAIN'						=> 'The number of articles on the management page articles and view page search.',
	'ARTICLE_MOVE_EXPLAIN'					=> 'Select category to which you want move article.',
	'ANOUNCE'								=> 'Announce article at the conference',
	'ANOUNCE_EXPLAIN'						=> 'If selected, after the addition of articles on the conference will be automatically created topic with a brief article description and link to this. <br />Choose forum, which will be created announcements, from the list below (will be available at activation options).',

	'SELECT_CATEGORY'						=> 'Select a category',
	'ALL_CATS'								=> 'All Categories',

	'ACP_LIBRARY_PERMISSIONS'				=> 'Permissions',
	'ACP_LIBRARY_PERMISSIONS_NO_CATS'		=> 'To set permissions, you must create at least one category.',
// User Permissions
	'kb_u_add'				=> 'Can add article',
	'kb_u_edit'				=> 'Can edit own articles',
	'kb_u_delete'			=> 'Can delete own articles',
	'kb_u_add_noapprove'	=> 'Can add articles without prior approval',
// Moderator Permissions
	'kb_m_edit'				=> 'Can edit article',
	'kb_m_delete'			=> 'Can delete articles',
	'kb_m_approve'			=> 'Can approve articles',

	'LOG_KB_CONFIG_SEARCH'					=> '<b>Knowlege Base Search settings changed</b>',
	'ACP_SEARCH_INDEX_EXPLAIN'				=> 'Here you can manage the search backend’s indexes. Since you normally use only one backend you should delete all indexes that you do not make use of. After altering some of the search settings (e.g. the number of minimum/maximum chars) it might be worth recreating the index so it reflects those changes.',
	'ACP_SEARCH_SETTINGS_EXPLAIN'			=> 'Here you can define what search backend will be used for indexing posts and performing searches. You can set various options that can influence how much processing these actions require. Some of these settings are the same for all search engine backends.',

	'CONFIRM_SEARCH_BACKEND'				=> 'Are you sure you wish to switch to a different search backend? After changing the search backend you will have to create an index for the new search backend. If you don’t plan on switching back to the old search backend you can also delete the old backend’s index in order to free system resources.',
	'CONTINUE_DELETING_INDEX'				=> 'Continue previous index removal process',
	'CONTINUE_DELETING_INDEX_EXPLAIN'		=> 'An index removal process has been started. In order to access the search index page you will have to complete it or cancel it.',
	'CONTINUE_INDEXING'						=> 'Continue previous indexing process',
	'CONTINUE_INDEXING_EXPLAIN'				=> 'An indexing process has been started. In order to access the search index page you will have to complete it or cancel it.',
	'CREATE_INDEX'							=> 'Create index',

	'DELETE_INDEX'							=> 'Delete index',
	'DELETING_INDEX_IN_PROGRESS'			=> 'Deleting the index in progress',
	'DELETING_INDEX_IN_PROGRESS_EXPLAIN'	=> 'The search backend is currently cleaning its index. This can take a few minutes.',

	'FULLTEXT_MYSQL_INCOMPATIBLE_DATABASE'	=> 'The MySQL fulltext backend can only be used with MySQL4 and above.',
	'FULLTEXT_MYSQL_NOT_SUPPORTED'			=> 'MySQL fulltext indexes can only be used with MyISAM or InnoDB tables. MySQL 5.6.4 or later is required for fulltext indexes on InnoDB tables.',
	'FULLTEXT_MYSQL_TOTAL_POSTS'			=> 'Total number of indexed posts',

	'FULLTEXT_POSTGRES_INCOMPATIBLE_DATABASE'	=> 'The PostgreSQL fulltext backend can only be used with PostgreSQL.',
	'FULLTEXT_POSTGRES_TOTAL_POSTS'				=> 'Total number of indexed posts',
	'FULLTEXT_POSTGRES_VERSION_CHECK'			=> 'PostgreSQL version',
	'FULLTEXT_POSTGRES_TS_NAME'					=> 'Text search Configuration Profile:',
	'FULLTEXT_POSTGRES_VERSION_CHECK_EXPLAIN'	=> 'This search backend requires PostgreSQL version 8.3 and above.',

	'GENERAL_SEARCH_SETTINGS'				=> 'General search settings',

	'INDEX_STATS'							=> 'Index statistics',
	'INDEXING_IN_PROGRESS'					=> 'Indexing in progress',
	'INDEXING_IN_PROGRESS_EXPLAIN'			=> 'The search backend is currently indexing all articles. This can take from a few minutes to a few hours depending on your board’s size.',

	'LIMIT_SEARCH_LOAD'						=> 'Search page system load limit',
	'LIMIT_SEARCH_LOAD_EXPLAIN'				=> 'If the 1 minute system load exceeds this value the search page will go offline, 1.0 equals ~100% utilisation of one processor. This only functions on UNIX based servers.',
	'PER_PAGE_SEARCH'						=> 'Search Results',
	'PER_PAGE_SEARCH_EXPLAIN'				=> 'The number of items displayed on the search results page.',

	'PROGRESS_BAR'							=> 'Progress bar',

	'SEARCH_INDEX_CREATE_REDIRECT'			=> array(
		2	=> 'All posts up to post id %2$d have now been indexed, of which %1$d posts were within this step.<br />',
	),
	'SEARCH_INDEX_CREATE_REDIRECT_RATE'		=> array(
		2	=> 'The current rate of indexing is approximately %1$.1f posts per second.<br />Indexing in progress…',
	),
	'SEARCH_INDEX_DELETE_REDIRECT'			=> array(
		2	=> 'All posts up to post id %2$d have been removed from the search index.<br />Deleting in progress…',
	),
	'SEARCH_INDEX_CREATED'					=> 'Successfully indexed all posts in the board database.',
	'SEARCH_INDEX_REMOVED'					=> 'Successfully deleted the search index for this backend.',
	'SEARCH_TYPE'							=> 'Search backend',
	'SEARCH_TYPE_EXPLAIN'					=> 'phpBB allows you to choose the backend that is used for searching text in post contents. By default the search will use phpBB’s own fulltext search.',
	'SWITCHED_SEARCH_BACKEND'				=> 'You switched the search backend. In order to use the new search backend you should make sure that there is an index for the backend you chose.',

	'TOTAL_WORDS'							=> 'Total number of indexed words',
	'TOTAL_MATCHES'							=> 'Total number of word to post relations indexed',

	'YES_SEARCH'							=> 'Enable search facilities',
	'YES_SEARCH_EXPLAIN'					=> 'Enables user facing search functionality including member search.',
	'YES_SEARCH_UPDATE'						=> 'Enable fulltext updating',

	'ACP_LIBRARY_LOGS'					=> 'Log action',
	'ACP_LIBRARY_LOGS_EXPLAIN'			=> 'This is a list of actions performed with the library. You can sort the list by user name, date, IP-address or action. You can delete individual entries or clear the entire log as a whole.',
	'LOG_CLEAR_KB'						=> '<b>Cleaned logs library </b>',
	'LOG_CATS_MOVE_DOWN'				=> '<b>Moved category</b> %1$s <b>under</b> %2$s',
	'LOG_CATS_MOVE_UP'					=> '<b>Moved category</b> %1$s <b>on</b> %2$s',
	'LOG_CATS_ADD'						=> '<b>Add category</b><br /> %s',
	'LOG_CATS_DEL_ARTICLES'				=> '<b>Remove Category articles</b><br /> %s',
	'LOG_CATS_DEL_MOVE_POSTS_MOVE_CATS'	=> '<b>Remove Category</b> %3$s, <b>Article moved to</b> %1$s <b>and subcategories</b> % 2$s',
	'LOG_CATS_DEL_MOVE_POSTS'			=> '<b>Remove Category</b> %2$s<br /><b>and moved to an article in</b> % 1$s',
	'LOG_CATS_DEL_CAT'					=> '<b>Remove Category</b><br /> %s',
	'LOG_CATS_DEL_MOVE_POSTS_CATS'		=> '<b>Remove Category </b> %2$s<br /><b>with subcategories, articles moved to</b> %1$s',
	'LOG_CATS_DEL_POSTS_MOVE_CATS'		=> '<b>Remove Category </b> %2$s <b>with articles, subcategory moved to</b> %1$s',
	'LOG_CATS_DEL_POSTS_CATS'			=> '<b>Remove Category with articles and subcategories</b><br /> %s',
	'LOG_CATS_DEL_CATS'					=> '<b>Remove Category</b> %2$s <b>and subcategories moved to</b> %1$s',
	'LOG_CATS_EDIT'						=> '<b>Changed category information</b><br /> %1$s',
	'LOG_CATS_CAT_MOVED_TO'				=> '<b>Category</b> %1$s <b>moved to</b> %2$s',
	'LOG_CATS_SYNC'						=> '<b>Synchronized category</b><br /> %1s',
	'LOG_KB_CONFIG_SEARCH'				=> '<b>Changed search</b>',
	'LOG_KB_SEARCH_INDEX_REMOVED'		=> '<b>Removed search indexes</b>',
	'LOG_KB_SEARCH_INDEX_CREATED'		=> '<b>Create search indexes</b>',
	'LOG_LIBRARY_ADD_ARTICLE'			=> 'Added article &laquo;<b>%1s</b>&raquo; in category<br /> <b>%2s</b>',
	'LOG_LIBRARY_DEL_ARTICLE'			=> 'Removed article &laquo;<b>%1s</b>&raquo; from category<br /> <b>%2s</b>',
	'LOG_LIBRARY_EDIT_ARTICLE'			=> 'Edited article &laquo;<b>%1s</b>&raquo; in category<br /> <b>%2s</b>',
	'LOG_LIBRARY_MOVED_ARTICLE'			=> 'Moved article <b>%1s</b> from category <b>%2s</b><br />to category <b>%3s</b>',
	'LOG_LIBRARY_APPROVED_ARTICLE'		=> 'Approved article <b>%1s</b> in category <b>%2s</b><br />created by user <b>%3s</b>',
	'LOG_LIBRARY_REJECTED_ARTICLE'		=> 'Rejected article <b>%1s</b> in category <b>%2s</b><br />created by user <b>%3s</b>',
	'LOG_LIBRARY_PERMISSION_DELETED'	=> 'Remove user/group access to category <b>%1s</b><br /> %2s',
	'LOG_LIBRARY_PERMISSION_ADD'		=> 'Adding or changing user/group access to category <b>%1s</b><br /> %2s',
	'LOG_LIBRARY_CONFIG'				=> '<b>Reconfigured library</b>',
));

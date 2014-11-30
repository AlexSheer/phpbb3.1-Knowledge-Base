<?php
/**
*
* @package phpBB Extension - Knowlege baset
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'LIBRARY'						=> 'Библиотека',
	'TOTAL_ITEMS'					=> 'Статей: <strong>%d</strong>',
	'CATEGORY'						=> 'Категория',
	'CATEGORIES'					=> 'Категории',
	'CATEGORIES_LIST'				=> 'Список категорий',
	'ARTICLES'						=> 'Статьи',
	'ARTICLE'						=> 'Статья',
	'ARTICLE_AUTHOR'				=> 'Автор',
	'ARTICLE_DATE'					=> 'Дата',
	'ARTICLE_DESCRIPTION'			=> 'Описание',
	'NO_ARTICLES'					=> 'В этой категории нет статей',
	'COMMENTS'						=> 'Комментарии',
	'LEAVE_COMMENTS'				=> 'Оставить комментарий',
	'ARTICLE_MANAGE'				=> 'Управление статьями',
	'ADD_ARTICLE'					=> 'Добавить статью',
	'EDIT_ARTICLE'					=> 'Редактировать статью',
	'DELETE_ARTICLE'				=> 'Удалить статью',
	'ARTICLE_BODY'					=> 'Текст статьи',
	'ARTICLE_BODY_EXPLAIN'			=> 'Введите здесь текст статьи',
	'DELETE_ARTICLE_WARN'			=> 'Удаленную статью восстановить невозможно',
	'ARTICLE_SUBMITTED'				=> 'Статья успешно добавлена.',
	'ARTICLE_NEED_APPROVE'			=> 'Статья успешно добавлена, но требует предварительного одобрения.',
	'ARTICLE_DELETED'				=> 'Статья успешно удалена.',
	'ARTICLE_EDITED'				=> 'Статья успешно отредактирована.',
	'ARTICLE_MOVED'					=> 'Статья успешно перенесена.',
	'RETURN_ARTICLE'				=> '%sПерейти к статье%s',
	'RETURN_CAT'					=> '%sВернуться в категорию%s',
	'RETURN_NEW_CAT'				=> '%sПерейти в новую категорию%s',
	'RETURN_LIBRARY'				=> '%sВернутся в Библиотеку%s',
	'CAT_NO_EXISTS'					=> 'Такой категории не существует',
	'ARTICLE_NO_EXISTS'				=> 'Такой статьи не существует',
	'NO_TEXT' 						=> 'Вы не ввели текст статьи',
	'NO_TITLE'						=> 'Вы не указали название статьи',
	'NO_DESCR'						=> 'Вы не ввели описание статьи',
	'DESCR'							=> 'Описание статьи',
	'ARTICLE_TITLE'					=> 'Название статьи',
	'READ_FULL'						=> 'Прочитать статью полностью',
	'NO_ID_SPECIFIED'				=> 'Не указан номер статьи',
	'CONFIRM_DELETE_ARTICLE'		=> 'Вы уверены, что хотите удалить эту статью?',
	'EDIT'							=> 'Редактировать',
	'PRINT'							=> 'Версия для печати',

	'NEED_APPROOVE'							=> 'Статья требует одобрения',
	'NO_NEED_APPROVE' 						=> 'Эта статья не требует одобрения.',
	'APPROVE'								=> 'Одобрить',
	'DISAPPROVE'							=> 'Отклонить',
	'ARTICLE_APPROVED_SUCESS'				=> 'Статья была одобрена.',
	'ARTICLE_DISAPPROVED_SUCESS'			=> 'Статья была отклонена.',
	'NOTIFICATION_NEED_APPROVAL'			=> '<b>Ожидает одобрения</b> статья от пользователя %1$s:',
	'NOTIFICATION_TYPE_NEED_APPROVAL'		=> 'Статья ожидает одобрения',
	'NOTIFICATION_ARTICLE_APPROVE'			=> '<b>Модератор</b> %1$s одобрил вашу статью:',
	'NOTIFICATION_ARTICLE_DISAPPROVE'		=>'<b>Модератор</b> %1$s отклонил вашу статью:',
	'NOTIFICATION_TYPE_ARTICLE_APPROVE'		=> 'Статья была одобрена',
	'NOTIFICATION_TYPE_ARTICLE_DISAPPROVE'	=> 'Статья была отклонена',
	'LOGIN_EXPLAIN_APPROVE'					=> 'Для проведения этого действия вы должны войти на конференцию.',

	'NO_CAT_YET'					=> 'В библиотеке еще нет ни одной категории.',
	'COULDNT_GET_CAT_DATA'			=> 'Невозможно получить данные',
	'COULDNT_UPDATE_ORDER'			=> 'Невозможно изменнить порядок категорий',
	'WARNING_DEFAULT_CONFIG'		=> 'Конфигурационные настройки библиотеки усановлены по умолчанию, это может привести к некорректной работе модуля.<br />Пожалуйста, перейдите в <b>Конфигурация</b> и задайте необходимые значения.',
// Permissions
	'KB_PERMISSIONS'				=> 'Права доступа',
	'RULES_KB_ADD_CAN'				=> 'Вы <b>можете</b> добавлять статьи',
	'RULES_KB_ADD_CANNOT'			=> 'Вы <b>не можете</b> добавлять статьи',
	'RULES_KB_EDIT_CAN'				=> 'Вы <b>можете</b> редактировать свои статьи',
	'RULES_KB_EDIT_CANNOT'			=> 'Вы <b>не можете</b> редактировать свои статьи',
	'RULES_KB_DELETE_CAN'			=> 'Вы <b>можете</b> удалять свои статьи',
	'RULES_KB_DELETE_CANNOT'		=> 'Вы <b>не можете</b> удалять свои статьи',
	'RULES_KB_ADD_NOAPPROVE'		=> 'Вы <b>можете</b> добавлять статьи без предварительного одобрения',
	'RULES_KB_ADD_NOAPPROVE_CANNOT'	=> 'Вы <b>не можете</b> добавлять статьи без предварительного одобрения',

	'RULES_KB_DELETE_MOD_CAN'	=> 'Вы <b>можете</b> удалять статьи',
	'RULES_KB_EDIT_MOD_CAN'		=> 'Вы <b>можете</b> редактировать статьи',
	'RULES_KB_APPROVE_MOD_CAN'	=> 'Вы <b>можете</b> одобрять статьи',

	'RULES_KB_MOD_EDIT_CANNOT'	=> 'Вы <b>не можете</b> редактировать статьи',
	'RULES_KB_MOD_DELETE_CANNOT'=> 'Вы <b>не можете</b> удалять статьи',
	'RULES_KB_APPROVE_MOD_CANNOT'=> 'Вы <b>не можете</b> одобрять статьи',

// Search
	'SEARCH_KB'					=> 'Поиск',
	'SEARCH_IN_CAT'				=> 'Поиск в категории…',
	'FOUND_KB_SEARCH_MATCHES'	=> 'Найдено совпадений %s',
	'FOUND_KB_SEARCH_MATCH'		=> 'Найдено %s совпадение',
	'RETURN_TO_KB_SEARCH_ADV'	=> 'Вернуться к расширенному поиску',
	'SEARCH_ARTICLES_TITLE_ONLY'=> 'Только в заголовках статей',
	'SEARCH_ARTICLES_ONLY'		=> 'Только в тексте статей',
	'EMPTY_QUERY'				=> 'Вы не ввели никакого поискового запроса',
	'SEARCH_DISABLED'			=> 'Поиск в Библиотеке отключен администратором',
	'SORT_ARTICLE_TITLE'		=> 'Заголовок статьи',
	'SEARCH_CAT'				=> 'Искать в категориях',
	'SEARCH_CAT_EXPLAIN'		=> 'Выберите категорию или категории, в которых будет произведён поиск. Если не выбрано ничего, поиск будет осуществлен во всех категориях.',
));

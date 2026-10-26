<?php
/**
 * Better Contact
 * Modern, privacy-minded contact tools for SMF 2.1.x.
 *
 * @package BetterContact
 * @version 0.1.2
 */

if (!defined('SMF'))
	die('No direct access...');

define('BETTER_CONTACT_VERSION', '0.1.2');

function BetterContact_LoadLanguage()
{
	global $txt, $settings;
	static $merged = false;

	loadLanguage('BetterContact');
	if ($merged)
		return;

	if (!empty($settings['default_theme_dir']))
	{
		$default_file = $settings['default_theme_dir'] . '/languages/BetterContact.english.php';
		if (is_file($default_file))
		{
			$active_txt = $txt;
			template_include($default_file);
			$txt = $active_txt + $txt;
		}
	}

	$merged = true;
}

/** Make public form text safe to embed in an SMF PM without activating BBCode. */
function BetterContact_PMText($text)
{
	$text = (string) $text;

	// SMF PM bodies are parsed as BBC. Neutralize brackets so guest-supplied
	// tags such as [img] are displayed literally instead of being executed.
	return strtr($text, array('[' => '&#91;', ']' => '&#93;'));
}

function BetterContact_MemberContext(&$member_context, $user, $display_custom_fields)
{
	global $modSettings;

	if (empty($modSettings['better_contact_enabled']) || empty($modSettings['better_contact_hide_member_email']))
		return;

	$member_context['show_email'] = false;
}

function BetterContact_DisplayContext(&$output, &$message, $counter)
{
	global $modSettings;

	if (empty($modSettings['better_contact_enabled']) || empty($output['member']) || !is_array($output['member']))
		return;

	$is_guest = !empty($output['member']['is_guest']);

	if (($is_guest && !empty($modSettings['better_contact_hide_guest_email'])) || (!$is_guest && !empty($modSettings['better_contact_hide_member_email'])))
		$output['member']['show_email'] = false;
}

function BetterContact_Actions(&$actions)
{
	$actions['contactstaff'] = array('BetterContact.php', 'BetterContact_Contact');
	$actions['contactreply'] = array('BetterContact.php', 'BetterContact_Reply');
}

/** Add Contact Staff beside Help / Terms in the rendered forum footer. */
function BetterContact_Buffer($buffer)
{
	global $modSettings, $scripturl, $txt;

	if (empty($buffer) || empty($modSettings['better_contact_enabled']) || empty($modSettings['better_contact_form_enabled']))
		return $buffer;

	if (strpos($buffer, 'better_contact_footer_link') !== false)
		return $buffer;

	BetterContact_LoadLanguage();

	$label = isset($txt['better_contact_menu_contact']) ? $txt['better_contact_menu_contact'] : 'Contact Staff';
	$link = '<a class="better_contact_footer_link" href="' . $scripturl . '?action=contactstaff">' . $label . '</a>';

	$footer_pos = strpos($buffer, 'id="footer"');
	if ($footer_pos === false)
		$footer_pos = strpos($buffer, "id='footer'");
	if ($footer_pos === false)
		$footer_pos = strpos($buffer, 'id="footer_section"');
	if ($footer_pos === false)
		$footer_pos = strpos($buffer, "id='footer_section'");
	if ($footer_pos === false)
		return $buffer;

	$before = substr($buffer, 0, $footer_pos);
	$footer = substr($buffer, $footer_pos);

	$terms = !empty($txt['terms_and_rules']) ? $txt['terms_and_rules'] : 'Terms and Rules';
	$terms_pattern = '~(<a\\b[^>]*>[^<]*' . preg_quote($terms, '~') . '[^<]*</a>)~i';
	$count = 0;
	$footer = preg_replace($terms_pattern, '$1 | ' . $link, $footer, 1, $count);

	if (!$count)
	{
		$help = !empty($txt['help']) ? $txt['help'] : 'Help';
		$help_pattern = '~(<a\\b[^>]*>[^<]*' . preg_quote($help, '~') . '[^<]*</a>)~i';
		$footer = preg_replace($help_pattern, '$1 | ' . $link, $footer, 1);
	}

	return $before . $footer;
}

/** Load a contact record by contact id or PM id. */
function BetterContact_LoadRecord($id_contact = 0, $id_pm = 0)
{
	global $smcFunc;

	$id_contact = (int) $id_contact;
	$id_pm = (int) $id_pm;
	if ($id_contact <= 0 && $id_pm <= 0)
		return false;

	$request = $smcFunc['db_query']('', '
		SELECT bc.id_contact, bc.id_pm, bc.created_at, bc.updated_at, bc.sender_name,
			bc.reply_email, bc.id_member, bc.subject, bc.message, bc.status,
			bc.replied_by, bc.replied_at, bc.last_reply
		FROM {db_prefix}better_contact_messages AS bc
		WHERE ' . ($id_contact > 0 ? 'bc.id_contact = {int:id_contact}' : 'bc.id_pm = {int:id_pm}') . '
		LIMIT 1',
		array(
			'id_contact' => $id_contact,
			'id_pm' => $id_pm,
		)
	);

	$record = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	return $record ?: false;
}

/** Capture the PM id created for a Better Contact submission. */
function BetterContact_PersonalMessageAfter(&$id_pm, &$log, &$recipients, &$from, &$subject, &$message)
{
	global $smcFunc;

	if (empty($GLOBALS['better_contact_pending_contact_id']) || empty($id_pm))
		return;

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}better_contact_messages
		SET id_pm = {int:id_pm}, updated_at = {int:updated_at}
		WHERE id_contact = {int:id_contact}',
		array(
			'id_pm' => (int) $id_pm,
			'updated_at' => time(),
			'id_contact' => (int) $GLOBALS['better_contact_pending_contact_id'],
		)
	);
}

/**
 * Add a real quickbutton and live status block to Better Contact PMs.
 * This avoids mailto links and keeps PM state current without rewriting PM text.
 */
function BetterContact_PreparePMContext(&$output, &$message, $counter)
{
	global $scripturl, $txt, $user_info;
	static $cache = array();

	if (empty($user_info['is_admin']) || empty($output['id']))
		return;

	$id_pm = (int) $output['id'];
	if (!array_key_exists($id_pm, $cache))
		$cache[$id_pm] = BetterContact_LoadRecord(0, $id_pm);

	$record = $cache[$id_pm];
	if (empty($record))
		return;

	BetterContact_LoadLanguage();

	$status_key = 'better_contact_status_' . $record['status'];
	$status_label = isset($txt[$status_key]) ? $txt[$status_key] : ucfirst($record['status']);
	$status_text = sprintf($txt['better_contact_pm_live_status'], (int) $record['id_contact'], $status_label);

	if (!empty($record['replied_at']))
	{
		$replied_name = $txt['better_contact_unknown_staff'];
		if (!empty($record['replied_by']))
		{
			global $smcFunc;
			$request = $smcFunc['db_query']('', '
				SELECT real_name
				FROM {db_prefix}members
				WHERE id_member = {int:id_member}
				LIMIT 1',
				array('id_member' => (int) $record['replied_by'])
			);
			if ($row = $smcFunc['db_fetch_assoc']($request))
				$replied_name = $row['real_name'];
			$smcFunc['db_free_result']($request);
		}
		$status_text .= '<br>' . sprintf($txt['better_contact_pm_replied_meta'], htmlspecialchars($replied_name, ENT_QUOTES, 'UTF-8'), timeformat((int) $record['replied_at']));
	}

	// dev3a: the PM quickbutton is the single reply action. Strip the legacy
	// in-message Reply by Email link from dev3 PMs so existing test messages
	// clean up immediately after upgrade, not only newly submitted contacts.
	$output['body'] = preg_replace(
		'~(?:<br\s*/?>\s*)?<strong>\s*<a[^>]+action=contactreply[^>]*>.*?</a>\s*</strong>~is',
		'',
		$output['body']
	);
	$output['body'] = preg_replace(
		'~(?:<br\s*/?>\s*)?<a[^>]+action=contactreply[^>]*>[^<]*</a>~is',
		'',
		$output['body']
	);

	// Move SMF's bulk-selection checkbox out of the lower quickbutton strip and
	// into the upper-right corner of this Better Contact PM. Keep the stock
	// pms[] name/value and listing-checkbox sync so Remove selected still works.
	$select_html = '';
	if (isset($output['quickbuttons']['quickmod']) && !empty($output['quickbuttons']['quickmod']['show']))
	{
		$select_html = '<span class="better_contact_pm_select"><input type="checkbox" name="pms[]" id="deletedisplay' . $id_pm . '" value="' . $id_pm . '" onclick="var listing=document.getElementById(\'deletelisting' . $id_pm . '\'); if (listing) listing.checked=this.checked;"></span>';
		unset($output['quickbuttons']['quickmod']);
	}

	$output['body'] = '<style>#msg' . $id_pm . ' .postarea{position:relative}#msg' . $id_pm . ' .keyinfo{padding-right:34px}#msg' . $id_pm . ' .better_contact_pm_select{position:absolute;top:8px;right:8px;z-index:3;line-height:1}</style>'
		. $select_html
		. '<div class="information better_contact_pm_status">' . $status_text . '</div>'
		. $output['body'];

	$output['quickbuttons']['better_contact_reply'] = array(
		'label' => $txt['better_contact_reply_by_email'],
		'href' => $scripturl . '?action=contactreply;id=' . (int) $record['id_contact'],
		'icon' => 'reply_button',
		'show' => $record['status'] !== 'closed',
	);
}

/** Guest/member Contact Staff form. */
function BetterContact_Contact()
{
	global $context, $modSettings, $scripturl, $smcFunc, $sourcedir, $txt, $user_info;

	BetterContact_LoadLanguage();

	if (empty($modSettings['better_contact_enabled']) || empty($modSettings['better_contact_form_enabled']))
		fatal_lang_error('better_contact_form_disabled', false);

	loadTemplate('BetterContact');
	$context['page_title'] = $txt['better_contact_form_title'];
	$context['sub_template'] = 'better_contact_form';
	$context['better_contact_errors'] = array();
	$context['better_contact_sent'] = !empty($_GET['sent']);
	$context['better_contact_sent_id'] = !empty($_GET['id']) ? (int) $_GET['id'] : 0;

	$prefill_subject = isset($_GET['subject']) ? trim((string) $_GET['subject']) : '';
	if (strlen($prefill_subject) > 150)
		$prefill_subject = substr($prefill_subject, 0, 150);

	$context['better_contact_form'] = array(
		'name' => !$user_info['is_guest'] ? $user_info['name'] : '',
		'email' => '',
		'subject' => $prefill_subject,
		'message' => '',
	);

	// Use SMF's native verification control so Better CAPTCHA can enhance the
	// Contact Staff form through the same verification lifecycle used elsewhere.
	$verification_options = array('id' => 'better_contact');
	$context['better_contact_verification'] = false;
	$context['better_contact_verification_id'] = $verification_options['id'];

	if (!empty($modSettings['better_contact_captcha_enabled']) && !empty($user_info['is_guest']))
	{
		require_once($sourcedir . '/Subs-Editor.php');
		$context['better_contact_verification'] = create_control_verification(
			$verification_options,
			$_SERVER['REQUEST_METHOD'] === 'POST'
		);
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		return;

	checkSession('post');

	if (!empty($modSettings['better_contact_captcha_enabled']) && !empty($user_info['is_guest']) && is_array($context['better_contact_verification']))
	{
		$verification_errors = $context['better_contact_verification'];
		$shown_specific_error = false;

		// Better CAPTCHA returns SMF-style language keys. Surface any specific
		// message it provides (rate limit, expiry, puzzle, session, timing) so a
		// guest can tell a temporary lockout from an ordinary wrong answer.
		foreach ($verification_errors as $verification_error)
		{
			$error_key = 'error_' . (string) $verification_error;
			if (isset($txt[$error_key]))
			{
				$context['better_contact_errors'][] = $txt[$error_key];
				$shown_specific_error = true;
			}
		}

		if (!$shown_specific_error)
			$context['better_contact_errors'][] = $txt['better_contact_error_verification'];

		// create_control_verification(..., true) is the test pass. Better CAPTCHA
		// intentionally consumes its issued challenge state after every tested
		// attempt. Because Contact Staff renders its errors in the same request,
		// immediately run the non-test pass again to arm the retry challenge.
		// This is also what lets adaptive failure tiers, puzzle escalation and
		// temporary lockouts become visible on the very next rendered form.
		$context['better_contact_verification'] = create_control_verification($verification_options, false);
	}

	if (!empty($_POST['website']))
		fatal_lang_error('better_contact_submission_failed', false);

	$name = isset($_POST['contact_name']) ? trim((string) $_POST['contact_name']) : '';
	$email = isset($_POST['contact_email']) ? trim((string) $_POST['contact_email']) : '';
	$subject = isset($_POST['contact_subject']) ? trim((string) $_POST['contact_subject']) : '';
	$message = isset($_POST['contact_message']) ? trim((string) $_POST['contact_message']) : '';

	$context['better_contact_form'] = array(
		'name' => $name,
		'email' => $email,
		'subject' => $subject,
		'message' => $message,
	);

	if ($name === '')
		$context['better_contact_errors'][] = $txt['better_contact_error_name'];
	if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))
		$context['better_contact_errors'][] = $txt['better_contact_error_email'];
	if ($subject === '')
		$context['better_contact_errors'][] = $txt['better_contact_error_subject'];
	if ($message === '')
		$context['better_contact_errors'][] = $txt['better_contact_error_message'];
	if (strlen($name) > 100 || strlen($email) > 255 || strlen($subject) > 150 || strlen($message) > 10000)
		$context['better_contact_errors'][] = $txt['better_contact_error_too_long'];

	if (!empty($context['better_contact_errors']))
		return;

	$admins = array();
	$request = $smcFunc['db_query']('', '
		SELECT id_member
		FROM {db_prefix}members
		WHERE is_activated IN (1, 11)
			AND (id_group = {int:admin_group} OR FIND_IN_SET({int:admin_group}, additional_groups) != 0)',
		array('admin_group' => 1)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$admins[] = (int) $row['id_member'];
	$smcFunc['db_free_result']($request);
	$admins = array_values(array_unique(array_filter($admins)));

	if (empty($admins))
	{
		$context['better_contact_errors'][] = $txt['better_contact_no_admins'];
		return;
	}

	$id_contact = $smcFunc['db_insert']('insert',
		'{db_prefix}better_contact_messages',
		array(
			'id_pm' => 'int', 'created_at' => 'int', 'updated_at' => 'int',
			'sender_name' => 'string-100', 'reply_email' => 'string-255', 'id_member' => 'int',
			'subject' => 'string-150', 'message' => 'string', 'status' => 'string-20',
			'replied_by' => 'int', 'replied_at' => 'int', 'last_reply' => 'string',
		),
		array(
			0, time(), time(), $name, $email, !$user_info['is_guest'] ? (int) $user_info['id'] : 0,
			$subject, $message, 'new', 0, 0, '',
		),
		array('id_contact'),
		1
	);

	if (empty($id_contact))
	{
		$context['better_contact_errors'][] = $txt['better_contact_submission_failed'];
		return;
	}

	require_once($sourcedir . '/Subs-Post.php');

	$pm_subject = sprintf($txt['better_contact_pm_subject'], (int) $id_contact, $subject);
	$account_label = !$user_info['is_guest'] ? $user_info['name'] . ' (#' . $user_info['id'] . ')' : $txt['better_contact_guest'];
	$pm_body = sprintf(
		$txt['better_contact_pm_body'],
		(int) $id_contact,
		BetterContact_PMText($name),
		BetterContact_PMText($account_label),
		BetterContact_PMText($message)
	);

	$recipients = array('to' => $admins, 'bcc' => array());
	$from = $user_info['is_guest']
		? array('id' => 0, 'name' => $name, 'username' => $name)
		: array('id' => (int) $user_info['id'], 'name' => $user_info['name'], 'username' => $user_info['username']);

	$GLOBALS['better_contact_pending_contact_id'] = (int) $id_contact;
	$result = sendpm($recipients, $pm_subject, $pm_body, false, $from);
	unset($GLOBALS['better_contact_pending_contact_id']);

	if (!is_array($result) || empty($result['sent']))
	{
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}better_contact_messages
			WHERE id_contact = {int:id_contact}',
			array('id_contact' => (int) $id_contact)
		);
		$context['better_contact_errors'][] = $txt['better_contact_submission_failed'];
		return;
	}

	redirectexit('action=contactstaff;sent=1;id=' . (int) $id_contact);
}

/** Staff-only server-side email reply screen. */
function BetterContact_Reply()
{
	global $context, $modSettings, $scripturl, $smcFunc, $sourcedir, $txt, $user_info, $webmaster_email;

	isAllowedTo('admin_forum');
	BetterContact_LoadLanguage();

	$id_contact = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
	$record = BetterContact_LoadRecord($id_contact, 0);
	if (empty($record))
		fatal_lang_error('better_contact_record_not_found', false);

	loadTemplate('BetterContact');
	$context['page_title'] = sprintf($txt['better_contact_reply_title'], (int) $record['id_contact']);
	$context['sub_template'] = 'better_contact_reply';
	$context['better_contact_record'] = $record;
	$context['better_contact_reply_errors'] = array();
	$context['better_contact_reply_sent'] = !empty($_GET['sent']);
	$context['better_contact_reply_message'] = '';
	$context['better_contact_return_pm'] = !empty($record['id_pm']) ? $scripturl . '?action=pm;pmsg=' . (int) $record['id_pm'] . '#msg' . (int) $record['id_pm'] : $scripturl . '?action=pm';

	if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		return;

	checkSession('post');

	if (isset($_POST['close_contact']))
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}better_contact_messages
			SET status = {string:status}, updated_at = {int:updated_at}
			WHERE id_contact = {int:id_contact}',
			array('status' => 'closed', 'updated_at' => time(), 'id_contact' => $id_contact)
		);
		redirectexit('action=contactreply;id=' . $id_contact);
	}

	if (isset($_POST['reopen_contact']))
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}better_contact_messages
			SET status = {string:status}, updated_at = {int:updated_at}
			WHERE id_contact = {int:id_contact}',
			array('status' => !empty($record['replied_at']) ? 'replied' : 'new', 'updated_at' => time(), 'id_contact' => $id_contact)
		);
		redirectexit('action=contactreply;id=' . $id_contact);
	}

	if (empty($_POST['send_reply']))
		return;

	if ($record['status'] === 'closed')
	{
		$context['better_contact_reply_errors'][] = $txt['better_contact_error_closed'];
		return;
	}

	$reply = isset($_POST['reply_message']) ? trim((string) $_POST['reply_message']) : '';
	$context['better_contact_reply_message'] = $reply;
	if ($reply === '')
		$context['better_contact_reply_errors'][] = $txt['better_contact_error_reply_message'];
	if (strlen($reply) > 20000)
		$context['better_contact_reply_errors'][] = $txt['better_contact_error_reply_too_long'];
	if (!empty($context['better_contact_reply_errors']))
		return;

	require_once($sourcedir . '/Subs-Post.php');

	$email_subject = 'Re: ' . $record['subject'];
	$email_body = sprintf(
		$txt['better_contact_email_reply_body'],
		$record['sender_name'],
		$reply,
		$context['forum_name'],
		(int) $record['id_contact'],
		$record['subject']
	);

	$reply_address = !empty($modSettings['better_contact_reply_address']) && filter_var($modSettings['better_contact_reply_address'], FILTER_VALIDATE_EMAIL)
		? $modSettings['better_contact_reply_address']
		: $webmaster_email;

	$sent = sendmail($record['reply_email'], $email_subject, $email_body, $reply_address, 'bc' . (int) $record['id_contact'], false, 0, null, true);
	if (!$sent)
	{
		$context['better_contact_reply_errors'][] = $txt['better_contact_email_failed'];
		return;
	}

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}better_contact_messages
		SET status = {string:status}, replied_by = {int:replied_by}, replied_at = {int:replied_at},
			last_reply = {string:last_reply}, updated_at = {int:updated_at}
		WHERE id_contact = {int:id_contact}',
		array(
			'status' => 'replied',
			'replied_by' => (int) $user_info['id'],
			'replied_at' => time(),
			'last_reply' => $reply,
			'updated_at' => time(),
			'id_contact' => $id_contact,
		)
	);

	redirectexit('action=contactreply;id=' . $id_contact . ';sent=1');
}

function BetterContact_AdminAreas(&$admin_areas)
{
	global $txt;

	BetterContact_LoadLanguage();

	$admin_areas['config']['areas']['bettercontact'] = array(
		'label' => $txt['better_contact_admin_title'],
		'file' => 'BetterContact.php',
		'function' => 'BetterContact_Admin',
		'icon' => 'better_contact.png',
		'permission' => array('admin_forum'),
	);
}

function BetterContact_Admin()
{
	global $context, $txt, $modSettings, $webmaster_email;

	isAllowedTo('admin_forum');
	BetterContact_LoadLanguage();
	loadTemplate('BetterContactAdmin');

	$context['page_title'] = $txt['better_contact_admin_title'];

	if (!empty($context['admin_menu_name']))
	{
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $txt['better_contact_admin_title'],
			'description' => $txt['better_contact_admin_desc'],
		);
	}

	if (isset($_POST['save']))
	{
		checkSession('post');
		$reply_address = isset($_POST['better_contact_reply_address']) ? trim((string) $_POST['better_contact_reply_address']) : '';
		if ($reply_address !== '' && !filter_var($reply_address, FILTER_VALIDATE_EMAIL))
			fatal_lang_error('better_contact_invalid_reply_address', false);

		updateSettings(array(
			'better_contact_enabled' => !empty($_POST['better_contact_enabled']) ? 1 : 0,
			'better_contact_hide_member_email' => !empty($_POST['better_contact_hide_member_email']) ? 1 : 0,
			'better_contact_hide_guest_email' => !empty($_POST['better_contact_hide_guest_email']) ? 1 : 0,
			'better_contact_form_enabled' => !empty($_POST['better_contact_form_enabled']) ? 1 : 0,
			'better_contact_captcha_enabled' => !empty($_POST['better_contact_captcha_enabled']) ? 1 : 0,
			'better_contact_reply_address' => $reply_address,
		));

		redirectexit('action=admin;area=bettercontact;saved');
	}

	$context['better_contact_settings'] = array(
		'enabled' => !empty($modSettings['better_contact_enabled']),
		'hide_member_email' => !empty($modSettings['better_contact_hide_member_email']),
		'hide_guest_email' => !empty($modSettings['better_contact_hide_guest_email']),
		'contact_form_enabled' => !empty($modSettings['better_contact_form_enabled']),
		'captcha_enabled' => !empty($modSettings['better_contact_captcha_enabled']),
		'reply_address' => isset($modSettings['better_contact_reply_address']) ? $modSettings['better_contact_reply_address'] : $webmaster_email,
	);
	$context['better_contact_saved'] = isset($_GET['saved']);
	$context['sub_template'] = 'better_contact_admin';
}

<?php
/** Better Contact installer/uninstaller for SMF 2.1.x. @version 0.1.2 */
if (!defined('SMF')) die('No direct access...');

global $context, $sourcedir, $modSettings, $smcFunc, $webmaster_email, $boarddir;
require_once($sourcedir . '/Subs.php');

$new_hooks = array(
	'integrate_member_context' => 'BetterContact_MemberContext',
	'integrate_prepare_display_context' => 'BetterContact_DisplayContext',
	'integrate_admin_areas' => 'BetterContact_AdminAreas',
	'integrate_actions' => 'BetterContact_Actions',
	'integrate_buffer' => 'BetterContact_Buffer',
	'integrate_personal_message_after' => 'BetterContact_PersonalMessageAfter',
	'integrate_prepare_pm_context' => 'BetterContact_PreparePMContext',
);
$new_hook_file = '$sourcedir/BetterContact.php';

$old_hooks = array(
	'integrate_member_context' => 'BetterProfileContact_MemberContext',
	'integrate_prepare_display_context' => 'BetterProfileContact_DisplayContext',
	'integrate_admin_areas' => 'BetterProfileContact_AdminAreas',
	'integrate_actions' => 'BetterProfileContact_Actions',
	'integrate_buffer' => 'BetterProfileContact_Buffer',
	'integrate_menu_buttons' => 'BetterProfileContact_MenuButtons',
);
$old_hook_file = '$sourcedir/BetterProfileContact.php';

foreach ($old_hooks as $hook => $function)
{
	remove_integration_function($hook, $function, true, $old_hook_file);
	remove_integration_function($hook, $function, true);
}

if (!empty($context['uninstalling']))
{
	foreach ($new_hooks as $hook => $function)
	{
		remove_integration_function($hook, $function, true, $new_hook_file);
		remove_integration_function($hook, $function, true);
	}
	return;
}

foreach ($new_hooks as $hook => $function)
{
	remove_integration_function($hook, $function, true);
	add_integration_function($hook, $function, true, $new_hook_file);
}

// Migrate Better Profile Contact settings to Better Contact settings.
$migrations = array(
	'better_profile_contact_enabled' => 'better_contact_enabled',
	'better_profile_contact_hide_member_email' => 'better_contact_hide_member_email',
	'better_profile_contact_hide_guest_email' => 'better_contact_hide_guest_email',
	'better_profile_contact_form_enabled' => 'better_contact_form_enabled',
);
foreach ($migrations as $old => $new)
	if (!isset($modSettings[$new]) && isset($modSettings[$old]))
		updateSettings(array($new => $modSettings[$old]));

$defaults = array(
	'better_contact_enabled' => 1,
	'better_contact_hide_member_email' => 1,
	'better_contact_hide_guest_email' => 1,
	'better_contact_form_enabled' => 0,
	'better_contact_captcha_enabled' => 1,
	'better_contact_reply_address' => !empty($webmaster_email) ? $webmaster_email : '',
);
foreach ($defaults as $setting => $default)
	if (!isset($modSettings[$setting]))
		updateSettings(array($setting => $default));

// Remove migrated legacy setting rows so the rename is complete.
$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}settings
	WHERE variable LIKE {string:legacy_prefix}',
	array('legacy_prefix' => 'better\\_profile\\_contact\\_%')
);

// Clean up old Better Profile Contact files after a successful upgrade copy.
$legacy_files = array(
	$sourcedir . '/BetterProfileContact.php',
	$boarddir . '/Themes/default/BetterProfileContact.template.php',
	$boarddir . '/Themes/default/BetterProfileContactAdmin.template.php',
	$boarddir . '/Themes/default/languages/BetterProfileContact.english.php',
);
foreach ($legacy_files as $legacy_file)
	if (is_file($legacy_file))
		@unlink($legacy_file);

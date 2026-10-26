<?php
/** Better Contact destructive uninstall data cleanup. @version 0.1.2 */
if (!defined('SMF')) die('No direct access...');

global $smcFunc, $sourcedir;
require_once($sourcedir . '/Subs.php');
db_extend('packages');

$smcFunc['db_drop_table']('{db_prefix}better_contact_messages');
$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}settings
	WHERE variable LIKE {string:new_prefix}
		OR variable LIKE {string:old_prefix}',
	array(
		'new_prefix' => 'better\\_contact\\_%',
		'old_prefix' => 'better\\_profile\\_contact\\_%',
	)
);

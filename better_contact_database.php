<?php
/** Better Contact database installer/upgrader. @version 0.1.2 */
if (!defined('SMF')) die('No direct access...');

global $smcFunc, $sourcedir;
require_once($sourcedir . '/Subs.php');
db_extend('packages');

$columns = array(
	array('name' => 'id_contact', 'type' => 'int', 'unsigned' => true, 'auto' => true),
	array('name' => 'id_pm', 'type' => 'int', 'unsigned' => true, 'default' => 0),
	array('name' => 'created_at', 'type' => 'int', 'unsigned' => true, 'default' => 0),
	array('name' => 'updated_at', 'type' => 'int', 'unsigned' => true, 'default' => 0),
	array('name' => 'sender_name', 'type' => 'varchar', 'size' => 100, 'default' => ''),
	array('name' => 'reply_email', 'type' => 'varchar', 'size' => 255, 'default' => ''),
	array('name' => 'id_member', 'type' => 'int', 'unsigned' => true, 'default' => 0),
	array('name' => 'subject', 'type' => 'varchar', 'size' => 150, 'default' => ''),
	array('name' => 'message', 'type' => 'text'),
	array('name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'new'),
	array('name' => 'replied_by', 'type' => 'int', 'unsigned' => true, 'default' => 0),
	array('name' => 'replied_at', 'type' => 'int', 'unsigned' => true, 'default' => 0),
	array('name' => 'last_reply', 'type' => 'text'),
);
$indexes = array(
	array('type' => 'primary', 'columns' => array('id_contact')),
	array('type' => 'index', 'columns' => array('id_pm')),
	array('type' => 'index', 'columns' => array('status')),
	array('type' => 'index', 'columns' => array('created_at')),
);

$smcFunc['db_create_table']('{db_prefix}better_contact_messages', $columns, $indexes, array(), 'ignore');

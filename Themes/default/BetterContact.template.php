<?php
/** Better Contact public/staff templates. @version 0.1.2 */
if (!defined('SMF')) die('No direct access...');

function template_better_contact_form()
{
	global $context, $txt, $scripturl;
	$form = $context['better_contact_form'];

	echo '
	<div class="cat_bar"><h3 class="catbg">', $txt['better_contact_form_title'], '</h3></div>
	<div class="windowbg">';

	if (!empty($context['better_contact_sent']))
	{
		echo '
		<div class="infobox">', sprintf($txt['better_contact_sent'], (int) $context['better_contact_sent_id']), '</div>
		<div class="centertext" style="margin-top: 1em;">
			<a class="button" href="', $scripturl, '">', $txt['better_contact_go_forum'], '</a>
		</div>
	</div>';
		return;
	}

	if (!empty($context['better_contact_errors']))
	{
		echo '<div class="errorbox"><strong>', $txt['better_contact_fix_errors'], '</strong><ul>';
		foreach ($context['better_contact_errors'] as $error)
			echo '<li>', $error, '</li>';
		echo '</ul></div>';
	}

	echo '
		<p>', $txt['better_contact_form_desc'], '</p>
		<form action="', $scripturl, '?action=contactstaff" method="post" accept-charset="UTF-8">
			<dl class="settings">
				<dt><label for="contact_name"><strong>', $txt['better_contact_name'], '</strong></label></dt>
				<dd><input type="text" id="contact_name" name="contact_name" maxlength="100" value="', htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8'), '" required></dd>
				<dt><label for="contact_email"><strong>', $txt['better_contact_reply_email'], '</strong></label><br><span class="smalltext">', $txt['better_contact_reply_email_desc'], '</span></dt>
				<dd><input type="email" id="contact_email" name="contact_email" maxlength="255" value="', htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8'), '" required></dd>
				<dt><label for="contact_subject"><strong>', $txt['better_contact_subject'], '</strong></label></dt>
				<dd><input type="text" id="contact_subject" name="contact_subject" maxlength="150" value="', htmlspecialchars($form['subject'], ENT_QUOTES, 'UTF-8'), '" required></dd>
				<dt><label for="contact_message"><strong>', $txt['better_contact_message'], '</strong></label></dt>
				<dd><textarea id="contact_message" name="contact_message" rows="10" maxlength="10000" required>', htmlspecialchars($form['message'], ENT_QUOTES, 'UTF-8'), '</textarea></dd>';

	if (!empty($context['better_contact_verification']))
		echo '
				<dt><strong>', $txt['better_contact_verification'], '</strong><br><span class="smalltext">', $txt['better_contact_verification_desc'], '</span></dt>
				<dd class="better_contact_verification">', template_control_verification($context['better_contact_verification_id'], 'all'), '</dd>';

	echo '
			</dl>
			<div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label></div>
			<div class="righttext"><button type="submit" class="button">', $txt['better_contact_send'], '</button></div>
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
		</form>
	</div>';
}

function template_better_contact_reply()
{
	global $context, $txt, $scripturl;
	$r = $context['better_contact_record'];
	$status_key = 'better_contact_status_' . $r['status'];
	$status_label = isset($txt[$status_key]) ? $txt[$status_key] : ucfirst($r['status']);

	echo '
	<div class="cat_bar"><h3 class="catbg">', sprintf($txt['better_contact_reply_title'], (int) $r['id_contact']), '</h3></div>
	<div class="windowbg">';

	if (!empty($context['better_contact_reply_sent']))
		echo '<div class="infobox">', $txt['better_contact_reply_queued'], '</div>';

	if (!empty($context['better_contact_reply_errors']))
	{
		echo '<div class="errorbox"><strong>', $txt['better_contact_fix_errors'], '</strong><ul>';
		foreach ($context['better_contact_reply_errors'] as $error)
			echo '<li>', $error, '</li>';
		echo '</ul></div>';
	}

	echo '
		<div class="information">
			<strong>', $txt['better_contact_status'], ':</strong> ', $status_label, '<br>
			<strong>', $txt['better_contact_name'], ':</strong> ', htmlspecialchars($r['sender_name'], ENT_QUOTES, 'UTF-8'), '<br>
			<strong>', $txt['better_contact_reply_email'], ':</strong> ', htmlspecialchars($r['reply_email'], ENT_QUOTES, 'UTF-8'), '<br>
			<strong>', $txt['better_contact_subject'], ':</strong> ', htmlspecialchars($r['subject'], ENT_QUOTES, 'UTF-8'), '
		</div>
		<div class="cat_bar"><h3 class="catbg">', $txt['better_contact_original_message'], '</h3></div>
		<div class="windowbg">', nl2br(htmlspecialchars($r['message'], ENT_QUOTES, 'UTF-8')), '</div>';

	if (!empty($r['last_reply']))
		echo '
		<div class="cat_bar" style="margin-top:1em;"><h3 class="catbg">', $txt['better_contact_last_reply'], '</h3></div>
		<div class="windowbg">', nl2br(htmlspecialchars($r['last_reply'], ENT_QUOTES, 'UTF-8')), '</div>';

	if ($r['status'] !== 'closed')
	{
		echo '
		<form action="', $scripturl, '?action=contactreply;id=', (int) $r['id_contact'], '" method="post" accept-charset="UTF-8" style="margin-top:1em;">
			<dl class="settings">
				<dt><label for="reply_message"><strong>', $txt['better_contact_reply_message'], '</strong></label><br><span class="smalltext">', $txt['better_contact_reply_message_desc'], '</span></dt>
				<dd><textarea id="reply_message" name="reply_message" rows="12" maxlength="20000">', htmlspecialchars($context['better_contact_reply_message'], ENT_QUOTES, 'UTF-8'), '</textarea></dd>
			</dl>
			<div class="righttext">
				<button type="submit" name="send_reply" value="1" class="button">', $txt['better_contact_send_email_reply'], '</button>
				<button type="submit" name="close_contact" value="1" class="button">', $txt['better_contact_mark_closed'], '</button>
			</div>
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
		</form>';
	}
	else
	{
		echo '
		<form action="', $scripturl, '?action=contactreply;id=', (int) $r['id_contact'], '" method="post" style="margin-top:1em;">
			<div class="righttext"><button type="submit" name="reopen_contact" value="1" class="button">', $txt['better_contact_reopen'], '</button></div>
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
		</form>';
	}

	echo '
		<div style="margin-top:1em;"><a class="button" href="', $context['better_contact_return_pm'], '">', $txt['better_contact_back_to_pm'], '</a></div>
	</div>';
}

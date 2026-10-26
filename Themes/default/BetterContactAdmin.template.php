<?php
/** Better Contact admin template. @version 0.1.2 */
if (!defined('SMF')) die('No direct access...');

function template_better_contact_admin()
{
	global $context, $txt, $scripturl;
	$settings = $context['better_contact_settings'];

	if (!empty($context['better_contact_saved']))
		echo '<div class="infobox">', $txt['better_contact_saved'], '</div>';

	echo '
	<form action="', $scripturl, '?action=admin;area=bettercontact" method="post" accept-charset="UTF-8">
		<div class="cat_bar"><h3 class="catbg">', $txt['better_contact_settings_title'], '</h3></div>
		<div class="windowbg">
			<dl class="settings">
				<dt><strong>', $txt['better_contact_enabled'], '</strong><br><span class="smalltext">', $txt['better_contact_enabled_desc'], '</span></dt>
				<dd><input type="checkbox" name="better_contact_enabled" value="1"', !empty($settings['enabled']) ? ' checked' : '', '></dd>
				<dt><strong>', $txt['better_contact_hide_member_email'], '</strong><br><span class="smalltext">', $txt['better_contact_hide_member_email_desc'], '</span></dt>
				<dd><input type="checkbox" name="better_contact_hide_member_email" value="1"', !empty($settings['hide_member_email']) ? ' checked' : '', '></dd>
				<dt><strong>', $txt['better_contact_hide_guest_email'], '</strong><br><span class="smalltext">', $txt['better_contact_hide_guest_email_desc'], '</span></dt>
				<dd><input type="checkbox" name="better_contact_hide_guest_email" value="1"', !empty($settings['hide_guest_email']) ? ' checked' : '', '></dd>
				<dt><strong>', $txt['better_contact_form_enabled'], '</strong><br><span class="smalltext">', $txt['better_contact_form_enabled_desc'], '</span></dt>
				<dd><input type="checkbox" name="better_contact_form_enabled" value="1"', !empty($settings['contact_form_enabled']) ? ' checked' : '', '></dd>
				<dt><strong>', $txt['better_contact_captcha_enabled'], '</strong><br><span class="smalltext">', $txt['better_contact_captcha_enabled_desc'], '</span></dt>
				<dd><input type="checkbox" name="better_contact_captcha_enabled" value="1"', !empty($settings['captcha_enabled']) ? ' checked' : '', '></dd>
				<dt><label for="better_contact_reply_address"><strong>', $txt['better_contact_reply_address'], '</strong></label><br><span class="smalltext">', $txt['better_contact_reply_address_desc'], '</span></dt>
				<dd><input type="email" id="better_contact_reply_address" name="better_contact_reply_address" maxlength="255" value="', htmlspecialchars($settings['reply_address'], ENT_QUOTES, 'UTF-8'), '"></dd>
			</dl>
			<div class="information"><strong>', $txt['better_contact_contact_path'], '</strong><br>', $txt['better_contact_contact_path_desc'], '</div>
			<div class="righttext"><input type="submit" name="save" value="', $txt['save'], '" class="button"></div>
		</div>
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
	</form>';
}

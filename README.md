# Better Contact v0.1.2

Stable release for SMF 2.1.x.

## v0.1.2 — Stable Release Audit

- Restored safe Contact Staff subject prefill support for integrations such as Better Ads via `?subject=`.
- Neutralized guest-supplied BBCode in staff PM copies so public submissions cannot trigger embedded BBCode such as remote images when staff read the PM.
- Preserves the original submitted text unchanged in Better Contact's private record for the staff reply screen.
- Includes the guest-only CAPTCHA lifecycle fixes from 0.1.1a/0.1.1b and the branded Better SMF admin icon from 0.1.1c.
- Normalized package, source, template, language, and documentation version metadata.
- Verified install/upgrade/uninstall behavior, preserve-data uninstall behavior, admin/session permissions, mail reply flow, and package integrity.

Modern, privacy-minded contact tools for SMF 2.1.x.

Better Contact keeps member contact inside SMF wherever possible, reduces public exposure of email addresses, and gives guests a private Contact Staff path with an internal staff reply workflow.

## Highlights

- PM-first member-to-member contact.
- Optional suppression of member email links and addresses on SMF surfaces that use member context.
- Optional suppression of guest poster mailto links in topic view.
- Optional **Contact Staff** form linked from the forum footer.
- Guest reply addresses are stored privately in Better Contact records and are not placed in the staff PM body or public markup.
- Contact Staff submissions are delivered privately to active administrators by SMF Private Message.
- Each new submission receives a private **Contact #ID** record.
- Staff can use a native-style **Reply by Email** PM quickbutton without opening an external mail client.
- Replies are sent through SMF's configured mail system using a forum-controlled Contact Reply Address.
- A contact is marked **Replied** only after SMF reports that the immediate mail send succeeded.
- Live status on Better Contact PMs: **New / Replied / Closed**.
- Staff can close and reopen contacts.
- Reply screen shows the original contact message and the most recent staff reply.
- Active/custom theme language strings win, with missing Better Contact strings backfilled from the default theme language file.
- Upgrade support from all Better Contact development builds through 0.1.0-dev3c.

## Privacy model

Better Contact does not remove the account email address stored by SMF. Instead, it controls presentation and contact paths so email addresses do not need to be exposed as public or clickable mailto links.

For Contact Staff submissions, the supplied reply email is stored server-side in the Better Contact record so authorized staff can respond through the forum. The address is not displayed in the generated staff PM body.

## Contact Staff workflow

1. A guest or member submits the Contact Staff form.
2. Better Contact creates a private Contact #ID record.
3. Active administrators receive an SMF Private Message containing the contact message.
4. Staff use **Reply by Email** from the PM.
5. Better Contact sends the response through SMF's configured SMTP/sendmail system.
6. The record is marked **Replied** only after a successful immediate send.
7. Staff can later mark the contact **Closed** or reopen it.

## Administration

Better Contact is available under **Administration > Configuration > Better Contact**.

Administrators can control:

- Enable/disable Better Contact.
- Hide member email links and addresses.
- Hide guest poster email links.
- Enable/disable the Contact Staff form.
- Set the forum-controlled Contact Reply Address.

## Upgrade note

The package retains the historical Better Profile Contact package ID so earlier builds upgrade in place. Legacy Better Profile Contact settings, hooks, and files are migrated/cleaned automatically.

Contact Staff submissions created before the dev3 record-based workflow did not have a Better Contact record ID. Those older PMs cannot gain the newer Reply by Email/status workflow retroactively. New submissions use the full workflow.

## Uninstall behavior

Better Contact follows the Better SMF uninstall standard.

**Normal uninstall**

- Removes Better Contact hooks and package files.
- Preserves Better Contact settings and stored contact records.

**Uninstall with Package Manager “Remove data” selected**

- Removes hooks and files.
- Drops the Better Contact contact-record table.
- Removes Better Contact and legacy Better Profile Contact settings.

## Compatibility

- Better Contact: **0.1.2**
- SMF: **2.1.x**

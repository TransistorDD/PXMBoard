# Changelog

## [3.0.0-alpha] (unreleased - in development) - 2026-02-10 

### System Requirements

- **PHP:** 8.5+
- **MySQL:** 5.6+ / **MariaDB:** 10.0.5+
- **Database engine:** InnoDB (previously MyISAM)
- **Character set:** utf8mb4

> ⚠️ **Database migration required.** Run `install/sql/upgrade-3.0.0.sql` before upgrading.

### 🔒 Security

- **CRITICAL:** Password hashing migrated from MD5 to bcrypt. Existing passwords are automatically migrated on the next successful login.
- **CRITICAL:** Admin and moderator permission checks were completely ineffective due to an operator precedence bug — fixed across the codebase.
- **CRITICAL:** Login tickets and password recovery tokens are now generated using a cryptographically secure random source.
- SQL escaping improved: database-specific escape functions are now used instead of `addslashes()`.

### ✨ New Features

- **PWA support:** PXMBoard can be installed as a Progressive Web App on Android Chrome and iOS Safari 16.4+.
- **New default skin (pxm):** HTMX-based skin with SPA-like navigation (no full page reloads). Two layout modes: two-column desktop and stacked. Includes dark mode and mobile support.
- **Multi-device login:** Users can remain logged in on multiple devices simultaneously. A device management screen shows active sessions (user agent, IP, last used); individual devices can be logged out remotely. Admin DB Clean removes tickets inactive for more than 180 days. Password changes invalidate all active login tickets.
- **In-app notifications:** Notification badge for replies and private messages. Notification center with clickable entries and direct links. Automatic cleanup (mark as read after 7 days, delete after 90 days).
- **Message subscriptions:** Users can subscribe to individual messages via a bell icon in the message footer. Own posts are subscribed automatically. All subscribers receive a notification when a reply is posted.
- **@mentions:** Users can mention other users with @-syntax in the editor (autocomplete after 2 characters). Mentioned users receive a notification with a direct link. Maximum 10 mentions per message.
- **Rich-text editor (Tiptap):** WYSIWYG editor with Bold, Italic, Underline, Strikethrough, Links, Images, Blockquotes, Spoiler (members only), Member-only content, YouTube and Twitch video embeds. Backwards-compatible with the existing PXM message format.
- **Server-side read tracking:** Cross-device read status for logged-in users. Visual indicators in the thread list and message view. Guests continue to use browser-based tracking.
- **Message drafts:** Posts (public and private) can be saved as drafts visible only to the author. A separate drafts list is available for navigation.
- **AJAX moderation actions:** Admin/moderator actions (delete message, delete subthread, extract subthread, delete thread) now run via AJAX without a page reload. Confirmation dialogs use the native HTML `<dialog>` element instead of `confirm()`.
- **Mobile view:** New view management with automatic detection based on viewport width and a manual toggle in the settings dropdown. Thread list and message tree adapt to the mobile layout.
- **Online list and user search:** Now open in a modal dialog and are fully functional in mobile view.
- **Cookie-only sessions:** Session IDs are no longer passed via forms or URLs. Sessions are exclusively managed via cookies.
- **ElasticSearch support (optional):** Full-text search can use ElasticSearch instead of MySQL FULLTEXT. Configured via `pxmboard-config.php`; MySQL remains the default and requires no configuration changes.

### ❌ Removed Features

- **Banner system:** Completely removed. The `pxm_banner` database table and all related admin pages and templates have been removed.
- **Flat message view:** Removed.
- **Guest posting:** Removed.

### ⚠️ Breaking Changes

The following changes require action when upgrading from 2.x:

- **Database engine and charset:** All tables migrated from MyISAM to InnoDB with the utf8mb4 character set (handled by `upgrade-3.0.0.sql`).
- **`pxm_notification` table renamed to `pxm_template`** (e-mail templates). A new `pxm_notification` table has been created for in-app notifications.
- **`u_ticket` column removed** from `pxm_user`, replaced by the new `pxm_user_login_ticket` table supporting multi-device login.
- **SKIN CHANGE — Thread list template** (`threadlist.tpl`): Layout changed from `<table>` to CSS Grid. Desktop: 7-column grid; Mobile: compact 3-column card view with a colored status indicator. Custom skins based on the table layout must be updated.
- **SKIN CHANGE — Templates consolidated:** Overall template count reduced from 41 to 31. 13 redundant per-action error and confirm templates have been removed. Use `error.tpl` and `confirm.tpl` for all error and confirmation output. Three new reusable partials added: `partial_inline_errors.tpl`, `partial_pm_tabs.tpl`, `partial_editor.tpl`.
- **SKIN CHANGE — Error system:** The `pxm_error` database table and its admin interface have been removed. Error messages are now defined in a PHP enum. Skins must not reference the old error admin pages.
- **SKIN CHANGE — Edit button renamed:** The message save form submit button has been renamed from `btn_save` to `btn_edit`.

### 🔧 Improvements

- **Smarty:** Updated from 2.6.18 to 5.7.
- **PHP 8.5 compatibility:** Reference assignments and return-by-reference removed; visibility modifiers added throughout.
- **Automated tests:** PHPUnit test suite introduced with unit and integration tests covering actions, parsers, validators, and core models.
- **Dependency management:** PHP dependencies are now managed via Composer; JavaScript dependencies via npm.

---

## [2.5.1] - 2007-06

### 🔧 Improvements

- Updated Smarty to 2.6.18.

---

## [2.5.0] - 2006-03

### 🐛 Bug Fixes

- Deleting a forum now works correctly again.
- Saving thread graphics for skins in the admin area fixed.
- `stripslashes()` now also applied to arrays (e.g. skin edit).
- Posting in closed forums is no longer possible.

### ✨ New Features

- **Search:** MySQL full-text search implemented.
  - Selection of boards to search.
  - Selection of time range.
  - Display of search result relevance.
  - Last 10 searches displayed on the search page.
- **Private messages:** Outbox added (separated from setup).
- **Board index:** Display of the newest posts.
- **Signatures:**
  - Added on display, not on save.
  - Display configurable per user.
- **Formatting:** New `strikeout` option added.
- **Installer:** Beta version of the PXMBoard installer.

### 🔧 Improvements

- PHP 5 compatibility (Smarty recursion revised).
- Updated Smarty to 2.6.13.
- Skin ID is no longer assignable per board.
- `StringValidations` for input validation.
- New `quote()` method in DB class; global DB object renamed.
- No e-mail notification for replies to own posts.
- Additional user information available in messages (profile image, registration date, etc.).
- Login via query string disabled (security).

---

## [2.2.2] - 2003-08

### 🐛 Bug Fixes

- Queries use `AND` instead of `&&` in various places.

### 🔧 Improvements

- Updated Smarty to 2.6.0.

---

## [2.2.1] - 2003-01

### 🔧 Improvements

- DB result set classes integrated directly into DB classes (2 fewer includes).
- Thread list sort order selection added to admin user form.
- Cookie functionality integrated into the session class.
- `getBody()` for messages optimised.
- Login by ticket available on every page.
- PNG image support when linking images in messages.
- New config setting `privatemessagesperpage`.

### 🐛 Bug Fixes

- User skin setting is now active immediately without a page reload.
- Special characters are no longer double-escaped when previewing a message with XSL templates.
- Template `error-cactionuserchangepwdsave` renamed to `error-cactionuserchangepwd`.
- A user's public mail address can now be empty.
- Skin selection for a user in the admin tool fixed.

---

## [2.2.0]

### ✨ New Features

- **Notifications:**
  - E-mail notification for new private messages (optional).
  - E-mail notification for replies to own posts (optional).
- **Display modes:**
  - Mode `message` can optionally show the matching thread (`showthread=1`) — enabling a 2-frame forum layout.
  - Mode `messagelist` for guest-book-style view of a thread.
- **Thread list:**
  - New sort modes: `views`, `replies`, `nickname`, `subject`.
  - Paging in flat view.
- **Administration:**
  - Selection of moderated boards in the admin user view.
  - JavaScript confirmation when setting the admin flag.
- **User management:**
  - Private e-mail address can now be changed (new DB field `u_registrationmail`).

### 🔧 Improvements

- Cookie-based login also works for `mode=board`.

### 🐛 Bug Fixes

- Replacement of spaces with `&nbsp;`.
- Pinned threads are displayed even when they fall outside the configured display time range.
- SID tag in URL corrected (`privatemessage.xsl`).
- Message display on error when saving a message.
- Nickname and user ID are passed correctly in error cases (send private message).
- Error display corrected (e.g. missing subject when sending a private message).
- Default sort order now uses the board setting.

---

## [2.1.0]

### ✨ New Features

- Board sort order.
- Pre- and post-actions.
- Passwords stored as MD5 hash in DB (⚠️ **deprecated, see 3.0.0**).
- Mail text management moved to a separate page and table (`pxm_notification`).
- Multi-line registration rejection reasons.
- `%reason%` variable for placement of the reason in the mail body.

### 🔧 Improvements

- Error list sorted by ID.
- Password recovery functionality revised.

### 🐛 Bug Fixes

- Deleting a message.
- SQL error when deleting via admin tool if no threads are found.
- User-initiated image deletion.
- Saving the online list flag in user config.
- Display of user signature for new messages in the Smarty template set.
- JavaScript error in message forms (`document.forms[0].send not defined`).

---

## [2.0.0]

### ✨ New Features

- **Architecture:**
  - Class concept and program structure completely revised.
  - DB connection and result set in separate classes.
  - Template factory for DOM XSLT and Smarty.
- **Administration:**
  - Display of users by criteria.
  - Forbidden mails, text replacement and bad words each on their own page.
  - Separate admin login (`mode=admframe`).
  - Skin editor.
  - Banners can be edited.
- **Threads:**
  - Thread splitting.
  - Subthread deletion.
- **Users:**
  - Ticket system for persistent login.
  - Password-less mode.
  - Quick post and password-less mode each have a switch in config.
  - Users can be blocked by moderators.
  - Message can be edited as long as no reply exists.
- **Search:**
  - Full-text search for MySQL.
- **Profile:**
  - `u_profile_xxx` for additional profile fields.
- **Interface:**
  - Bold, Italic and Underline buttons also available for Mozilla.
  - Preview with dedicated button instead of checkbox.

### 🔧 Improvements

- `getMetaType(string|number)` for DB fields.
- Switched to `pconnect`.
- Message body parser optimised (formatting time reduced by approx. 60%).
- Config area in template restructured (user, board, skin).
- Various template tags moved to appropriate sections (signature now in config/user).
- Per-class error templates (`error-classname`) can override the generic template.
- Webmaster e-mail in config.
- Root folder for skins renamed to `pxmboard` (no longer skin-dependent).
- Skin directory stored in config.
- Parse URL/Style in general config for private messages.
- `disable-output-escaping` in `value-of` used for banners etc.
- `usedirectregistration` and `guestpost` switches in config section for templates.
- Session ID passed as config to skins.

### 🐛 Bug Fixes

- `]]>` in a post caused errors → use `htmlspecialchars` instead of CDATA.
- Messages for a thread are now in the subarray `msg`, no longer directly indexed by ID.
- `brdid` and `thrdid` do not necessarily match (user supplies `thrdid` from a locked forum).
- Parent ID and thread ID do not necessarily match on insert.
- DOM XML represents empty elements incorrectly (textarea in registration, JavaScript etc.) → workaround: `<xsl:comment/>` inserted.

---

## 2003-01-03

### 🐛 Bug Fixes

- Font size is no longer evaluated by user classes.

### 🔧 Improvements

- More user states introduced. ⚠️ **DB migration:** `u_active` renamed to `u_state`; value `0` becomes `2`.
- New method in config: `getUserStates()` (stub implementation).
- User admin tool revised to reflect new states.
- `cUserIndex.class` removed.
- `userprofile.xsl` updated: `user/state` instead of `user/active`.

---

## 2002-11-20

### 🐛 Bug Fixes

- `config/nick` moved to `config/user/nick` to avoid collision with user search.
- Cookie flag change in settings no longer produces an error message.
- PXM skin: user list active flag is now checked correctly.

---

## 2002-10-11

### 🔧 Improvements

- `brdid` passed to `movethread` (moderators can no longer fake `brdid`).
- `u_login` renamed to `u_active`; flag is set when a user is activated. ⚠️ **DB migration:** `ALTER TABLE px_user CHANGE u_login u_active TINYINT(3) UNSIGNED DEFAULT '0' NOT NULL`
- Display of whether a user is active is available in all templates (stub in messages).

---

## 2002-10-09

### ✨ New Features

- Moderators can move threads.
- Messages can be marked as important (new DB column `t_important` in `px_thread`).

### 🔧 Improvements

- Font size removed from config (can be manipulated in the browser).
- `cUserConfig` object stored directly in session.
- Users are shown in the online list when they use quick post.

---

## 2002-09-18

### 🐛 Bug Fixes

- Banner display corrected.

---

## 2002-09-14

### ✨ New Features

- Banners in user search, online list, message search and message list.

### 🔧 Improvements

- `brdname` in config (XML).
- Admin "clean db" function no longer available for PostgreSQL.
- Various JS additions for skins.
- Output compression removed → use `zlib.output_compression` in `php.ini` if needed.

---

## 2002-08-31

### 🔧 Improvements

- `cDBBuilder` renamed to `cDBFactory`; classes updated accordingly.

---

## 2002-08-25

### 🔧 Improvements

- `getDBType()` in `cDB` and derived classes.

---

## 2002-08-16

### 🔧 Improvements

- Parameters `thrdid` and `msgid` can be passed with `mode=brd` → forwarded to `config/thrdid` & `config/msgid`.

---

## 2002-08-10

### ✨ New Features

- **PostgreSQL support:**
  - DB abstraction with builder.
  - SQL LIMIT abstracted.
  - Tables renamed for SQL compatibility (`px_` prefix).
  - `ORDER BY RAND()` replaced by PHP `rand()` (PostgreSQL compatibility).
  - `getInsertId()` receives `table` and `column` parameters.

### 🔧 Improvements

- Thread ID on confirmation pages when saving a message.
- `cTemplate` detects the installed PHP version and addresses the XSLT extension accordingly.

### 🐛 Bug Fixes

- Timezone: user and server settings no longer overwrite each other.
- Board list display corrected after opening or closing a board.

---

## 2002-07-21

### ✨ New Features

- Nickname of the logged-in user available in all templates (`config/nick`).
- Newest forum member displayed in `brdidx` (`newestmember/user`).
- Text replacement optional → new DB columns:
  - `ALTER TABLE user ADD u_repltext tinyint(3) unsigned NOT NULL default '1';`
  - `ALTER TABLE board ADD b_repltext tinyint(3) unsigned NOT NULL default '1';`

### 🔧 Improvements

- `brdidx` template moved to subfolder `boards` (`board` → `boards/board`).

---

## 2002-06-28

### ⚡ Performance

- Thread assembly speed improved by approximately factor 10 (`cThread.class`).

---

## 2002-06-19

### ✨ New Features

- Marking of new messages.

---

## 2002-06-07

### ✨ New Features

- `u_highlight` for user list and online list.

---

## 2002-05-31

### ✨ New Features

- Visible flag in user table (hides user from "Who's online" list).
- Visible/not-visible evaluation in "Who's online".

---

## 2002-05-09

### ✨ New Features

- Auto-close threads after a configured number of messages.
- Indicator for new private messages (new `read` column).
- Unbuffered `mysql_query` for threads.

---

## 2002-05-05

### 🔧 Improvements

- Index added for online time column in user table.

---

## 2002-05-04

### ⚡ Performance

- Pass-by-reference used wherever possible.
- `foreach` replaced by `while`/`list`/`each` where necessary (no memory copy required → thread index).

---

## 2002-05-03

### 🐛 Bug Fixes

- Blocked users who are still logged in can no longer post.
- Changing settings after a password change now works correctly (affects profile, config and password changes).

---

## 2002-05-01

### 🐛 Bug Fixes

- `]]>` and `<![CDATA[` accumulated during text replacement.
- `mode` in error template (`pxmboard`).

---

## 2002-04-02

### 🔧 Improvements

- `nl2br` (e.g. banners) and `<pre>` (profile) for rendering line breaks.

### 🐛 Bug Fixes

- Text replacement no longer replaces content inside link areas.

---

## 2002-03-29

### 🐛 Bug Fixes

- Month selection (`main-admin`).
- `getRandomBanner()` with time limit (`cBanner`).

---

## 2002-03-21

### 🔧 Improvements

- Link to mail host in admin tool.

---

## 2002-03-18

### ✨ New Features

- IP display for messages (admin and moderator).
- Preview when posting.

---

## 2002-02-24

### 🔧 Improvements

- No CDATA for banners.

---

## 2002-02-17

### 🐛 Bug Fixes

- `cMessage` (`/` for image tag).
- `cTemplate` (CDATA section made optional).

### 🔧 Improvements

- CDATA for `getTextreplacement()`.

---

## 2002-02-14

### 🔧 Improvements

- Message body and IMG in thread → entities now replaced by XSL.
- XSL `copy-of` instead of `value-of` and `disable-output-escaping` for body and IMG.
- Compatible with new Sablotron.

### 🐛 Bug Fixes

- Unclosed quote prefix at end of message.

---

## 2002-02-08

### ✨ New Features

- Bold/Italic/Underline/Link/Image buttons for IE users when posting in skin.
- Quote character parameter in config (DB entry in `skin` table no longer needed).
- Quote subject parameter in config.

### 🔧 Improvements

- Bad word replacement when posting (more efficient).
- Bad words replaced in subjects.

### 🐛 Bug Fixes

- Moving threads.

---

## 2002-02-01

### ✨ New Features

- Login stored in cookie.

### 🔧 Improvements

- `setdata()` now used for user registration.

---

## 2002-01-31

### 🔧 Improvements

- `decode` removed.

---

## 2002-01-29

### 🔧 Improvements

- `updonlinetime` removed (DB column `c_updonltime` in `configuration` table dropped; now controlled by `onlineTime`).

---

## 2002-01-28

### 🔧 Improvements

- `getArrFormVar()` revised (`pxmboard`).

---

## 2002-01-26

### ✨ New Features

- Start date for banner rotation (DB column `ba_start` in `banner` table; index on `ba_boardid`).
- Thread moving.

---

## 2002-01-24

### ✨ New Features

- Unique private mail optional (DB column `c_uniquemail` in `configuration` table).
- Banner rotation moved to dedicated class `cBanner.php`.
- Banner expiry date and max views (DB columns `ba_expiration`, `ba_views`, `ba_maxviews` in `banner` table).

---

## 2002-01-14

### ✨ New Features

- Switch for banner rotation in `cConfig`.

### 🔧 Improvements

- `main-admin` updated.

---

## 2002-01-13

### ✨ New Features

- Banner rotation.

### 🐛 Bug Fixes

- `cMessage` (Italic/Bold/Underline open/close).

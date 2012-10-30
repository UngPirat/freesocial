<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * utilities for sending email
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Mail
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Zach Copley <zach@status.net>
 * @author    Robin Millette <millette@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once 'Mail.php';

/**
 * return the configured mail backend
 *
 * Uses the $config array to make a mail backend. Cached so it is safe to call
 * more than once.
 *
 * @return Mail backend
 */
function mail_backend()
{
    static $backend = null;

    if (!$backend) {
        $backend = Mail::factory(common_config('mail', 'backend'),
                                 (common_config('mail', 'params')) ?
                                 common_config('mail', 'params') :
                                 array());
        if (PEAR::isError($backend)) {
            common_server_error($backend->getMessage(), 500);
        }
    }
    return $backend;
}

/**
 * send an email to one or more recipients
 *
 * @param array  $recipients array of strings with email addresses of recipients
 * @param array  $headers    array mapping strings to strings for email headers
 * @param string $body       body of the email
 *
 * @return boolean success flag
 */
function mail_send($recipients, $headers, $body)
{
    try {
        // XXX: use Mail_Queue... maybe
        $backend = mail_backend();

        if (!isset($headers['Content-Type'])) {
           $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        }

        assert($backend); // throws an error if it's bad
        $sent = $backend->send($recipients, $headers, $body);
        return true;
    } catch (PEAR_Exception $e) {
        common_log(
            LOG_ERR,
            "Unable to send email - '{$e->getMessage()}'. "
            . 'Is your mail subsystem set up correctly?'
        );
        return false;
    }
}

/**
 * returns the configured mail domain
 *
 * Defaults to the server name.
 *
 * @return string mail domain, suitable for making email addresses.
 */
function mail_domain()
{
    $maildomain = common_config('mail', 'domain');
    if (!$maildomain) {
        $maildomain = common_config('site', 'server');
    }
    return $maildomain;
}

/**
 * returns a good address for sending email from this server
 *
 * Uses either the configured value or a faked-up value made
 * from the mail domain.
 *
 * @return string notify from address
 */
function mail_notify_from()
{
    $notifyfrom = common_config('mail', 'notifyfrom');

    if (!$notifyfrom) {

        $domain = mail_domain();

        $notifyfrom = '"'. str_replace('"', '\\"', common_config('site', 'name')) .'" <noreply@'.$domain.'>';
    }

    return $notifyfrom;
}

/**
 * sends email to a user
 *
 * @param User   &$user   user to send email to
 * @param string $subject subject of the email
 * @param string $body    body of the email
 * @param array  $headers optional list of email headers
 * @param string $address optional specification of email address
 *
 * @return boolean success flag
 */
function mail_to_user(&$user, $subject, $body, $headers=array(), $address=null)
{
    if (!$address) {
        $address = $user->email;
    }

    $recipients = $address;
    $profile    = $user->getProfile();

    $headers['From']    = mail_notify_from();
    $headers['To']      = $profile->getBestName() . ' <' . $address . '>';
    $headers['Subject'] = $subject;

    return mail_send($recipients, $headers, $body);
}

/**
 * Send an email to confirm a user's control of an email address
 *
 * @param User   $user     User claiming the email address
 * @param string $code     Confirmation code
 * @param string $nickname Nickname of user
 * @param string $address  email address to confirm
 *
 * @see common_confirmation_code()
 *
 * @return success flag
 */
function mail_confirm_address($user, $code, $nickname, $address, $url=null)
{
    if (empty($url)) {
        $url = common_local_url('confirmaddress', array('code' => $code));
    }

    // TRANS: Subject for address confirmation email.
    $subject = _('Email address confirmation');

    // TRANS: Body for address confirmation email.
    // TRANS: %1$s is the addressed user's nickname, %2$s is the StatusNet sitename,
    // TRANS: %3$s is the URL to confirm at.
    $body = sprintf(_("Hey, %1\$s.\n\n".
                      "Someone just entered this email address on %2\$s.\n\n" .
                      "If it was you, and you want to confirm your entry, ".
                      "use the URL below:\n\n\t%3\$s\n\n" .
                      "If not, just ignore this message.\n\n".
                      "Thanks for your time, \n%2\$s\n"),
                    $nickname,
                    common_config('site', 'name'),
                    $url);

    $headers = array();

    return mail_to_user($user, $subject, $body, $headers, $address);
}

/**
 * notify a user of subscription by another user
 *
 * This is just a wrapper around the profile-based version.
 *
 * @param User $listenee user who is being subscribed to
 * @param User $listener user who is subscribing
 *
 * @see mail_subscribe_notify_profile()
 *
 * @return void
 */
function mail_subscribe_notify($listenee, $listener)
{
    $other = $listener->getProfile();
    mail_subscribe_notify_profile($listenee, $other);
}

/**
 * notify a user of subscription by a profile (remote or local)
 *
 * This function checks to see if the listenee has an email
 * address and wants subscription notices.
 *
 * @param User    $listenee user who's being subscribed to
 * @param Profile $other    profile of person who's listening
 *
 * @return void
 */
function mail_subscribe_notify_profile($listenee, $other)
{
    if ($other->hasRight(Right::EMAILONSUBSCRIBE) &&
        $listenee->email && $listenee->emailnotifysub) {

        $profile = $listenee->getProfile();

        $name = $profile->getBestName();

        $long_name = ($other->fullname) ?
          ($other->fullname . ' (' . $other->nickname . ')') : $other->nickname;

        $recipients = $listenee->email;

        // use the recipient's localization
        common_switch_locale($listenee->language);

        $headers = _mail_prepare_headers('subscribe', $listenee->nickname, $other->nickname);
        $headers['From']    = mail_notify_from();
        $headers['To']      = $name . ' <' . $listenee->email . '>';
        // TRANS: Subject of new-subscriber notification e-mail.
        // TRANS: %1$s is the subscribing user's nickname, %2$s is the StatusNet sitename.
        $headers['Subject'] = sprintf(_('%1$s is now following you on %2$s.'),
                                      $other->getBestName(),
                                      common_config('site', 'name'));

        // TRANS: Main body of new-subscriber notification e-mail.
        // TRANS: %1$s is the subscriber's long name, %2$s is the StatusNet sitename.
        $body = sprintf(_('%1$s is now following you on %2$s.'),
                        $long_name,
                        common_config('site', 'name')) .
                mail_profile_block($other) .
                mail_footer_block();

        // reset localization
        common_switch_locale();
        mail_send($recipients, $headers, $body);
    }
}

function mail_subscribe_pending_notify_profile($listenee, $other)
{
    if ($other->hasRight(Right::EMAILONSUBSCRIBE) &&
        $listenee->email && $listenee->emailnotifysub) {

        $profile = $listenee->getProfile();

        $name = $profile->getBestName();

        $long_name = ($other->fullname) ?
          ($other->fullname . ' (' . $other->nickname . ')') : $other->nickname;

        $recipients = $listenee->email;

        // use the recipient's localization
        common_switch_locale($listenee->language);

        $headers = _mail_prepare_headers('subscribe', $listenee->nickname, $other->nickname);
        $headers['From']    = mail_notify_from();
        $headers['To']      = $name . ' <' . $listenee->email . '>';
        // TRANS: Subject of pending new-subscriber notification e-mail.
        // TRANS: %1$s is the subscribing user's nickname, %2$s is the StatusNet sitename.
        $headers['Subject'] = sprintf(_('%1$s would like to listen to '.
                                        'your notices on %2$s.'),
                                      $other->getBestName(),
                                      common_config('site', 'name'));

        // TRANS: Main body of pending new-subscriber notification e-mail.
        // TRANS: %1$s is the subscriber's long name, %2$s is the StatusNet sitename.
        $body = sprintf(_('%1$s would like to listen to your notices on %2$s. ' .
                          'You may approve or reject their subscription at %3$s'),
                        $long_name,
                        common_config('site', 'name'),
                        common_local_url('subqueue', array('nickname' => $listenee->nickname))) .
                mail_profile_block($other) .
                mail_footer_block();

        // reset localization
        common_switch_locale();
        mail_send($recipients, $headers, $body);
    }
}

function mail_footer_block()
{
    // TRANS: Common footer block for StatusNet notification emails.
    // TRANS: %1$s is the StatusNet sitename,
    // TRANS: %2$s is a link to the addressed user's e-mail settings.
    return "\n\n" . sprintf(_('Faithfully yours,'.
                              "\n".'%1$s.'."\n\n".
                              "----\n".
                              "Change your email address or ".
                              "notification options at ".'%2$s'),
                            common_config('site', 'name'),
                            common_local_url('emailsettings')) . "\n";
}

/**
 * Format a block of profile info for a plaintext notification email.
 *
 * @param Profile $profile
 * @return string
 */
function mail_profile_block($profile)
{
    // TRANS: Layout for
    // TRANS: %1$s is the subscriber's profile URL, %2$s is the subscriber's location (or empty)
    // TRANS: %3$s is the subscriber's homepage URL (or empty), %4%s is the subscriber's description (or empty)
    $out = array();
    $out[] = "";
    $out[] = "";
    // TRANS: Profile info line in notification e-mail.
    // TRANS: %s is a URL.
    $out[] = sprintf(_("Profile: %s"), $profile->profileurl);
    if ($profile->location) {
        // TRANS: Profile info line in notification e-mail.
        // TRANS: %s is a location.
        $out[] = sprintf(_("Location: %s"), $profile->location);
    }
    if ($profile->homepage) {
        // TRANS: Profile info line in notification e-mail.
        // TRANS: %s is a homepage.
        $out[] = sprintf(_("Homepage: %s"), $profile->homepage);
    }
    if ($profile->description) {
        // TRANS: Profile info line in notification e-mail.
        // TRANS: %s is a user description.
        $out[] = sprintf(_("Description: %s"), $profile->description);
    }

    $blocklink = common_local_url('block', array('profileid' => $profile->id));
    // This'll let ModPlus add the remote profile info so it's possible
    // to block remote users directly...
    Event::handle('MailProfileInfoBlockLink', array($profile, &$blocklink));

    // TRANS: This is a paragraph in a new-subscriber e-mail.
    // TRANS: %s is a URL where the subscriber can be reported as abusive.
    $out[] = sprintf(_('If you believe this account is being used abusively, ' .
                       'you can block them from your subscribers list and ' .
                       'report as spam to site administrators at %s.'),
                     $blocklink);
    $out[] = "";

    return implode("\n", $out);
}

/**
 * notify a user of their new incoming email address
 *
 * User's email and incoming fields should already be updated.
 *
 * @param User $user user with the new address
 *
 * @return void
 */
function mail_new_incoming_notify($user)
{
    $profile = $user->getProfile();

    $name = $profile->getBestName();

    $headers['From']    = $user->incomingemail;
    $headers['To']      = $name . ' <' . $user->email . '>';
    // TRANS: Subject of notification mail for new posting email address.
    // TRANS: %s is the StatusNet sitename.
    $headers['Subject'] = sprintf(_('New email address for posting to %s'),
                                  common_config('site', 'name'));

    // TRANS: Body of notification mail for new posting email address.
    // TRANS: %1$s is the StatusNet sitename, %2$s is the e-mail address to send
    // TRANS: to to post by e-mail, %3$s is a URL to more instructions.
    $body = sprintf(_("You have a new posting address on %1\$s.\n\n".
                      "Send email to %2\$s to post new messages.\n\n".
                      "More email instructions at %3\$s."),
                    common_config('site', 'name'),
                    $user->incomingemail,
                    common_local_url('doc', array('title' => 'email'))) .
            mail_footer_block();

    mail_send($user->email, $headers, $body);
}

/**
 * generate a new address for incoming messages
 *
 * @todo check the database for uniqueness
 *
 * @return string new email address for incoming messages
 */
function mail_new_incoming_address()
{
    $prefix = common_confirmation_code(64);
    $suffix = mail_domain();
    return $prefix . '@' . $suffix;
}

/**
 * Notify a user that one of their notices has been chosen as a 'fave'
 *
 * Doesn't check that the user has an email address nor if they
 * want to receive notification of faves. Maybe this happens higher
 * up the stack...?
 *
 * @param User   $other  The user whose notice was faved
 * @param User   $user   The user who faved the notice
 * @param Notice $notice The notice that was faved
 *
 * @return void
 */
function mail_notify_fave($other, $user, $notice)
{
    if (!$user->hasRight(Right::EMAILONFAVE)) {
        return;
    }

    $profile = $user->getProfile();
    if ($other->hasBlocked($profile)) {
        // If the author has blocked us, don't spam them with a notification.
        return;
    }

    $bestname = $profile->getBestName();

    common_switch_locale($other->language);

    // TRANS: Subject for favorite notification e-mail.
    // TRANS: %1$s is the adding user's long name, %2$s is the adding user's nickname.
    $subject = sprintf(_('%1$s (@%2$s) added your notice as a favorite'), $bestname, $user->nickname);

    // TRANS: Body for favorite notification e-mail.
    // TRANS: %1$s is the adding user's long name, $2$s is the date the notice was created,
    // TRANS: %3$s is a URL to the faved notice, %4$s is the faved notice text,
    // TRANS: %5$s is a URL to all faves of the adding user, %6$s is the StatusNet sitename,
    // TRANS: %7$s is the adding user's nickname.
    $body = sprintf(_("%1\$s (@%7\$s) just added your notice from %2\$s".
                      " as one of their favorites.\n\n" .
                      "The URL of your notice is:\n\n" .
                      "%3\$s\n\n" .
                      "The text of your notice is:\n\n" .
                      "%4\$s\n\n" .
                      "You can see the list of %1\$s's favorites here:\n\n" .
                      "%5\$s"),
                    $bestname,
                    common_exact_date($notice->created),
                    common_local_url('notice', array('id' => $notice->id)),
                    $notice->content,
                    common_local_url('favorites',
                                     array('nickname' => $user->nickname)),
                    common_config('site', 'name'),
                    $user->nickname) .
            mail_footer_block();

    $headers = _mail_prepare_headers('fave', $other->nickname, $user->nickname);

    common_switch_locale();
    mail_to_user($other, $subject, $body, $headers);
}

/**
 * Notify a user that they have received an "attn:" message AKA "@-reply"
 *
 * @param User   $user   The user who recevied the notice
 * @param Notice $notice The notice that was sent
 *
 * @return void
 */
function mail_notify_attn($user, $notice)
{
    if (!$user->email || !$user->emailnotifyattn) {
        return;
    }

    $sender = $notice->getProfile();

    if ($sender->id == $user->id) {
        return;
    }

    if (!$sender->hasRight(Right::EMAILONREPLY)) {
        return;
    }

    if ($user->hasBlocked($sender)) {
        // If the author has blocked us, don't spam them with a notification.
        return;
    }

    $bestname = $sender->getBestName();

    common_switch_locale($user->language);

    if ($notice->hasConversation()) {
        $conversationUrl = common_local_url('conversation',
                         array('id' => $notice->conversation)).'#notice-'.$notice->id;
        // TRANS: Line in @-reply notification e-mail. %s is conversation URL.
        $conversationEmailText = sprintf(_("The full conversation can be read here:\n\n".
                                           "\t%s"), $conversationUrl) . "\n\n";
    } else {
        $conversationEmailText = '';
    }

    // TRANS: E-mail subject for notice notification.
    // TRANS: %1$s is the sending user's long name, %2$s is the adding user's nickname.
    $subject = sprintf(_('%1$s (@%2$s) sent a notice to your attention'), $bestname, $sender->nickname);

        // TRANS: Body of @-reply notification e-mail.
        // TRANS: %1$s is the sending user's name, $2$s is the StatusNet sitename,
        // TRANS: %3$s is a URL to the notice, %4$s is the notice text,
        // TRANS: %5$s is the text "The full conversation can be read here:" and a URL to the full conversion if it exists (otherwise empty),
        // TRANS: %6$s is a URL to reply to the notice, %7$s is a URL to all @-mentions for the addressed user,
        $body = sprintf(_("%1\$s just sent a notice to your attention (an '@-reply') on %2\$s.\n\n".
                      "The notice is here:\n\n".
                      "\t%3\$s\n\n" .
                      "It reads:\n\n".
                      "\t%4\$s\n\n" .
                      "%5\$s" .
                      "You can reply back here:\n\n".
                      "\t%6\$s\n\n" .
                      "The list of all @-mentions for you here:\n\n" .
                      "%7\$s"),
                    $sender->getFancyName(),//%1
                    common_config('site', 'name'),//%2
                    common_local_url('notice', array('id' => $notice->id)),//%3
                    $notice->content,//%4
                    $conversationEmailText,//%5
                    common_local_url('newnotice',
                                     array('replyto' => $sender->nickname, 'inreplyto' => $notice->id)),//%6
                    common_local_url('mentions',
                                     array('nickname' => $user->nickname))) . //%7
                mail_footer_block();
    $headers = _mail_prepare_headers('mention', $user->nickname, $sender->nickname);
	if (!empty($user->incomingemail)) {
	    $headers['Reply-To'] = $user->incomingemail;
	}

    common_switch_locale();
    mail_to_user($user, $subject, $body, $headers);
}

/**
 * Prepare the common mail headers used in notification emails
 *
 * @param string $msg_type type of message being sent to the user
 * @param string $to       nickname of the receipient
 * @param string $from     nickname of the user triggering the notification
 *
 * @return array list of mail headers to include in the message
 */
function _mail_prepare_headers($msg_type, $to, $from)
{
    $headers = array(
        'X-StatusNet-MessageType' => $msg_type,
        'X-StatusNet-TargetUser'  => $to,
        'X-StatusNet-SourceUser'  => $from,
        'X-StatusNet-Domain'      => common_config('site', 'server')
    );

    return $headers;
}

/**
 * Send notification emails to group administrator.
 *
 * @param User_group $group
 * @param Profile $joiner
 */
function mail_notify_group_join($group, $joiner)
{
    // This returns a Profile query...
    $admin = $group->getAdmins();
    while ($admin->fetch()) {
        // We need a local user for email notifications...
        $adminUser = User::staticGet('id', $admin->id);
        // @fixme check for email preference?
        if ($adminUser && $adminUser->email) {
            // use the recipient's localization
            common_switch_locale($adminUser->language);

            $headers = _mail_prepare_headers('join', $admin->nickname, $joiner->nickname);
            $headers['From']    = mail_notify_from();
            $headers['To']      = $admin->getBestName() . ' <' . $adminUser->email . '>';
            // TRANS: Subject of group join notification e-mail.
            // TRANS: %1$s is the joining user's nickname, %2$s is the group name, and %3$s is the StatusNet sitename.
            $headers['Subject'] = sprintf(_('%1$s has joined '.
                                            'your group %2$s on %3$s'),
                                          $joiner->getBestName(),
                                          $group->getBestName(),
                                          common_config('site', 'name'));

            // TRANS: Main body of group join notification e-mail.
            // TRANS: %1$s is the subscriber's long name, %2$s is the group name, and %3$s is the StatusNet sitename,
            // TRANS: %4$s is a block of profile info about the subscriber.
            // TRANS: %5$s is a link to the addressed user's e-mail settings.
            $body = sprintf(_('%1$s has joined your group %2$s on %3$s.'),
                            $joiner->getFancyName(),
                            $group->getFancyName(),
                            common_config('site', 'name')) .
                    mail_profile_block($joiner) .
                    mail_footer_block();

            // reset localization
            common_switch_locale();
            mail_send($adminUser->email, $headers, $body);
        }
    }
}


/**
 * Send notification emails to group administrator.
 *
 * @param User_group $group
 * @param Profile $joiner
 */
function mail_notify_group_join_pending($group, $joiner)
{
    $admin = $group->getAdmins();
    while ($admin->fetch()) {
        // We need a local user for email notifications...
        $adminUser = User::staticGet('id', $admin->id);
        // @fixme check for email preference?
        if ($adminUser && $adminUser->email) {
            // use the recipient's localization
            common_switch_locale($adminUser->language);

            $headers = _mail_prepare_headers('join', $admin->nickname, $joiner->nickname);
            $headers['From']    = mail_notify_from();
            $headers['To']      = $admin->getBestName() . ' <' . $adminUser->email . '>';
            // TRANS: Subject of pending group join request notification e-mail.
            // TRANS: %1$s is the joining user's nickname, %2$s is the group name, and %3$s is the StatusNet sitename.
            $headers['Subject'] = sprintf(_('%1$s wants to join your group %2$s on %3$s.'),
                                          $joiner->getBestName(),
                                          $group->getBestName(),
                                          common_config('site', 'name'));

            // TRANS: Main body of pending group join request notification e-mail.
            // TRANS: %1$s is the subscriber's long name, %2$s is the group name, and %3$s is the StatusNet sitename,
            // TRANS: %4$s is the URL to the moderation queue page.
            $body = sprintf(_('%1$s would like to join your group %2$s on %3$s. ' .
                              'You may approve or reject their group membership at %4$s'),
                            $joiner->getFancyName(),
                            $group->getFancyName(),
                            common_config('site', 'name'),
                            common_local_url('groupqueue', array('nickname' => $group->nickname))) .
                    mail_profile_block($joiner) .
                    mail_footer_block();

            // reset localization
            common_switch_locale();
            mail_send($adminUser->email, $headers, $body);
        }
    }
}

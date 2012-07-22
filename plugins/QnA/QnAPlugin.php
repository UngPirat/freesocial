<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2011, StatusNet, Inc.
 *
 * Microapp plugin for Questions and Answers
 *
 * PHP version 5
 *
 * This program is free software: you can redistribute it and/or modify
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
 * @category  QnA
 * @package   StatusNet
 * @author    Zach Copley <zach@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

/**
 * Question and Answer plugin
 *
 * @category  Plugin
 * @package   StatusNet
 * @author    Zach Copley <zach@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */
class QnAPlugin extends MicroAppPlugin
{
    /**
     * Set up our tables (question and answer)
     *
     * @see Schema
     * @see ColumnDef
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */
    function onCheckSchema()
    {
        $schema = Schema::get();

        $schema->ensureTable('qna_question', QnA_Question::schemaDef());
        $schema->ensureTable('qna_answer', QnA_Answer::schemaDef());
        $schema->ensureTable('qna_vote', QnA_Vote::schemaDef());

        return true;
    }

    /**
     * Load related modules when needed
     *
     * @param string $cls Name of the class to be loaded
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */
    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'QnanewquestionAction':
        case 'QnanewanswerAction':
        case 'QnashowquestionAction':
        case 'QnaclosequestionAction':
        case 'QnashowanswerAction':
        case 'QnareviseanswerAction':
        case 'QnavoteAction':
            include_once $dir . '/actions/'
                . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        case 'QnanewquestionForm':
        case 'QnashowquestionForm':
        case 'QnanewanswerForm':
        case 'QnashowanswerForm':
        case 'QnareviseanswerForm':
        case 'QnavoteForm':
            include_once $dir . '/lib/' . strtolower($cls).'.php';
            break;
        case 'QnA_Question':
        case 'QnA_Answer':
        case 'QnA_Vote':
            include_once $dir . '/classes/' . $cls.'.php';
            return false;
            break;
        default:
            return true;
        }
    }

    /**
     * Map URLs to actions
     *
     * @param Net_URL_Mapper $m path-to-action mapper
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */

    function onRouterInitialized($m)
    {
        $UUIDregex = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

        $m->connect(
            'main/qna/newquestion',
            array('action' => 'qnanewquestion')
        );
        $m->connect(
            'answer/qna/closequestion',
            array('action' => 'qnaclosequestion')
        );
        $m->connect(
            'main/qna/newanswer',
            array('action' => 'qnanewanswer')
        );
        $m->connect(
            'main/qna/reviseanswer',
            array('action' => 'qnareviseanswer')
        );
        $m->connect(
            'question/vote/:id',
            array('action' => 'qnavote', 'type' => 'question'),
            array('id' => $UUIDregex)
        );
        $m->connect(
            'question/:id',
            array('action' => 'qnashowquestion'),
            array('id' => $UUIDregex)
        );
        $m->connect(
            'answer/vote/:id',
            array('action' => 'qnavote', 'type' => 'answer'),
            array('id' => $UUIDregex)
        );
        $m->connect(
            'answer/:id',
            array('action' => 'qnashowanswer'),
            array('id' => $UUIDregex)
        );

        return true;
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array(
            'name'        => 'QnA',
            'version'     => STATUSNET_VERSION,
            'author'      => 'Zach Copley',
            'homepage'    => 'http://status.net/wiki/Plugin:QnA',
            'description' =>
             // TRANS: Plugin description.
             _m('Question and Answers micro-app.')
        );
        return true;
    }

    function appTitle() {
        // TRANS: Application title.
        return _m('TITLE','Question');
    }

    function tag() {
        return 'question';
    }

    function types() {
        return array(
            QnA_Question::OBJECT_TYPE,
            QnA_Answer::OBJECT_TYPE
        );
    }

    /**
     * Given a parsed ActivityStreams activity, save it into a notice
     * and other data structures.
     *
     * @param Activity $activity
     * @param Profile $actor
     * @param array $options=array()
     *
     * @return Notice the resulting notice
     */
    function saveNoticeFromActivity($activity, $actor, $options=array())
    {
        if (count($activity->objects) != 1) {
            // TRANS: Exception thrown when there are too many activity objects.
            throw new Exception(_m('Too many activity objects.'));
        }

        $questionObj = $activity->objects[0];

        if ($questionObj->type != QnA_Question::OBJECT_TYPE) {
            // TRANS: Exception thrown when an incorrect object type is encountered.
            throw new Exception(_m('Wrong type for object.'));
        }

        $notice = null;

        switch ($activity->verb) {
        case ActivityVerb::POST:
            $notice = QnA_Question::saveNew(
                $actor,
                $questionObj->title,
                $questionObj->summary,
                $options
            );
            break;
        case QnA_Answer::OBJECT_TYPE:
            $question = QnA_Question::staticGet('uri', $questionObj->id);
            if (empty($question)) {
                // FIXME: save the question
                // TRANS: Exception thrown when answering a non-existing question.
                throw new Exception(_m('Answer to unknown question.'));
            }
            $notice = QnA_Answer::saveNew($actor, $question, $options);
            break;
        default:
            // TRANS: Exception thrown when an object type is encountered that cannot be handled.
            throw new Exception(_m('Unknown object type.'));
        }

        return $notice;
    }

    /**
     * Turn a Notice into an activity object
     *
     * @param Notice $notice
     *
     * @return ActivityObject
     */

    function activityObjectFromNotice($notice)
    {
        $question = null;

        switch ($notice->object_type) {
        case QnA_Question::OBJECT_TYPE:
            $question = QnA_Question::fromNotice($notice);
            break;
        case QnA_Answer::OBJECT_TYPE:
            $answer   = QnA_Answer::fromNotice($notice);
            $question = $answer->getQuestion();
            break;
        }

        if (empty($question)) {
            // TRANS: Exception thrown when an object type is encountered that cannot be handled.
            throw new Exception(_m('Unknown object type.'));
        }

        $notice = $question->getNotice();

        if (empty($notice)) {
            // TRANS: Exception thrown when requesting a non-existing question notice.
            throw new Exception(_m('Unknown question notice.'));
        }

        $obj = new ActivityObject();

        $obj->id      = $question->uri;
        $obj->type    = QnA_Question::OBJECT_TYPE;
        $obj->title   = $question->title;
        $obj->link    = $notice->bestUrl();

        // XXX: probably need other stuff here

        return $obj;
    }

    /**
     * Output our CSS class for QnA notice list elements
     *
     * @param NoticeListItem $nli The item being shown
     *
     * @return boolean hook value
     */

    function onStartOpenNoticeListItemElement($nli)
    {
        $type = $nli->notice->object_type;

        switch($type)
        {
        case QnA_Question::OBJECT_TYPE:
            $id = (empty($nli->repeat)) ? $nli->notice->id : $nli->repeat->id;
            $class = 'hentry notice question';
            if ($nli->notice->scope != 0 && $nli->notice->scope != 1) {
                $class .= ' limited-scope';
            }

            $question = QnA_Question::staticGet('uri', $nli->notice->uri);

            if (!empty($question->closed)) {
                $class .= ' closed';
            }

            $nli->out->elementStart(
                'li', array(
                    'class' => $class,
                    'id'    => 'notice-' . $id
                )
            );
            Event::handle('EndOpenNoticeListItemElement', array($nli));
            return false;
            break;
        case QnA_Answer::OBJECT_TYPE:
            $id = (empty($nli->repeat)) ? $nli->notice->id : $nli->repeat->id;

            $cls = array('hentry', 'notice', 'answer');

            $answer = QnA_Answer::staticGet('uri', $nli->notice->uri);

            if (!empty($answer) && !empty($answer->best)) {
                $cls[] = 'best';
            }

            $nli->out->elementStart(
                'li',
                array(
                    'class' => implode(' ', $cls),
                    'id'    => 'notice-' . $id
                )
            );
            Event::handle('EndOpenNoticeListItemElement', array($nli));
            return false;
            break;
        default:
            return true;
        }

        return true;
    }

    /**
     * Custom HTML output for our notices
     *
     * @param Notice $notice
     * @param HTMLOutputter $out
     */
    function showNotice($notice, $out)
    {
        switch ($notice->object_type) {
        case QnA_Question::OBJECT_TYPE:
            return $this->showNoticeQuestion($notice, $out);
        case QnA_Answer::OBJECT_TYPE:
            return $this->showNoticeAnswer($notice, $out);
        default:
            throw new Exception(
                // TRANS: Exception thrown when performing an unexpected action on a question.
                // TRANS: %s is the unpexpected object type.
                sprintf(_m('Unexpected type for QnA plugin: %s.'),
                        $notice->object_type
                )
            );
        }
    }

    function showNoticeQuestion($notice, $out)
    {
        $user = common_current_user();

        // @hack we want regular rendering, then just add stuff after that
        $nli = new NoticeListItem($notice, $out);
        $nli->showNotice();

        $out->elementStart('div', array('class' => 'entry-content question-description'));

        $question = QnA_Question::getByNotice($notice);

        if (!empty($question)) {

            $form = new QnashowquestionForm($out, $question);
            $form->show();

        } else {
            // TRANS: Error message displayed when question data is not present.
            $out->text(_m('Question data is missing.'));
        }
        $out->elementEnd('div');

        // @fixme
        $out->elementStart('div', array('class' => 'entry-content'));
    }

    /**
     * Output the HTML for this kind of object in a list
     *
     * @param NoticeListItem $nli The list item being shown.
     *
     * @return boolean hook value
     *
     * @todo FIXME: WARNING WARNING WARNING this closes a 'div' that is implicitly opened in BookmarkPlugin's showNotice implementation
     */
    function onStartShowNoticeItem($nli)
    {
        if (!$this->isMyNotice($nli->notice)) {
            return true;
        }

        $out = $nli->out;
        $notice = $nli->notice;

        $this->showNotice($notice, $out);

        $nli->showNoticeLink();
        $nli->showNoticeSource();
        $nli->showNoticeLocation();
        $nli->showContext();
        $nli->showRepeat();

        $out->elementEnd('div');

        $nli->showNoticeOptions();

        if ($notice->object_type == QnA_Question::OBJECT_TYPE) {
            $user = common_current_user();
            $question = QnA_Question::getByNotice($notice);

            if (!empty($user)) {
                $profile = $user->getProfile();
                $answer = $question->getAnswer($profile);

                // Output a placeholder input -- clicking on it will
                // bring up a real answer form

                // NOTE: this whole ul is just a placeholder
                if (empty($question->closed) && empty($answer)) {
                    $out->elementStart('ul', 'notices qna-dummy');
                    $out->elementStart('li', 'qna-dummy-placeholder');
                    $out->element(
                        'input',
                        array(
                            'class' => 'placeholder',
                            // TRANS: Placeholder value for a possible answer to a question
                            // TRANS: by the logged in user.
                            'value' => _m('Your answer...')
                        )
                    );
                    $out->elementEnd('li');
                    $out->elementEnd('ul');
                }
            }
        }

        return false;
    }

    function showNoticeAnswer($notice, $out)
    {
        $user = common_current_user();

        $answer   = QnA_Answer::getByNotice($notice);
        $question = $answer->getQuestion();

        $nli = new NoticeListItem($notice, $out);
        $nli->showNotice();

        $out->elementStart('div', array('class' => 'entry-content answer-content'));

        if (!empty($answer)) {
            $form = new QnashowanswerForm($out, $answer);
            $form->show();
        } else {
            // TRANS: Error message displayed when answer data is not present.
            $out->text(_m('Answer data is missing.'));
        }

        $out->elementEnd('div');

        // @todo FIXME
        $out->elementStart('div', array('class' => 'entry-content'));
    }

    static function shorten($content, $notice)
    {
        $short = null;

        if (Notice::contentTooLong($content)) {
            common_debug("content too long");
            $max = Notice::maxContent();
            // TRANS: Link description for link to full notice text if it is longer than
            // TRANS: what will be dispplayed.
            $ellipsis = _m('…');
            $short = mb_substr($content, 0, $max - 1);
            $short .= sprintf('<a href="%1$s" rel="more" title="%2$s">%3$s</a>',
                              $notice->uri,
                              // TRANS: Title for link that is an ellipsis in English.
                              _m('more...'),
                              $ellipsis);
        } else {
            $short = $content;
        }

        return $short;
    }

    /**
     * Form for our app
     *
     * @param HTMLOutputter $out
     * @return Widget
     */
    function entryForm($out)
    {
        return new QnanewquestionForm($out);
    }

    /**
     * When a notice is deleted, clean up related tables.
     *
     * @param Notice $notice
     */
    function deleteRelated($notice)
    {
        switch ($notice->object_type) {
        case QnA_Question::OBJECT_TYPE:
            common_log(LOG_DEBUG, "Deleting question from notice...");
            $question = QnA_Question::fromNotice($notice);
            $question->delete();
            break;
        case QnA_Answer::OBJECT_TYPE:
            common_log(LOG_DEBUG, "Deleting answer from notice...");
            $answer = QnA_Answer::fromNotice($notice);
            common_log(LOG_DEBUG, "to delete: $answer->id");
            $answer->delete();
            break;
        default:
            common_log(LOG_DEBUG, "Not deleting related, wtf...");
        }
    }

    function onEndShowScripts($action)
    {
        $action->script($this->path('js/qna.js'));
        return true;
    }

    function onEndShowStyles($action)
    {
        $action->cssLink($this->path('css/qna.css'));
        return true;
    }
}

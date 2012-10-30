<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Data class for Conversations
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
 * @category  Data
 * @package   StatusNet
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

class Conversation extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'conversation';                    // table name
    public $id;                              // int(4)  primary_key not_null
    public $uri;                             // varchar(225)  unique_key
    public $notice_id;                       // int(4)  primary_key not_null
    public $created;                         // datetime   not_null
    public $modified;                        // timestamp   not_null default_CURRENT_TIMESTAMP

    /* Static get */
    function staticGet($k,$v=NULL) {
		return Memcached_DataObject::staticGet('Conversation',$k,$v);
	}

    function pkeyGet($kv)
	    {  
	        return Memcached_DataObject::pkeyGet('Conversation', $kv);
	    }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'id' => array('type' => 'int', 'not null' => true, 'description' => 'from conversation root notice'),
                'uri' => array('type' => 'varchar', 'length' => 255, 'description' => 'URI of the conversation'),
                'notice_id' => array('type' => 'int', 'not null' => true, 'description' => 'this notice'),
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('id', 'notice_id'),
            'unique keys' => array(
                'conversation_uri_key' => array('uri'),
            ),
            'foreign keys' => array(
                'conversation_notice_id_fkey' => array('notice', array('notice_id' => 'id')),
			),
			'indexes' => array(
				'conversation_notice_id_idx' => array('notice_id'),
			),
        );
    }

    /**
     * Factory method for creating a new conversation
	 *
	 * @param notice_id int notice_id that started the conversation
     *
     * @return Conversation the new conversation DO
     */
    static function create($notice_id)
    {
        $conv = new Conversation();
        $conv->created = common_sql_now();
		$conv->id = $notice_id;
		$conv->notice_id = $notice_id;
        $conv->uri = common_local_url('conversation', array('id' => $notice_id), null, null, false);
        $result = $conv->insert();

        if (empty($result)) {
            common_log_db_error($conv, 'INSERT', __FILE__);
            return null;
        }

		self::blow();
        return $conv;
    }
	static function append($conv_id, $notice_id)
	{
		// find a root, i.e. where conv_id==$notice_id
		$conv = self::pkeyGet(array('id'=>$conv_id, 'notice_id'=>$conv_id));
		if (empty($conv)) {
			//return self::create($conv_id);	// should it really do this?
			throw new Exception('Conversation root not found');	// maybe this should be done instead
		}
		file_put_contents('/tmp/convobj', print_r($conv,true));
		$conv->query('BEGIN');

		// update modified value for root
		$original = clone($conv);
		$conv->modified = common_sql_now();
		$conv->update($original);

		// append by insert with new notice_id. uri value is a hack for now.
		$conv->uri .= '#notice-'.$notice_id;
		$conv->notice_id = $notice_id;
		$conv->created = $conv->modified;
		$result = $conv->insert();

        if (empty($result)) {
            common_log_db_error($conv, 'INSERT', __FILE__);
            throw new Exception('Could not append to conversation');
        }

		// finalize transaction
		$conv->query('COMMIT');

        self::blow('conversation:length:%d', $conv->id);
		return $conv;
	}

    static function noticeCount($id)
    {
		return Notice::conversationLength($id);
	}

	static function getNotices($id)
	{
		$conv = new Conversation;
		$conv->id = $id;
		$conv->orderBy('created ASC');
		$ids = $conv->fetchAll('notice_id');
		if (empty($ids)) {
			throw new Exception('No notices found');
		}
		return Notice::multiGet('id', $ids);
	}
}

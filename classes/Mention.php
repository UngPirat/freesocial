<?php
/**
 * Table Definition for mention
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Mention extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'mention';                           // table name
    public $notice_id;                       // int(4)  primary_key not_null
    public $profile_id;                      // int(4)  primary_key not_null
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    /* Static get */
    function staticGet($k,$v=null)
    { return Memcached_DataObject::staticGet('Mention',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'notice_id' => array('type' => 'int', 'not null' => true, 'description' => 'notice that has the mention'),
                'profile_id' => array('type' => 'int', 'not null' => true, 'description' => 'profile mentioned'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('notice_id', 'profile_id'),
            'foreign keys' => array(
                'mention_notice_id_fkey' => array('notice', array('notice_id' => 'id')),
                'mention_profile_id_fkey' => array('profile', array('profile_id' => 'id')),
            ),
            'indexes' => array(
                'mention_notice_id_idx' => array('notice_id'),
                'mention_profile_id_idx' => array('profile_id'),
                'mention_profile_id_modified_notice_id_idx' => array('profile_id', 'modified', 'notice_id')
            ),
        );
    }    

	function pkeyGet($kv)
	{
		return Memcached_DataObject::pkeyGet('Mention',$kv);   
	}
	
    /**
     * Wrapper for record insertion to update related caches
     */
    function insert()
    {
        $result = parent::insert();

        if ($result) {
            self::blow('mention:stream:%d', $this->profile_id);
        }

        return $result;
    }

    function stream($user_id, $offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $max_id=0)
    {
        $stream = new MentionNoticeStream($user_id);

        return $stream->getNotices($offset, $limit, $since_id, $max_id);
    }
}

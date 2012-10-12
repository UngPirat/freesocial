<?php
/**
 * Table Definition for avatar
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Avatar extends Managed_DataObject
{
    const PROFILE_SIZE = 128;
    const STREAM_SIZE  =  96;
    const MINI_SIZE    =  24;

	private static $avatars = array();

    public $__table = 'avatar';              // table name
    public $profile_id;                      // int(4)  primary_key not_null
    public $original;                        // tinyint(1)
    public $width;                           // int(4)  primary_key not_null
    public $height;                          // int(4)  primary_key not_null
    public $mediatype;                       // varchar(32)   not_null
    public $filename;                        // varchar(255)
    public $url;                             // varchar(255)  unique_key
    public $created;                         // datetime()   not_null
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    /* Static get */
    function staticGet($k,$v=null)
    { return Memcached_DataObject::staticGet('Avatar',$k,$v); }

    static function pivotGet($keyCol, $keyVals, $otherCols)
    {
        return Memcached_DataObject::pivotGet('Avatar', $keyCol, $keyVals, $otherCols);
    }
    
    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'profile_id' => array('type' => 'int', 'not null' => true, 'description' => 'foreign key to profile table'),
                'original' => array('type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'uploaded by user or generated?'),
                'width' => array('type' => 'int', 'not null' => true, 'description' => 'image width'),
                'height' => array('type' => 'int', 'not null' => true, 'description' => 'image height'),
                'mediatype' => array('type' => 'varchar', 'length' => 32, 'not null' => true, 'description' => 'file type'),
                'filename' => array('type' => 'varchar', 'length' => 255, 'description' => 'local filename, if local'),
                'url' => array('type' => 'varchar', 'length' => 255, 'description' => 'avatar location'),
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('profile_id', 'width', 'height'),
            'unique keys' => array(
                'avatar_url_key' => array('url'),
            ),
            'foreign keys' => array(
                'avatar_profile_id_fkey' => array('profile', array('profile_id' => 'id')),
            ),
            'indexes' => array(
                'avatar_profile_id_idx' => array('profile_id'),
            ),
        );
    }
    // We clean up the file, too

    function delete()
    {
        $filename = $this->filename;
        if (parent::delete()) {
            @unlink(Avatar::path($filename));
        }
    }

    function pkeyGet($kv)
    {
        return Memcached_DataObject::pkeyGet('Avatar', $kv);
    }

    static function getUploaded($profile_id)
    {
        $avatar = new Avatar();
        $avatar->profile_id = $profile_id;
        $avatar->original = true;
        if (!$avatar->find(true)) {
            throw new Exception (_m('No original avatar filename found for profile'));
        }
        return $avatar;
    }

	static function getUrlByProfile(Profile $profile, $width=self::PROFILE_SIZE, $height=null, array $args=array()) {
		if (!isset($args['fallback'])) {
			$args['fallback'] = true;
		}
		$avatar = self::getByProfile($profile, $width, $height, $args);
		return $avatar ? $avatar->displayUrl() : null;
	}

    static function getByProfile(Profile $profile, $width=self::PROFILE_SIZE, $height=null, array $args=array()) {
        if (empty($height)) {
            $height = $width;
        }
		$fallback = isset($args['fallback']) ? $args['fallback'] : false;

		if ($avatar = self::getCached($profile->id, $width, $height)) {
			return $avatar;
		}

        if (Event::handle('StartProfileGetAvatar', array($profile, $width, &$avatar))) {
            $avatar = Avatar::pkeyGet(
                array(
                    'profile_id' => $profile->id,
                    'width'      => $width,
                    'height'     => $height
                )
            ); 
            Event::handle('EndProfileGetAvatar', array($profile, $width, &$avatar));
        }

        if (empty($avatar)) {
            try {
                $avatar = Avatar::newSize($profile->id, $width);
            } catch (Exception $e) {
                common_debug($e->getMessage());
            }
        }
		if (empty($avatar) && $fallback) {
			$avatar = new Avatar;
			$avatar->url = Avatar::defaultImage($width, $height);
			$avatar->mediatype = 'image/png';	//TODO: get default mediatype correctly!
			$avatar->width = $width;
			$avatar->height = $height;
		}
		if (!empty($avatar)) {
			self::setCached($avatar, $profile->id, $width, $height);
		}

        return $avatar;
    }
    static function getProfileAvatars($profile_id) {
        $avatar = new Avatar();
        $avatar->profile_id = $profile_id;

        return $avatar->fetchAll();
    }

	static function getCached($profile_id, $width, $height) {
		if (isset(self::$avatars[$profile_id]["{$width}x{$height}"])) {
			return self::$avatars[$profile_id]["{$width}x{$height}"];
		}
		return null;
	}
	static function setCached($avatar, $profile_id, $width, $height) {
		self::$avatars[$profile_id]["{$width}x{$height}"] = $avatar;
	}

    /**
     * Where should the avatar go for this user?
     */
    static function filename($id, $extension, $size=null, $extra=null)
    {
        if ($size) {
            return $id . '-' . $size . (($extra) ? ('-' . $extra) : '') . $extension;
        } else {
            return $id . '-original' . (($extra) ? ('-' . $extra) : '') . $extension;
        }
    }

    static function path($filename)
    {
        $dir = common_config('avatar', 'dir');

        if ($dir[strlen($dir)-1] != '/') {
            $dir .= '/';
        }

        return $dir . $filename;
    }

    static function url($filename)
    {
        $path = common_config('avatar', 'path');

        if ($path[strlen($path)-1] != '/') {
            $path .= '/';
        }

        if ($path[0] != '/') {
            $path = '/'.$path;
        }

        $server = common_config('avatar', 'server');

        if (empty($server)) {
            $server = common_config('site', 'server');
        }

        $ssl = common_config('avatar', 'ssl');

        if (is_null($ssl)) { // null -> guess
            if (common_config('site', 'ssl') == 'always' &&
                !common_config('avatar', 'server')) {
                $ssl = true;
            } else {
                $ssl = false;
            }
        }

        $protocol = ($ssl) ? 'https' : 'http';

        return $protocol.'://'.$server.$path.$filename;
    }

    function displayUrl()
    {
        $server = common_config('avatar', 'server');
        if ($server && !empty($this->filename)) {
            return Avatar::url($this->filename);
        } else {
            return $this->url;
        }
    }

    static function defaultImage($width, $height=null)
    {
		// height is not handled yet
        static $sizenames = array(Avatar::PROFILE_SIZE => 'profile',
                                  Avatar::STREAM_SIZE => 'stream',
                                  Avatar::MINI_SIZE => 'mini');
        return Theme::path('default-avatar-'.$sizenames[$width].'.png');
    }

    static function deleteFromProfile($profile_id) {
        $avatars = Avatar::getProfileAvatars($profile_id);
        foreach ($avatars as $avatar) {
            $avatar->delete();
        }
    }


    static function newSize($profile_id, $size) {
        $safesize = floor($size);
        if ($safesize < 1 || $safesize > 999) {
            throw new Exception('Bad avatar size: '.$size);
        }

        $original = Avatar::getUploaded($profile_id);

        $imagefile = new ImageFile($profile_id, Avatar::path($original->filename));
        $filename = $imagefile->resize($safesize);

        $scaled = clone($original);
        $scaled->original = false;
        $scaled->width = $safesize;
        $scaled->height = $safesize;
        $scaled->url = Avatar::url($filename);
        $scaled->created = DB_DataObject_Cast::dateTime();

        if (!$scaled->insert()) {
            throw new Exception('Could not create new avatar from original image for profile_id='.$profile_id);
        }
        return $scaled;
    }
}

define('AVATAR_PROFILE_SIZE', Avatar::PROFILE_SIZE);
define('AVATAR_STREAM_SIZE',  Avatar::STREAM_SIZE);
define('AVATAR_MINI_SIZE',    Avatar::MINI_SIZE);

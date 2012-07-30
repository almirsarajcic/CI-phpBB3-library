<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
* CodeIgniter phpBB3 Library
*
* CodeIgniter phpBB3 bridge (access phpBB3 user sessions and other functions inside your CodeIgniter applications).
*
* @author Tomaž Muraus
* @version	1.2
* @link http://www.tomaz-muraus.info
*/
class Phpbb
{
    public $CI;
    protected $_user;
    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!isset($this->CI))
		{
			$this->CI =& get_instance();
		}
		// Set the variables scope
		global $phpbb_root_path, $phpEx, $user, $auth, $cache, $db, $config, $template, $table_prefix;
		define('IN_PHPBB', TRUE);
		define('FORUM_ROOT_PATH', './forum/');
		$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : FORUM_ROOT_PATH;
		$phpEx = substr(strrchr(__FILE__, '.'), 1);
		// Include needed files
		include($phpbb_root_path . 'common.' . $phpEx);
		include($phpbb_root_path . 'config.' . $phpEx);
		include($phpbb_root_path . 'includes/functions_user.' . $phpEx);
		include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
		include($phpbb_root_path . 'includes/functions_privmsgs.' . $phpEx);
		include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
		// Initialize phpBB user session
		$user->session_begin();
		$auth->acl($user->data);
		$user->setup();
		// Save user data into $_user variable
		$this->_user = $user;
    }
    /**
     * Returns information from the user data array.
     *
     * @param string $key Item key.
     *
     * @return string/boolean User information on success, FALSE otherwise.
     */
    public function getUserInfo($key)
    {
        if (array_key_exists($key, $this->_user->data))
        {
            return $this->_user->data[$key];
        }
        else
        {
            return FALSE;
        }
    }
	
	/**
	 * Get user's avatar
	 */
	public function getUserAvatar($avatar, $avatar_type, $avatar_width, $avatar_height)
	{
		if (empty($avatar) || !$avatar_type)
		{
			return '';
		}
		$avatar_img = '';
	
		switch ($avatar_type)
		{
			case 1:
				$avatar_img = "/forum/download/file.php?avatar=";
			break;
	
			case 2:
				$avatar_img = '/forum/images/avatars/gallery/';
			break;
	
			case 3:
			break;
		}
	
		$avatar_img .= $avatar;
		return '<img src="' . (str_replace(' ', '%20', $avatar_img)) . '" width="' . $avatar_width . '" height="' . $avatar_height . '" alt="Avatar" />';
	}
	
	/**
	 * Get user session id
	 *
	 * Can be used for log out form
	 */
	public function session_id()
	{
		return $this->_user->session_id;
	}
    /**
     * Returns user status.
     *
     * @return boolean TRUE is user is logged in, FALSE otherwise.
     */
    public function isLoggedIn()
    {
        return $this->_user->data['is_registered'];
    }
    /**
     * Checks if the currently logged-in user is an administrator.
     *
     * @return boolean TRUE if the currently logged-in user is an administrator, FALSE otherwise.
     */
    public function isAdministrator()
    {
        return $this->isGroupMember('administrators');
    }
	/**
     * Checks if the currently logged-in user is a moderator.
     *
     * @return boolean TRUE if the currently logged-in user is a moderator, FALSE otherwise.
     */
    public function isModerator()
    {
        return  $this->isGroupMember('moderators');
    }
    /**
     * Checks if a user is a member of the given user group.
     *
     * @param string $group Group name in lowercase.
     *
     * @return boolean TRUE if user is a group member, FALSE otherwise.
     */
    public function isGroupMember($group)
    {
        $groups = array_map(strtolower, $this->getUserGroupMembership());
        if (in_array($group, $groups))
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
    /**
     * Returns information for a given user.
     *
     * @param int $userId User ID.
     *
     * @return array/boolean Array with user information on success, FALSE otherwise.
     */
    public function getUserById($userId)
    {
        global $table_prefix;
        $this->CI->db->select('*');
        $this->CI->db->from($table_prefix . 'users');
        $this->CI->db->where('user_id', $userId);
        $this->CI->db->limit(1);
        $query = $this->CI->db->get();
        if ($query->num_rows() == 1)
        {
            return $query->row_array();
        }
        else
        {
            return FALSE;
        }
    }
	/**
     * Returns information for a given user.
     *
     * @param string $username User name.
     *
     * @return array/boolean Array with user information on success, FALSE otherwise.
     */
    public function getUserByName($username)
    {
        global $table_prefix;
        $this->CI->db->select('*');
        $this->CI->db->from($table_prefix . 'users');
        $this->CI->db->where('username', $username);
        $this->CI->db->limit(1);
        $query = $this->CI->db->get();
        if ($query->num_rows() == 1)
        {
            return $query->row_array();
        }
        else
        {
            return FALSE;
        }
    }
    /**
     * Returns all user groups.
     *
     * @return array User groups.
     */
    public function getUserGroupMembership()
    {
        global $table_prefix;
        $userId = $this->_user->data['user_id'];
        $this->CI->db->select('g.group_name');
        $this->CI->db->from($table_prefix . 'groups g');
        $this->CI->db->from($table_prefix . 'user_group u');
        $this->CI->db->where('u.user_id', $userId);
        $this->CI->db->where('u.group_id', 'g.group_id', FALSE);
        $query = $this->CI->db->get();
        foreach ($query->result_array() as $group)
        {
            $groups[] = $group['group_name'];
        }
        return $groups;
    }
    /**
     * Send user a private message.
     *
     * @param int $senderId The sender's user ID.
     * @param string $senderIp The sender's IP address.
     * @param string $senderUsername The sender's username.
     * @param int $recipientId Recipient ID.
     * @param string $subject Message subject.
     * @param string $message Message body.
     * @param boolean $enableSignature Attach user signature?
     * @param boolean $enableBBcode Enable BB code?
     * @param boolean $enableSmilies Enable smiles?
     * @param boolean $enableUrls Enable URLs (automatically wrap URLs in <a> tag)?
     */
    public function sendPrivateMessage($senderId, $senderIp = '127.0.0.1', $senderUsername, $recipientId, $subject, $message, $enableSignature = FALSE, $enableBBcode = TRUE, $enableSmilies = TRUE, $enableUrls = TRUE)
    {
        $uid = $bitfield = $options = '';
		generate_text_for_storage($message, $uid, $bitfield, $options, $enableBBcode, $enableUrls, $enableSmilies);
		$data = array
					(
						'from_user_id'			=> $senderId,
						'from_user_ip'			=> $senderIp,
						'from_username'			=> $senderUsername,
						'enable_sig'			=> $enableSignature,
						'enable_bbcode'			=> $enableBBcode,
						'enable_smilies'		=> $enableSmilies,
						'enable_urls'			=> $enableUrls,
						'icon_id'				=> 0,
						'bbcode_bitfield'		=> $bitfield,
						'bbcode_uid'			=> $uid,
						'message'				=> $message,
						'address_list'			=> array('u' => array($recipientId => 'to'))
					);
		submit_pm('post', $subject, $data, FALSE);
    }
    /**
     * Add user to group.
     *
     * @param int $userId User ID.
     * @param int $groupId The user group ID to add user to.
     * @param boolean $default If true, will set this group as the default group for the user being added.
     * @param boolean $leader If true, user will be a leader of the group.
     * @param boolean $pending If true, user needs to be approved before being shown in the group member list.
     *
     * @return boolean/string FALSE on success, language string for the relevant error otherwise.
     */
    public function addUserToGroup($userId, $groupId, $default = FALSE, $leader = FALSE, $pending = FALSE)
    {
        return group_user_add($groupId, $userId, FALSE, FALSE, $default, $leader, $pending);
    }
    /**
     * Create a new topic (topic will be posted with the currently logged-in user as an author).
     *
     * @param int $forumId The forum ID.
     * @param string $subject Topic subject.
     * @param string $message Topic body.
     * @param boolean $enableSignature Attach user signature?
     * @param boolean $enableBBcode Enable BB code?
     * @param boolean $enableSmilies Enable smiles?
     * @param boolean $enableUrls Enable URLs (automatically wrap URLs in <a> tag)?
     *
     * @return string Topic URL on success, forum URL otherwise.
     */
    public function createNewTopic($forumId, $subject, $message, $enableSignature = FALSE, $enableBBcode = TRUE, $enableSmilies = TRUE, $enableUrls = TRUE)
    {
        $poll = $uid = $bitfield = $options = '';
        generate_text_for_storage($subject, $uid, $bitfield, $options, false, false, false);
        generate_text_for_storage($message, $uid, $bitfield, $options, $enableBBcode, $enableUrls, $enableSmilies);
        $data = array
                    (
                        'forum_id'          => $forumId,
                        'icon_id'           => FALSE,
                        'enable_bbcode'     => $enableBBcode,
                        'enable_smilies'    => $enableSmilies,
                        'enable_urls'       => $enableUrls,
                        'enable_sig'        => $enableSignature,
                        'message'           => $message,
                        'message_md5'       => md5($message),
                        'bbcode_bitfield'   => $bitfield,
                        'bbcode_uid'        => $uid,
                        'post_edit_locked'  => 0,
                        'topic_title'       => $subject,
                        'notify_set'        => FALSE,
                        'notify'            => FALSE,
                        'post_time'         => 0,
                        'forum_name'        => '',
                        'enable_indexing'   => TRUE
                    );
        return submit_post('post', $subject, '', POST_NORMAL, $poll, $data);
    }
}
/* End of file phpbb.php */
/* Location: ./application/libraries/phpbb.php */
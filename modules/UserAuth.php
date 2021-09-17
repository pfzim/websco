<?php
/*
    UserAuth class - Internal or LDAP user authentication and access
	                 control module.
    Copyright (C) 2020 Dmitry V. Zimin

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
	Usage example:

		define('RB_ACCESS_READ', 1);
		define('RB_ACCESS_WRITE', 2);
		define('RB_ACCESS_EXECUTE', 3);
		define('RB_ACCESS_LIST', 4);

		$core->UserAuth->set_bits_representation('rwxl');

	Where 1, 2, 3, 4 are the ordinal numbers of the bits.
*/

define('UA_DISABLED',	0x0001);  /// Ignored for LDAP user
define('UA_LDAP',		0x0002);  /// Is a LDAP user (overwise is internal user)

class UserAuth
{
	private $uid = 0;                    /// User ID

	private $loaded = FALSE;
	private $login = NULL;				/// sAMAccountName, zl cookie
	private $token = NULL;				/// zh cookie
	private $flags = 0;

	private $ldap = NULL;

	private $bits_string_representation = '';  // like 'rwxd'
	private $max_bits = 0;

	private $rights = array();           // $rights[$object_id] = $bit_flags_permissions

	private $rise_exception = FALSE;

	function __construct(&$core)
	{
		$this->core = &$core;
		$this->rise_exception = FALSE;

		if(!isset($this->core->LDAP))
		{
			$this->ldap = NULL;
		}
		else
		{
			$this->ldap = &$this->core->LDAP;
		}

		$this->bits_string_representation = '';
		$this->max_bits = 0;
		$this->rights = array();
		$this->loaded = FALSE;

		if(empty($_SESSION['uid']))
		{
			if(!empty($_COOKIE['zh']) && !empty($_COOKIE['zl']))
			{
				if($this->core->db->select_ex($user_data, rpv("
					SELECT
						m.`id`,
						m.`flags`,
						m.`login`,
						m.`sid`
					FROM
						@users AS m
					WHERE
						m.`login` = !
						AND m.`sid` IS NOT NULL
						AND m.`sid` = !
						AND (m.`flags` & 0x0001) = 0
					LIMIT 1
				", $_COOKIE['zl'], $_COOKIE['zh'])))
				{
					$this->loaded = TRUE;
					$_SESSION['uid'] = $user_data[0][0];
					$this->uid = $_SESSION['uid'];
					$this->flags = intval($user_data[0][1]);
					$this->login = $user_data[0][2];
					$this->token = $user_data[0][3];

					// Extend cookie life time
					setcookie('zh', $this->token, time() + 2592000, '/');
					setcookie('zl', $this->login, time() + 2592000, '/');
				}
			}
		}
		else
		{
			$this->uid = intval($_SESSION['uid']);

			/*
			// preload user info

			if($this->core->db->select_ex($user_data, rpv("
				SELECT
					m.`id`,
					m.`ldap`,
					m.`login`,
					m.`sid`
				FROM
					@users AS m
				WHERE
					m.`id` = #
					AND (m.`flags` & 0x0001) = 0
				LIMIT 1
			", $_SESSION['uid'])))
			{
				$this->loaded = TRUE;
				$this->uid = $user_data[0][0];
				$this->flags = intval($user_data[0][1]);
				$this->login = $user_data[0][2];
				$this->token = $user_data[0][3];
			}
			*/
		}
	}

	public function logon($login, $passwd)
	{
		if(empty($login) || empty($passwd))
		{
			$this->core->error_ex('Empty login or password!', $this->rise_exception);
			return FALSE;
		}

		if(strpbrk($login, '\\@'))  // LDAP authorization method
		{
			if(!$this->ldap)
			{
				$this->core->error_ex('LDAP class not initialized!', $this->rise_exception);
				return FALSE;
			}

			if(strpos($login, '\\'))
			{
				list($domain, $sam_account_name) = explode('\\', $login, 2);
			}
			else if(strpos($login, '@'))
			{
				list($sam_account_name, $domain) = explode('@', $login, 2);
			}

			if(!$this->ldap->reset_user($login, $passwd, TRUE))
			{
				$this->core->error_ex($this->core->get_last_error(), $this->rise_exception);
				return FALSE;
			}

			if($this->core->db->select_ex($user_data, rpv("
					SELECT
						m.`id`,
						m.`login`,
						m.`flags`,
						m.`sid`,
						m.`passwd`
					FROM
						`@users` AS m
					WHERE
						m.`login` = !
						AND (m.`flags` & (0x0002)) = 0x0002
					LIMIT 1
				", $sam_account_name
			)))
			{
				if(!empty($user_data[0][4]))
				{
					//$this->error('Неверное имя пользователя или пароль!');
					return FALSE;
				}

				$_SESSION['uid'] = $user_data[0][0];
				$this->login = $user_data[0][1];
				$this->flags = intval($user_data[0][2]);
				$this->token = $user_data[0][3];
			}
			else // add new LDAP user
			{
				$this->token = uniqid();
				$this->login = $sam_account_name;
				$this->flags = UA_LDAP;
				$this->core->db->put(rpv('INSERT INTO @users (login, passwd, mail, sid, flags) VALUES (!, \'\', !, !, #)', $this->login, @$records[0]['mail'][0], $this->token, $this->flags));
				$_SESSION['uid'] = $this->core->db->last_id();
			}
		}
		else  // internal authorization method
		{
			if(!$this->core->db->select_ex($user_data, rpv("
					SELECT
						m.`id`,
						m.`login`,
						m.`flags`,
						m.`sid`
					FROM
						@users AS m
					WHERE
						m.`login` = !
						AND m.`passwd` = MD5(!)
						AND (m.`flags` & (0x0001 | 0x0002)) = 0x0000
					LIMIT 1
				", $login, $passwd
			)))
			{
				//$this->core->db->put(rpv('INSERT INTO `@log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)', 0, LOG_LOGIN_FAILED, 0, $ip));
				return FALSE;
			}

			$_SESSION['uid'] = $user_data[0][0];
			$this->login = $user_data[0][1];
			$this->flags = intval($user_data[0][2]);
			$this->token = $user_data[0][3];
		}

		$this->loaded = TRUE;
		$this->uid = $_SESSION['uid'];

		if(empty($this->token))
		{
			$this->token = uniqid();
			$this->core->db->put(rpv('UPDATE @users SET `sid` = ! WHERE `id` = # LIMIT 1', $this->token, $this->uid));
		}

		setcookie('zh', $this->token, time() + 2592000, '/');
		setcookie('zl', $this->login, time() + 2592000, '/');

		//$this->core->db->put(rpv('UPDATE @users SET `sid` = ! WHERE `id` = # LIMIT 1', $this->token, $this->uid));

		//$this->core->db->put(rpv('INSERT INTO `@log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)', $this->uid, LOG_LOGIN, 0, $ip));

		return TRUE;
	}

	public function logoff()
	{
		$_SESSION['uid'] = 0;
		setcookie('zh', NULL, time() - 60, '/');
		setcookie('zl', NULL, time() - 60, '/');

		$this->loaded = FALSE;
		$this->core->db->put(rpv('UPDATE @users SET `sid` = NULL WHERE `id` = # LIMIT 1', $this->uid));
		$this->uid = 0;
		$this->flags = 0;
		$this->login = NULL;
		$this->token = NULL;
	}

	public function add($login, $passwd, $mail)
	{
		if($this->core->db->select(rpv('SELECT u.`id` FROM @users AS u WHERE u.`login`= ! OR u.`mail` = ! LIMIT 1', $login, $mail)))
		{
			$this->core->error_ex('User already exist!', $this->rise_exception);
			return 0;
		}

		if(!$this->core->db->put(rpv('INSERT INTO @users (login, passwd, mail, flags) VALUES (!, MD5(!), !, 0x0001)', $login, $passwd, $mail)))
		{
			$this->core->error_ex($this->core->get_last_error(), $this->rise_exception);
			return 0;
		}

		return $this->core->db->last_id();
	}

	public function change_password($passwd)
	{
		if($this->uid && !$this->is_ldap_user())  // Only internal user can change self password
		{
			if($this->core->db->put(rpv('UPDATE `@users` SET `passwd` = MD5(!) WHERE `id` = # AND (`flags` & 0x0002) = 0x0000 LIMIT 1', $passwd, $this->uid)))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	public function check_password($passwd)
	{
		if($this->uid && !$this->is_ldap_user())  // Only internal user can change self password
		{
			if($this->core->db->select_ex($user, rpv('SELECT u.`id` FROM `@users` AS u WHERE u.`id` = # AND u.`passwd` = MD5(!) AND (u.`flags` & 0x0002) = 0x0000 LIMIT 1', $this->uid, $passwd)))
			{
				if(intval($user[0][0]) == $this->uid)
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 *  \brief Activate other user
	 *
	 *  \param [in] $id User ID
	 *  \param [in] $login User login
	 *  \param [out] $mail Activated user mail address for send notification
	 *  \return true - if activated successfully
	 */

	public function activate($id, $login, &$mail)
	{
		if($this->uid && !$this->is_ldap_user())  // Only internal user can activate
		{
			if($this->core->db->put(rpv('UPDATE @users SET `flags` = (`flags` & ~0x0001) WHERE `login` = ! AND `id` = #', $login, $id)))
			{
				//$this->core->db->put(rpv('INSERT INTO `@log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)', 0, LOG_LOGIN_ACTIVATE, $id, $ip));
				$mail = FALSE;
				if($this->core->db->select_ex($result, rpv('SELECT u.`mail` FROM @users AS u WHERE u.`id` = # LIMIT 1', $id)))
				{
					$mail = $result[0][0];
				}
				return TRUE;
			}
			else
			{
				$this->core->error_ex($this->core->get_last_error(), $this->rise_exception);
			}
		}

		return FALSE;
	}

	private function load_user_info()
	{
		if(!$this->core->db->select_ex($result, rpv('SELECT u.`login`, u.`sid`, u.`flags` FROM @users AS u WHERE u.`id` = # AND (u.`flags` & 0x0001) = 0x0000 LIMIT 1', $this->uid)))
		{
			$this->core->error_ex($this->core->get_last_error(), $this->rise_exception);
			return FALSE;
		}

		$this->loaded = TRUE;
		$this->login = $result[0][0];
		$this->token = $result[0][1];
		$this->flags = intval($result[0][2]);

		return TRUE;
	}

	public function get_id()
	{
		return $this->uid;
	}

	public function get_token()
	{
		if(!$this->loaded)
		{
			if(!$this->load_user_info())
			{
				return FALSE;
			}
		}

		return $this->token;
	}

	public function get_login()
	{
		if(!$this->loaded)
		{
			if(!$this->load_user_info())
			{
				return FALSE;
			}
		}

		return $this->login;
	}

	public function is_ldap_user()
	{
		if(!$this->loaded)
		{
			if(!$this->load_user_info())
			{
				return FALSE;
			}
		}

		return $this->flags & UA_LDAP;
	}

	public function is_member($group)
	{
		if($this->uid)
		{
			if(!$this->is_ldap_user())
			{
				return TRUE;  // Internal user is always admin
			}
			else
			{
				/*
				$cookie = '';
				ldap_control_paged_result($this->ldap->get_link(), 200, true, $cookie);

				$sr = ldap_search($this->ldap->get_link(), LDAP_BASE_DN, '(&(objectCategory=person)(objectClass=user)(sAMAccountName='.ldap_escape($this->get_login(), null, LDAP_ESCAPE_FILTER).')(memberOf:1.2.840.113556.1.4.1941:='.ldap_escape($group, null, LDAP_ESCAPE_FILTER).'))', array('samaccountname', 'objectsid'));
				if(!$sr)
				{
					$this->error($this->ldap->get_last_error());
					return FALSE;
				}

				$records = ldap_get_entries($this->ldap->get_link(), $sr);
				if(($records['count'] == 1) && (strcasecmp($records[0]['samaccountname'][0], $this->sam_account_name) == 0))
				{
					return TRUE;
				}
				*/
				if($this->ldap->search($records, '(&(objectCategory=person)(objectClass=user)(sAMAccountName='.ldap_escape($this->get_login(), null, LDAP_ESCAPE_FILTER).')(memberOf:1.2.840.113556.1.4.1941:='.ldap_escape($group, null, LDAP_ESCAPE_FILTER).'))', array('samaccountname', 'objectsid')) != 1)
				{
					return FALSE;
				}

				if(strcasecmp($records[0]['sAMAccountName'][0], $this->get_login()) == 0)
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	private function get_user_rights($object_id)
	{
		$this->rights[$object_id] = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

		$link = $this->ldap->get_link();

		if(	$this->uid
			&& $this->is_ldap_user()
			&& $link
			&& $this->core->db->select_ex($result, rpv("SELECT `dn`, `allow_bits` FROM @access WHERE `oid` = #", $object_id))
		)
		{
			foreach($result as &$row)
			{
				/*
				$cookie = '';
				ldap_control_paged_result($link, 2, true, $cookie);
				//echo '(&(objectClass=user)(sAMAccountName='.ldap_escape($this->sam_account_name, null, LDAP_ESCAPE_FILTER).')(memberOf:1.2.840.113556.1.4.1941:='.$row[0].'))';
				$sr = ldap_search($link, LDAP_BASE_DN, '(&(objectClass=user)(sAMAccountName='.ldap_escape($this->get_login(), null, LDAP_ESCAPE_FILTER).')(memberOf:1.2.840.113556.1.4.1941:='.$row[0].'))', array('samaccountname', 'objectsid'));
				if($sr)
				{
					$records = ldap_get_entries($link, $sr);
					if($records && ($records['count'] == 1))
					{
						for($i = 0; $i <= ((int) (LPD_ACCESS_LAST_BIT / 8)); $i++)
						{
							$this->rights[$object_id][$i] = chr(ord($this->rights[$object_id][$i]) | ord($row[1][$i]));
						}
					}
				}
				ldap_free_result($sr);
				*/
				if($this->ldap->search($records, '(&(objectClass=user)(sAMAccountName='.ldap_escape($this->get_login(), null, LDAP_ESCAPE_FILTER).')(memberOf:1.2.840.113556.1.4.1941:='.$row[0].'))', array('samaccountname', 'objectsid')) == 1)
				{
					$this->rights[$object_id] = $this->merge_permissions($this->rights[$object_id], $row[1]);
					/*
					for($i = 0; $i <= ((int) ($this->max_bits / 8)); $i++)
					{
						$this->rights[$object_id][$i] = chr(ord($this->rights[$object_id][$i]) | ord($row[1][$i]));
					}
					*/
				}
			}
		}
	}

	public function merge_permissions($rights, $added_rights)
	{
		for($i = 0; $i <= ((int) ($this->max_bits / 8)); $i++)
		{
			$rights[$i] = chr(ord($rights[$i]) | ord($added_rights[$i]));
		}

		return $rights;
	}

	/**
	 *  \brief Check user permissions for object
	 *
	 *  \param [in] $object_id Object ID
	 *  \param [in] $level One-based ordinal number of bit
	 *  \return true - if user have requested permisiions for object. For internal user always true.
	 */

	public function check_permission($object_id, $level)
	{
		if($this->uid && !$this->is_ldap_user())
		{
			return TRUE;  /// Internal user is always admin
		}

		if(!isset($this->rights[$object_id]))
		{
			$this->get_user_rights($object_id);
		}

		$level--;
		return ((ord($this->rights[$object_id][(int) ($level / 8)]) >> ($level % 8)) & 0x01);
	}

	public function set_bits_representation($representation)
	{
		$this->bits_string_representation = $representation;
		$this->max_bits = strlen($representation);
	}

	public function permissions_to_string($allow_bits)
	{
		$result = '';
		$bits_count = strlen($this->bits_string_representation);

		for($i = 0; $i < $bits_count; $i++)
		{
			if((ord($allow_bits[(int) ($i / 8)]) >> ($i % 8)) & 0x01)
			{
				$result .= $this->bits_string_representation[$i];
			}
			else
			{
				$result .= '-';
			}
		}
		return $result;
	}

	public function set_rise_exception($rise_exception)
	{
		$this->rise_exception = $rise_exception;
	}
}

/**
 *  \brief Set and Unset bits
 *
 *  \param [in/out] $bits Existings bits
 *  \param [in] $bit One-based ordinal number of bit
 *  \return Nothing
 */

function set_permission_bit(&$bits, $bit)
{
	$bit--;
	$bits[(int) ($bit / 8)] = chr(ord($bits[(int) ($bit / 8)]) | (0x1 << ($bit % 8)));
}

function unset_permission_bit(&$bits, $bit)
{
	$bit--;
	$bits[(int) ($bit / 8)] = chr(ord($bits[(int) ($bit / 8)]) & ((0x1 << ($bit % 8)) ^ 0xF));
}

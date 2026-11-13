<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * No privilege escalation in Users / Permissions: whoever creates or manages a user can give that user only
 * permissions he has himself, and can only manage users whose rights do not go beyond his own.
 *
 * - Super Admin (role 1): unrestricted (manages the platform staff only, see pjBaseUsers).
 * - Companies Admin (role 6): every permission of a page the Companies Admin may open (pjCompanyContext::pageScope).
 * - Company login (role 4): its own permissions, without user / permission management and its "My company" page.
 * - Every other role: its own effective permissions.
 *
 * Permissions of a user: his rows in user permissions, or (no rows) the permissions of his role. Because "no rows"
 * means "everything of the role", a restricted creator always stores explicit rows (seed / ensureRows).
 */
class pjPermissionGuard
{
	const ROLE_SUPER_ADMIN = 1;
	const ROLE_COMPANY = 4;
	const ROLE_COMPANIES_ADMIN = 6;

	private static $keyById = null;
	private static $givable = array();

	/** id => key of every permission. */
	public static function allKeys()
	{
		if (self::$keyById === null)
		{
			self::$keyById = array();
			foreach (pjAuthPermissionModel::factory()->select('t1.id, t1.key')->findAll()->getData() as $row)
			{
				self::$keyById[(int) $row['id']] = (string) $row['key'];
			}
		}
		return self::$keyById;
	}

	/** Controller and action of a permission key (Controller_pjActionX[_sub]). */
	private static function split($key)
	{
		$parts = explode('_', (string) $key);
		return array($parts[0], isset($parts[1]) ? $parts[1] : '');
	}

	/** Can a user of this role use the page of this permission at all? */
	public static function usableBy($role_id, $key)
	{
		if (!class_exists('pjCompanyContext'))
		{
			return true;
		}
		list($c, $a) = self::split($key);
		return pjCompanyContext::roleMayOpen((int) $role_id, pjCompanyContext::pageScope($c, $a));
	}

	/** Permission keys a user has now: key => key. */
	public static function keysOf($user_id, $role_id = null)
	{
		$user_id = (int) $user_id;
		if ($role_id === null)
		{
			$row = pjAuthUserModel::factory()->select('t1.role_id')->find($user_id)->getData();
			$role_id = !empty($row) ? (int) $row['role_id'] : 0;
		}
		$keys = self::allKeys();
		$ids = pjAuthUserPermissionModel::factory()->where('t1.user_id', $user_id)->findAll()->getDataPair(null, 'permission_id');
		if (empty($ids))
		{
			$ids = pjAuthRolePermissionModel::factory()->where('t1.role_id', (int) $role_id)->findAll()->getDataPair(null, 'permission_id');
		}
		$out = array();
		foreach ($ids as $id)
		{
			if (isset($keys[(int) $id])) { $out[$keys[(int) $id]] = $keys[(int) $id]; }
		}
		return $out;
	}

	/** True when the logged-in user may give any permission (the Super Admin). */
	public static function isUnrestricted($role_id)
	{
		return (int) $role_id === self::ROLE_SUPER_ADMIN;
	}

	/**
	 * Keys the logged-in user ($role_id, $user_id) may give to another user: key => key.
	 * Null for the Super Admin (everything).
	 */
	public static function givable($role_id, $user_id)
	{
		$role_id = (int) $role_id;
		$user_id = (int) $user_id;
		if (self::isUnrestricted($role_id))
		{
			return null;
		}
		$ck = $role_id . ':' . $user_id;
		if (isset(self::$givable[$ck]))
		{
			return self::$givable[$ck];
		}
		$out = array();
		if ($role_id === self::ROLE_COMPANIES_ADMIN)
		{
			foreach (self::allKeys() as $key)
			{
				if (self::usableBy($role_id, $key)) { $out[$key] = $key; }
			}
		} else {
			foreach (self::keysOf($user_id, $role_id) as $key)
			{
				if (!self::usableBy($role_id, $key))
				{
					continue;
				}
				if ($role_id === self::ROLE_COMPANY
					&& ((strpos($key, 'pjBaseUsers') === 0 && $key !== 'pjBaseUsers_pjActionProfile')
						|| strpos($key, 'pjBasePermissions') === 0 || $key === 'pjAdminCompanies_pjActionProfile'))
				{
					// a company manages its users itself: its users never manage users or the company
					continue;
				}
				$out[$key] = $key;
			}
		}
		return self::$givable[$ck] = $out;
	}

	/** May the logged-in user give this permission key? */
	public static function mayGive($role_id, $user_id, $key)
	{
		$g = self::givable($role_id, $user_id);
		return $g === null || isset($g[(string) $key]);
	}

	/**
	 * May the logged-in user manage (edit, change the role / status / permissions of, delete) $target_id?
	 * Only when everything the target can use is also something the logged-in user may give.
	 */
	public static function mayManage($role_id, $user_id, $target_id)
	{
		$g = self::givable($role_id, $user_id);
		if ($g === null)
		{
			return true;
		}
		$row = pjAuthUserModel::factory()->select('t1.role_id')->find((int) $target_id)->getData();
		if (empty($row))
		{
			return true;
		}
		if ((int) $role_id === self::ROLE_COMPANY && (int) $row['role_id'] === 5)
		{
			// a company always manages its own "Company user" users (which users: pjBaseUsers / pjBasePermissions);
			// their permissions are cut back to what the company may give when it opens or changes them
			return true;
		}
		foreach (self::keysOf($target_id, (int) $row['role_id']) as $key)
		{
			if (!isset($g[$key]) && self::usableBy((int) $row['role_id'], $key))
			{
				return false;
			}
		}
		return true;
	}

	/** Permission ids of $target_role that the logged-in user may give. */
	private static function rolePermissionIds($target_role, $g)
	{
		$keys = self::allKeys();
		$out = array();
		foreach (pjAuthRolePermissionModel::factory()->where('t1.role_id', (int) $target_role)->findAll()->getDataPair(null, 'permission_id') as $id)
		{
			$id = (int) $id;
			if (isset($keys[$id]) && ($g === null || isset($g[$keys[$id]])))
			{
				$out[$id] = $id;
			}
		}
		return $out;
	}

	/**
	 * Give a new user (or a user whose role was changed) the permissions of his role that the logged-in user has.
	 * Nothing to do for the Super Admin: the user follows his role.
	 */
	public static function seed($role_id, $user_id, $target_id, $target_role)
	{
		$g = self::givable($role_id, $user_id);
		pjAuthUserPermissionModel::factory()->where('user_id', (int) $target_id)->eraseAll();
		if ($g === null)
		{
			return;
		}
		foreach (self::rolePermissionIds($target_role, $g) as $pid)
		{
			pjAuthUserPermissionModel::factory(array('user_id' => (int) $target_id, 'permission_id' => $pid))->insert();
		}
		self::ensureRows($target_id);
	}

	/** Remove from $target_id every permission the logged-in user may not give. */
	public static function trim($role_id, $user_id, $target_id)
	{
		$g = self::givable($role_id, $user_id);
		if ($g === null)
		{
			return;
		}
		$keys = self::allKeys();
		if ((int) pjAuthUserPermissionModel::factory()->where('t1.user_id', (int) $target_id)->findCount()->getData() === 0)
		{
			// no rows yet = the permissions of his role: store them first, then cut
			$row = pjAuthUserModel::factory()->select('t1.role_id')->find((int) $target_id)->getData();
			foreach (self::rolePermissionIds(!empty($row) ? (int) $row['role_id'] : 0, null) as $pid)
			{
				pjAuthUserPermissionModel::factory(array('user_id' => (int) $target_id, 'permission_id' => $pid))->insert();
			}
		}
		$remove = array();
		foreach (pjAuthUserPermissionModel::factory()->where('t1.user_id', (int) $target_id)->findAll()->getDataPair(null, 'permission_id') as $pid)
		{
			if (!isset($keys[(int) $pid]) || !isset($g[$keys[(int) $pid]])) { $remove[] = (int) $pid; }
		}
		if (!empty($remove))
		{
			pjAuthUserPermissionModel::factory()->where('user_id', (int) $target_id)->whereIn('permission_id', $remove)->eraseAll();
		}
		self::ensureRows($target_id);
	}

	/**
	 * A user without rows would get every permission of his role: keep at least the dashboard
	 * (a page everybody may open) so that "no permissions" stays "no permissions".
	 */
	public static function ensureRows($target_id)
	{
		if ((int) pjAuthUserPermissionModel::factory()->where('t1.user_id', (int) $target_id)->findCount()->getData() > 0)
		{
			return;
		}
		$id = array_search('pjAdmin_pjActionIndex', self::allKeys(), true);
		if ($id === false)
		{
			$id = array_search('pjBaseUsers_pjActionProfile', self::allKeys(), true);
		}
		if ($id !== false)
		{
			pjAuthUserPermissionModel::factory(array('user_id' => (int) $target_id, 'permission_id' => (int) $id))->insert();
		}
	}
}
?>

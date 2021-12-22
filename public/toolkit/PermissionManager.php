<?php

namespace toolkit;

class PermissionManager {

    public static function allow(
        array $requester,
        string $permissions
        )
    {

        $requesterPermissions = explode(',',$requester['permissions']);

        # Either of the permission is allowed
        if (str_contains($permissions,'/')) {
            $allowedPermissions = explode('/',$permissions);
            foreach ($allowedPermissions as $allowedPermission) {
                if (in_array($allowedPermission,$requesterPermissions)) {
                    return true;
                }
            }
        }

        # Has to meet all the required permission
        if (str_contains($permissions,'+')) {
            $allowedPermissions = explode('+',$permissions);
            $requiredPermissionsCount = count($allowedPermissions);
            $i = 0;
            foreach ($allowedPermissions as $allowedPermission) {
                if (in_array($allowedPermission,$requesterPermissions)) {
                    $i++;
                }
            }
            return ($requiredPermissionsCount===$i);
        }

        if (in_array($permissions,$requesterPermissions)) {
            return true;
        }

        return false;
    }

}

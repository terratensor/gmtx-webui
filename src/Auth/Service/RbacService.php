<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Entity\User\Role;
use yii\rbac\ManagerInterface;

class RbacService
{
    public static function makeAdminSuperRole(ManagerInterface $auth): void
    {
        $admin = $auth->getRole(Role::ADMIN);
        if (!$admin) {
            return;
        }

        // Очищаем старые связи
        $auth->removeChildren($admin);

        // Добавляем все роли как дочерние для admin (кроме самой admin)
        foreach ($auth->getRoles() as $role) {
            if ($role->name !== Role::ADMIN && !$auth->hasChild($admin, $role)) {
                $auth->addChild($admin, $role);
            }
        }

        // Добавляем все разрешения напрямую к admin
        foreach ($auth->getPermissions() as $permission) {
            if (!$auth->hasChild($admin, $permission)) {
                $auth->addChild($admin, $permission);
            }
        }
    }
}

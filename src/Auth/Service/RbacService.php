<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Entity\User\Role;
use yii\rbac\ManagerInterface;
use yii\rbac\Role as RbacRole;
use yii\rbac\Permission;

/**
 * Сервис для работы с RBAC (Role-Based Access Control)
 * 
 * Предоставляет удобные методы для:
 * - Настройки иерархии ролей
 * - Создания ролей и разрешений
 * - Управления назначениями
 * - Автоматического обновления прав admin
 * 
 * @package App\Auth\Service
 * @see docs/RBAC_SERVICE_README.md Полная документация по работе с сервисом
 * @link docs/RBAC_SERVICE_README.md
 * RbacService::createRoleWithPermissions(
 *   Yii::$app->authManager,
 *   'moderator',
 *   ['post.edit', 'comment.delete']
 * );
 */
class RbacService
{
    /**
     * Настраивает базовую иерархию ролей:
     * - Создает user, member, admin
     * - Настраивает наследование member -> user
     * - Делает admin супер-ролью
     */
    public static function setupBasicHierarchy(ManagerInterface $auth): void
    {
        self::createRoleIfNotExists($auth, Role::USER);
        self::createRoleIfNotExists($auth, Role::MEMBER);
        self::createRoleIfNotExists($auth, Role::ADMIN);

        $auth->addChild(
            $auth->getRole(Role::MEMBER),
            $auth->getRole(Role::USER)
        );

        self::makeAdminSuperRole($auth);
    }

    /**
     * Делает роль admin супер-ролью (имеет все права)
     */
    public static function makeAdminSuperRole(ManagerInterface $auth): void
    {
        $admin = $auth->getRole(Role::ADMIN);
        if (!$admin) {
            return;
        }

        // Очищаем все текущие связи admin
        $auth->removeChildren($admin);

        // 1. Находим все роли кроме admin
        $roles = array_filter(
            $auth->getRoles(),
            fn($role) => $role->name !== Role::ADMIN
        );

        // 2. Добавляем связи только с другими ролями (не с разрешениями)
        foreach ($roles as $role) {
            if (!$auth->hasChild($admin, $role)) {
                $auth->addChild($admin, $role);
            }
        }
    }

    /**
     * Создает роль с набором разрешений
     * 
     * @param array $permissions Массив [permission => description]
     * @param string|null $parentRole Роль-родитель (для наследования)
     * @return RbacRole
     */
    public static function createRoleWithPermissions(
        ManagerInterface $auth,
        string $roleName,
        array $permissions,
        ?string $parentRole = null
    ): RbacRole {
        $role = self::createRoleIfNotExists($auth, $roleName);

        foreach ($permissions as $name => $description) {
            $permission = self::createPermissionIfNotExists($auth, $name, $description);
            if (!$auth->hasChild($role, $permission)) {
                $auth->addChild($role, $permission);
            }
        }

        if ($parentRole && $parent = $auth->getRole($parentRole)) {
            if (!$auth->hasChild($role, $parent)) {
                $auth->addChild($role, $parent);
            }
        }

        self::makeAdminSuperRole($auth);

        return $role;
    }

    /**
     * Создает роль, если она не существует
     */
    public static function createRoleIfNotExists(
        ManagerInterface $auth,
        string $roleName
    ): RbacRole {
        $role = $auth->getRole($roleName);
        if (!$role) {
            $role = $auth->createRole($roleName);
            $auth->add($role);
        }
        return $role;
    }

    /**
     * Создает разрешение, если оно не существует
     */
    public static function createPermissionIfNotExists(
        ManagerInterface $auth,
        string $permissionName,
        string $description = ''
    ): Permission {
        $permission = $auth->getPermission($permissionName);
        if (!$permission) {
            $permission = $auth->createPermission($permissionName);
            $permission->description = $description;
            $auth->add($permission);
        }
        return $permission;
    }

    /**
     * Назначает роль пользователю
     */
    public static function assignRoleToUser(
        ManagerInterface $auth,
        string $roleName,
        int $userId
    ): void {
        $role = $auth->getRole($roleName);
        if ($role && !$auth->getAssignment($roleName, $userId)) {
            $auth->assign($role, $userId);
        }
    }

    /**
     * Удаляет роль у пользователя
     */
    public static function removeRoleFromUser(
        ManagerInterface $auth,
        string $roleName,
        int $userId
    ): void {
        if ($auth->getAssignment($roleName, $userId)) {
            $auth->revoke($auth->getRole($roleName), $userId);
        }
    }
}

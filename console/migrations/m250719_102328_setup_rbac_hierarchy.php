<?php

use yii\db\Migration;
use App\Auth\Entity\User\Role;
use App\Auth\Service\RbacService;

/**
 * Class m250719_102328_setup_rbac_hierarchy
 * 
 * Миграция для настройки базовой иерархии RBAC:
 * - Создает основные роли (user, member, admin)
 * - Настраивает наследование (member -> user)
 * - Делает admin супер-ролью
 * - Добавляет базовые разрешения для member
 */
class m250719_102328_setup_rbac_hierarchy extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        // 1. Настраиваем базовую иерархию ролей
        RbacService::setupBasicHierarchy($auth);

        // 2. Добавляем специфические разрешения для member
        RbacService::createRoleWithPermissions(
            $auth,
            Role::MEMBER,
            [
                'viewSpecialContent' => 'Просмотр специального контента',
                'editOwnProfile' => 'Редактирование своего профиля'
            ],
            Role::USER // Наследует от user
        );

        // 3. Делаем admin супер-ролью (без назначения конкретному пользователю)
        RbacService::makeAdminSuperRole($auth);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        // Удаляем разрешения
        if ($permission = $auth->getPermission('viewSpecialContent')) {
            $auth->remove($permission);
        }
        if ($permission = $auth->getPermission('editOwnProfile')) {
            $auth->remove($permission);
        }

        // Очищаем иерархию (не удаляя сами роли)
        $admin = $auth->getRole(Role::ADMIN);
        if ($admin) {
            $auth->removeChildren($admin);
        }

        $member = $auth->getRole(Role::MEMBER);
        if ($member) {
            $auth->removeChildren($member);
        }
    }
}

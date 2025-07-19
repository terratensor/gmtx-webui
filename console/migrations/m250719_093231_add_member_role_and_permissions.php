<?php

use yii\db\Migration;
use App\Auth\Entity\User\Role;
use App\Auth\Service\RbacService;

/**
 * Class m250719_093231_add_member_role_and_permissions
 */
class m250719_093231_add_member_role_and_permissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        // Создаем базовые роли
        $user = $auth->getRole(Role::USER) ?: $auth->createRole(Role::USER);
        $member = $auth->getRole(Role::MEMBER) ?: $auth->createRole(Role::MEMBER);
        $admin = $auth->getRole(Role::ADMIN) ?: $auth->createRole(Role::ADMIN);

        if (!$auth->getRole(Role::USER)) $auth->add($user);
        if (!$auth->getRole(Role::MEMBER)) $auth->add($member);
        if (!$auth->getRole(Role::ADMIN)) $auth->add($admin);

        // Настраиваем иерархию
        $auth->addChild($member, $user);

        // Используем сервис для настройки admin
        RbacService::makeAdminSuperRole($auth);

        // Добавляем пример разрешения
        $viewSpecialContent = $auth->createPermission('viewSpecialContent');
        $auth->add($viewSpecialContent);
        $auth->addChild($member, $viewSpecialContent);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        // Удаляем только связи, сами роли остаются
        $admin = $auth->getRole(Role::ADMIN);
        if ($admin) {
            $auth->removeChildren($admin);
        }

        $member = $auth->getRole(Role::MEMBER);
        if ($member) {
            $auth->removeChildren($member);
        }

        // Удаляем пример разрешения (если нужно)
        $viewSpecialContent = $auth->getPermission('viewSpecialContent');
        if ($viewSpecialContent) {
            $auth->remove($viewSpecialContent);
        }
    }
}

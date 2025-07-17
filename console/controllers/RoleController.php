<?php

declare(strict_types=1);

namespace console\controllers;

use App\Auth\Entity\User\Email;
use App\Auth\Entity\User\Role;
use App\Auth\Entity\User\UserRepository;
use App\Rbac\Service\RoleManager;
use Yii;
use yii\helpers\ArrayHelper;

class RoleController extends \yii\console\Controller
{
    private $users;
    private $roleManager;

    public function __construct($id, $module, UserRepository $users, RoleManager $roleManager, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->users = $users;
        $this->roleManager = $roleManager;
    }

    public function actionAssign(): void
    {
        $input = $this->prompt('Email:', ['required' => true]);
        $user = $this->users->getByEmail(new Email($input));
        $role = $this->select('Role:', ArrayHelper::map(Yii::$app->authManager->getRoles(), 'name', 'description'));
        $this->roleManager->assign($user->id, new Role($role));
        $this->stdout('Done!' . PHP_EOL);
    }
}
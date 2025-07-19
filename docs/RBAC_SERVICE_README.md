# RBAC Service Documentation

Сервис для управления ролями и разрешениями в приложении.

# RBAC Service Documentation

[← Код класса RbacService](../src/Auth/Service/RbacService.php)

## Основные возможности

1. Настройка иерархии ролей
2. Создание ролей с разрешениями
3. Управление назначениями ролей
4. Автоматическое обновление прав admin

## Базовое использование

### 1. Инициализация RBAC (в миграции)

```php
use App\Auth\Service\RbacService;

// Настройка базовой иерархии
RbacService::setupBasicHierarchy(Yii::$app->authManager);

// Создание роли с разрешениями
RbacService::createRoleWithPermissions(
    Yii::$app->authManager,
    'moderator',
    [
        'approvePosts' => 'Может одобрять посты',
        'deleteComments' => 'Может удалять комментарии'
    ],
    'member' // Наследует от member
);
```

### 2. Назначение ролей

```php
// Назначение роли пользователю
RbacService::assignRoleToUser(
    Yii::$app->authManager,
    'admin',
    $userId
);

// Удаление роли
RbacService::removeRoleFromUser(
    Yii::$app->authManager,
    'moderator',
    $userId
);
```

### 3. Проверка прав в приложении

```php
// В контроллере
if (!Yii::$app->user->can('approvePosts')) {
    throw new ForbiddenHttpException('Нет прав');
}

// В представлении
<?php if (Yii::$app->user->can('editOwnProfile')): ?>
    <button>Редактировать профиль</button>
<?php endif; ?>
```

## Принципы работы

1. **Admin всегда супер-роль** - автоматически получает все новые права
2. **Иерархия наследования**:
   ```
   admin (все права)
   ├── moderator
   │   ├── member
   │   │   ├── user
   │   │   └── viewSpecialContent
   │   └── approvePosts
   └── другие роли
   ```
3. **Описания разрешений** - добавляются при создании

## Best Practices

1. Всегда используйте константы ролей из `App\Auth\Entity\User\Role`
2. Для новых фич сначала создавайте разрешения через сервис
3. Тестируйте миграции на staging перед production
4. После изменений очищайте кэш:
   ```bash
   php yii cache/flush-all
   ```

## Пример расширения

При добавлении новой фичи "Блог":

1. Создаем миграцию:
```php
public function safeUp()
{
    RbacService::createRoleWithPermissions(
        Yii::$app->authManager,
        'blog_editor',
        [
            'createPost' => 'Создание постов',
            'editAnyPost' => 'Редактирование любых постов'
        ],
        'member' // Наследует от member
    );
}
```

2. Проверяем права в коде:
```php
if (Yii::$app->user->can('editAnyPost')) {
    // ...
}
```
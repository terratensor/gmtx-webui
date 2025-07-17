<?php

declare(strict_types=1);

namespace App\Auth\Entity\User;

use App\Auth\Service\PasswordHasher;
use DateTimeImmutable;
use DomainException;
use yii\db\ActiveRecord;

/**
 * @property string $id
 * @property string $date
 * @property string $auth_key
 * @property string $email
 * @property string $status
 * @property string $role
 * @property string $password_hash
 * @property string $new_email
 * @property string $join_confirm_token_value
 * @property string $join_confirm_token_expires
 * @property string $password_reset_token_value
 * @property string $password_reset_token_expires
 * @property string $new_email_token_value
 * @property string $new_email_token_expires
 */
class User extends ActiveRecord
{
    private Id $_id;
    private DateTimeImmutable $_date;
    private AuthKey $authKey;
    private Email $_email;
    private Status $_status;
    private Role $_role;
    private ?string $passwordHash = null;
    private ?Token $joinConfirmToken = null;
    private ?Token $passwordResetToken = null;
    private ?Token $newEmailToken = null;
    private ?Email $newEmail = null;

    protected static function create(
        Id $id,
        DateTimeImmutable $date,
        AuthKey $authKey,
        Email $email,
        Status $status
    ): self {
        $user = new static();
        $user->_id = $id;
        $user->_date = $date;
        $user->authKey = $authKey;
        $user->_email = $email;
        $user->_status = $status;
        $user->_role = Role::user();

        return $user;
    }

    public static function requestJoinByEmail(
        Id $id,
        DateTimeImmutable $date,
        AuthKey $authKey,
        Email $email,
        string $passwordHash,
        Token $token
    ): self {
        $user = self::create($id, $date, $authKey, $email, Status::wait());
        $user->passwordHash = $passwordHash;
        $user->joinConfirmToken = $token;
        return $user;
    }

    public function confirmJoin(string $token, DateTimeImmutable $date): void
    {
        if ($this->joinConfirmToken === null) {
            throw new DomainException('Подтверждение регистрации не требуется.');
        }
        $this->joinConfirmToken->validate($token, $date);
        $this->_status = Status::active();
        $this->joinConfirmToken = null;
    }


    public function resendVerificationEmail(Token $token, DateTimeImmutable $date): void
    {
        if ($this->joinConfirmToken === null && $this->isActive()) {
            throw new DomainException('Подтверждение регистрации не требуется.');
        }
        $this->joinConfirmToken?->validateUpdate($date);

        $this->joinConfirmToken = $token;
    }

    public function validatePassword(string $password, PasswordHasher $hasher)
    {
        if ($this->passwordHash === null) {
            throw new DomainException('У пользователя нет пароля.');
        }
        if (!$hasher->validate($password, $this->passwordHash)) {
            throw new DomainException('Неверный пароль или email.');
        }
    }

    public function requestPasswordReset(Token $token, DateTimeImmutable $date): void
    {
        if (!$this->isActive()) {
            throw new DomainException('Пользователь не активен.');
        }
        if ($this->passwordResetToken !== null && !$this->passwordResetToken->isExpiredTo($date)) {
            throw new DomainException('Сброс пароля уже запрошен.');
        }
        $this->passwordResetToken = $token;
    }

    public function resetPassword(
        string $token,
        DateTimeImmutable $date,
        AuthKey $authKey,
        string $password,
        PasswordHasher $hasher
    ): void {
        if ($this->passwordResetToken === null) {
            throw new DomainException('Сброс не требуется.');
        }
        $this->passwordResetToken->validate($token, $date);
        $this->passwordResetToken = null;
        $this->passwordHash = $hasher->hash($password);
        $this->authKey = $authKey;
    }

    /**
     * @return bool
     */
    public function isWait(): bool
    {
        return $this->_status->isWait();
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->_status->isActive();
    }

    /**
     * @return Id
     */
    public function getId(): Id
    {
        return $this->_id;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getDate(): DateTimeImmutable
    {
        return $this->_date;
    }

    /**
     * @return Email
     */
    public function getEmail(): Email
    {
        return $this->_email;
    }

    /**
     * @return Status
     */
    public function getStatus(): Status
    {
        return $this->_status;
    }

    /**
     * @return string|null
     */
    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    /**
     * @return Token|null
     */
    public function getJoinConfirmToken(): ?Token
    {
        return $this->joinConfirmToken;
    }

    /**
     * @return Token|null
     */
    public function getPasswordResetToken(): ?Token
    {
        return $this->passwordResetToken;
    }

    /**
     * @return Token|null
     */
    public function getNewEmailToken(): ?Token
    {
        return $this->newEmailToken;
    }

    /**
     * @return Email|null
     */
    public function getNewEmail(): ?Email
    {
        return $this->newEmail;
    }

    /**
     * @return Role
     */
    public function getRole(): Role
    {
        return $this->_role;
    }

    public function behaviors(): array
    {
        return [
            UserBehavior::class,
        ];
    }

    public static function tableName(): string
    {
        return '{{%auth_users}}';
    }

    /**
     * @param Id $id
     */
    public function setId(Id $id): void
    {
        $this->_id = $id;
    }

    /**
     * @param DateTimeImmutable $date
     */
    public function setDate(DateTimeImmutable $date): void
    {
        $this->_date = $date;
    }

    /**
     * @param Email $email
     */
    public function setEmail(Email $email): void
    {
        $this->_email = $email;
    }

    /**
     * @param Status $status
     */
    public function setStatus(Status $status): void
    {
        $this->_status = $status;
    }

    /**
     * @param Role $role
     */
    public function setRole(Role $role): void
    {
        $this->_role = $role;
    }

    /**
     * @param string|null $passwordHash
     */
    public function setPasswordHash(?string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    /**
     * @param Token|null $joinConfirmToken
     */
    public function setJoinConfirmToken(?Token $joinConfirmToken): void
    {
        $this->joinConfirmToken = $joinConfirmToken;
    }

    /**
     * @param Token|null $passwordResetToken
     */
    public function setPasswordResetToken(?Token $passwordResetToken): void
    {
        $this->passwordResetToken = $passwordResetToken;
    }

    /**
     * @param Token|null $newEmailToken
     */
    public function setNewEmailToken(?Token $newEmailToken): void
    {
        $this->newEmailToken = $newEmailToken;
    }

    /**
     * @param Email|null $newEmail
     */
    public function setNewEmail(?Email $newEmail): void
    {
        $this->newEmail = $newEmail;
    }

    /**
     * @return AuthKey
     */
    public function getAuthKey(): AuthKey
    {
        return $this->authKey;
    }

    /**
     * @param AuthKey $authKey
     */
    public function setAuthKey(AuthKey $authKey): void
    {
        $this->authKey = $authKey;
    }

}

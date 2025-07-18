<?php

declare(strict_types=1);

namespace App\Auth\Entity\User;

use Webmozart\Assert\Assert;

class Role
{
    public const USER = 'user';
    public const ADMIN = 'admin';
    public const MEMBER = 'member';

    private string $name;

    public function __construct(string $name)
    {
        Assert::oneOf($name, [
            self::USER,
            self::MEMBER,
            self::ADMIN
        ]);

        $this->name = $name;
    }

    public static function user(): self
    {
        return new self(self::USER);
    }

    public static function member(): self
    {
        return new self(self::MEMBER);
    }

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}

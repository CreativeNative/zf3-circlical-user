<?php

declare(strict_types=1);

namespace CirclicalUser\Entity;

use CirclicalUser\Provider\UserInterface;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * A password-reset token. This is the thing that you would exchange in a forgot-password email
 * that the user can later consume to trigger a password change.
 *
 * @ORM\Entity
 * @ORM\Table(name="users_api_tokens")
 */
class UserApiToken
{
    use SecretIdPublicUuidTrait;

    public const SCOPE_NONE = 0;

    /**
     * @ORM\ManyToOne(targetEntity="CirclicalUser\Entity\User", inversedBy="api_tokens")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private UserInterface $user;

    /** @ORM\Column(type="datetime_immutable") */
    private DateTimeImmutable $creation_time;

    /** @ORM\Column(type="datetime_immutable", nullable=true) */
    private ?DateTimeImmutable $last_used = null;

    /** @ORM\Column(type="integer", options={"default":0, "unsigned": true}) */
    private int $times_used = 0;

    /** @ORM\Column(type="integer", options={"default":0, "unsigned": true}) */
    private int $scope = 0;

    /**
     * @param int $scope Push a bit-flag integer into this value to resolve scopes
     * @throws Exception
     */
    public function __construct(UserInterface $user, int $scope = self::SCOPE_NONE)
    {
        $this->setUser($user);
        $this->setCreationTime(new DateTimeImmutable('now', new DateTimeZone('UTC')));
        $this->addScope($scope);
        $this->setUuid(Uuid::uuid4());
    }

    public function addScope(int $newScope): void
    {
        $this->scope |= $newScope;
    }

    public function removeScope(int $removeScope): void
    {
        $this->scope &= ~$removeScope;
    }

    public function hasScope(int $checkForScope): bool
    {
        return ($this->scope & $checkForScope) === $checkForScope;
    }

    public function clearScope(): void
    {
        $this->scope = self::SCOPE_NONE;
    }

    public function getLastUsed(): ?DateTimeImmutable
    {
        return $this->last_used;
    }

    public function getTimesUsed(): int
    {
        return $this->times_used;
    }

    public function setTimesUsed(int $times_used): void
    {
        $this->times_used = $times_used;
    }

    /**
     * @throws Exception
     */
    public function tagUse(): void
    {
        $this->times_used++;
        $this->last_used = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(UuidInterface $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getToken(): string
    {
        return $this->uuid->toString();
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getCreationTime(): DateTimeImmutable
    {
        return $this->creation_time;
    }

    public function setCreationTime(DateTimeImmutable $creation_time): void
    {
        $this->creation_time = $creation_time;
    }
}

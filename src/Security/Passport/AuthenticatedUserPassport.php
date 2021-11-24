<?php

namespace App\Security\Passport;

use App\Security\User;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportTrait;
use Symfony\Component\Security\Http\Authenticator\Passport\UserPassportInterface;

class AuthenticatedUserPassport implements UserPassportInterface
{
    use PassportTrait;

    private UserInterface $user;

    public function __construct(UserInterface $user, array $badges = [])
    {
        $this->user = $user;
        foreach ($badges as $badge) {
            $this->addBadge($badge);
        }
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }
}
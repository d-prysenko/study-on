<?php

namespace App\Security\Token;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiPostAuthenticationToken extends AbstractToken
{
    private string $firewallName;

    /**
     * @param string[] $roles An array of roles
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $apiToken, string $firewallName, array $roles)
    {
        parent::__construct($roles);

        if ('' === $firewallName) {
            throw new \InvalidArgumentException('$firewallName must not be empty.');
        }

        //dd($this->JWTManager);

        $this->firewallName = $firewallName;
        $this->setUser($apiToken);
        //$this->token = $apiToken;

        // this token is meant to be used after authentication success, so it is always authenticated
        // you could set it as non authenticated later if you need to
        $this->setAuthenticated(true);
    }

    public function getUserIdentifier(): string
    {
        $userInfo = json_decode(base64_decode(explode('.', $this->getUser())[1]), true);
        return $userInfo['username'];
    }

    /**
     * This is meant to be only an authenticated token, where credentials
     * have already been used and are thus cleared.
     *
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return [];
    }

    public function getFirewallName(): string
    {
        return $this->firewallName;
    }


    /**
     * {@inheritdoc}
     */
    public function __serialize(): array
    {
        return [$this->firewallName, parent::__serialize()];
    }

    /**
     * {@inheritdoc}
     */
    public function __unserialize(array $data): void
    {
        [$this->firewallName, $parentData] = $data;
        parent::__unserialize($parentData);
    }
}
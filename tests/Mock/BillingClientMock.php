<?php

namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
use App\Exception\BillingUserAlreadyExists;
use App\Security\User;
use App\Service\BillingClient;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class BillingClientMock extends BillingClient
{

    private array $users;

    public function __construct()
    {
        $tempUser1 = new User();
        $tempUser1->setEmail('user@test.ru');
        $tempUser1->setPassword('qwerty');
        $tempUser1->setRoles(['ROLE_USER']);
        $tempUser1->setBalance(50.0);

        $this->users[] = $tempUser1;

        $tempUser2 = new User();
        $tempUser2->setEmail('admin@test.ru');
        $tempUser2->setPassword('password');
        $tempUser2->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_USER']);
        $tempUser2->setBalance(100.0);

        $this->users[] = $tempUser2;
    }

    /**
     * @throws BillingUnavailableException
     * @throws \JsonException
     */
    public function authenticate(string $jsonCredentials): string
    {
        $userCredentials = json_decode($jsonCredentials, true);

        $response = [];

        foreach ($this->users as $user) {
            if ($userCredentials['username'] === $user->getEmail() && $userCredentials['password'] === $user->getPassword()) {
                $response['token'] = "valid_token";
            }
        }

        $response = $this->jsonRequest('/api/v1/auth', CURLOPT_POST, $jsonCredentials);

        if (isset($response['token'])) {
            return $response['token'];
        }

        throw new CustomUserMessageAuthenticationException($response['message'] ?? "Unknown error");
    }

    /**
     * @throws BillingUnavailableException
     * @throws \JsonException
     * @throws \Exception
     */
    public function register(User $newUser): string
    {
//        $data = json_encode(['username' => $user->getEmail(), 'password' => $user->getPassword()], JSON_THROW_ON_ERROR);
//        $response = $this->jsonRequest('/api/v1/register', CURLOPT_POST, $data);

        $response = [];
        foreach ($this->users as $user) {
            if ($newUser->getEmail() === $user->getEmail()) {
                $response['message'] = "Email already in use!";
                break;
            }
        }

        if(!isset($response['message'])) {
            $response['token'] = "valid_token";
        }

        if (isset($response['token'])) {
            return $response['token'];
        }

        if (isset($response['message']) && $response['message'] === "Email already in use!") {
            throw new BillingUserAlreadyExists();
        }

        throw new \Exception($response['message'] ?? "Unknown error");
    }

    /**
     * @throws BillingUnavailableException
     * @throws \JsonException
     */
    public function getUser(string $apiToken): User
    {
        throw new \Exception("Implement getUser method!");
        $response = $this->jsonRequest('/api/v1/users/current', CURLOPT_HTTPGET,null, $apiToken);

        if (isset($response['code']) && $response['code'] == 200) {
            $user = new User();
            $user->setEmail($response['username']);
            $user->setRoles($response['roles']);
            $user->setBalance($response['balance']);
//            dd($response);
            return $user;
        }

        throw new \Exception($response['message'] ?? "Unknown error");
    }
}
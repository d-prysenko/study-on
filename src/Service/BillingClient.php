<?php

namespace App\Service;

use App\Exception\BillingUserAlreadyExists;
use App\Security\User;
use App\Exception\BillingUnavailableException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

class BillingClient
{
    private string $billingUrl;

    public function __construct()
    {
        $this->billingUrl = $_ENV['BILLING_URL'];
    }

    /**
     * @throws ServiceUnavailableHttpException
     * @throws \JsonException
     */
    public function authenticate(string $jsonCredentials): string
    {
        $response = $this->jsonRequest('/api/v1/auth', CURLOPT_POST, $jsonCredentials);

        if (isset($response['token'])) {
            return $response['token'];
        }

        throw new CustomUserMessageAuthenticationException($response['message'] ?? "Unknown error");
    }

    /**
     * @throws ServiceUnavailableHttpException
     * @throws BillingUserAlreadyExists
     * @throws \JsonException
     * @throws \Exception
     */
    public function register(User $user): string
    {
        $data = json_encode(['username' => $user->getEmail(), 'password' => $user->getPassword()], JSON_THROW_ON_ERROR);
        $response = $this->jsonRequest('/api/v1/register', CURLOPT_POST, $data);

        if (isset($response['token'])) {
            $user->setApiToken($response['token']);
            return $response['token'];
        }

        if (isset($response['message']) && $response['message'] === "Email already in use!") {
            throw new BillingUserAlreadyExists();
        }

        throw new \Exception($response['message'] ?? "Unknown error");
    }

    /**
     * @throws ServiceUnavailableHttpException
     * @throws \JsonException
     */
    public function getUser(string $apiToken): User
    {
        $response = $this->jsonRequest('/api/v1/users/current', CURLOPT_HTTPGET,null, $apiToken);

        if (isset($response['code']) && $response['code'] == 200) {
            $user = new User();
            $user->setEmail($response['username']);
            $user->setRoles($response['roles']);
            $user->setBalance($response['balance']);
            return $user;
        }

        throw new \Exception($response['message'] ?? "Unknown error");
    }

    /**
     * @param int $method CURLOPT_<method>
     * @throws ServiceUnavailableHttpException
     * @throws \JsonException
     */
    public function jsonRequest(string $urn, int $method, string $jsonPayload = null, string $apiToken = null): array
    {
        $url = $this->billingUrl . $urn;

        $headers = ["Content-Type: application/json"];

        if (!is_null($apiToken)) {
            $headers[] = "Authorization: Bearer $apiToken";
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, $method, true);
        if (!is_null($jsonPayload)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonPayload);
        }

        $resp = json_decode(curl_exec($curl), true, 512, JSON_THROW_ON_ERROR);

        curl_close($curl);

        if (curl_error($curl)) {
            throw new ServiceUnavailableHttpException();
        }

        return $resp;
    }
}
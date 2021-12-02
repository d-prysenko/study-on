<?php

namespace App\Service;

use App\Entity\Course;
use App\Exception\BillingUserAlreadyExists;
use App\Security\User;
use App\Exception\BillingUnavailableException;
use JsonException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

define('TRANSACTION_DEPOSIT', 0);
define('TRANSACTION_PAYMENT', 1);

class BillingClient
{
    private string $billingUrl;
    private JWTTokenManagerInterface $JWTManager;
    private TokenStorageInterface $storage;

    public function __construct(JWTTokenManagerInterface $JWTManager, TokenStorageInterface $storage)
    {
        $this->billingUrl = $_ENV['BILLING_URL'];
        $this->JWTManager = $JWTManager;
        $this->storage = $storage;
    }

    /**
     * @throws ServiceUnavailableHttpException
     * @throws JsonException
     */
    public function authenticate(string $jsonCredentials): User
    {
        $response = $this->jsonRequest('/api/v1/auth', CURLOPT_POST, $jsonCredentials);

        if (isset($response['token'], $response['refresh_token'])) {
            $user = $this->userFromToken($response['token']);
            $user->setRefreshToken($response['refresh_token']);
            return $user;
        }

        throw new CustomUserMessageAuthenticationException($response['message'] ?? "Unknown error");
    }

    /**
     * @throws ServiceUnavailableHttpException
     * @throws BillingUserAlreadyExists
     * @throws JsonException
     * @throws \Exception
     */
    public function register(User $user): User
    {
        $data = json_encode(['username' => $user->getEmail(), 'password' => $user->getPassword()], JSON_THROW_ON_ERROR);
        $response = $this->jsonRequest('/api/v1/register', CURLOPT_POST, $data);

        if (isset($response['token'], $response['refresh_token'])) {
            $user = $this->userFromToken($response['token']);
            $user->setRefreshToken($response['refresh_token']);
            return $user;
        }

        if (isset($response['message']) && $response['message'] === "Email already in use!") {
            throw new BillingUserAlreadyExists();
        }

        throw new \Exception($response['message'] ?? "Unknown error");
    }

    /**
     * @throws ServiceUnavailableHttpException
     * @throws JsonException
     * @throws \Exception
     */
    public function getCurrentUser(): User
    {
        $response = $this->jsonRequest('/api/v1/users/current', CURLOPT_HTTPGET, null, true);

        if (isset($response['code']) && $response['code'] === 200) {
            $user = new User();
            $user->setEmail($response['username']);
            $user->setRoles($response['roles']);
            $user->setBalance($response['balance']);
            return $user;
        }

        throw new \Exception($response['message'] ?? "Unknown error");
    }

    /**
     * @throws JsonException
     * @throws \Exception
     */
    public function createCourse(Course $course): void
    {
        $data = [
            'code' => $course->getCode(),
            'name' => $course->getName(),
            'price' => $course->getPrice(),
            'type' => $course->getType(),
        ];

        if ($course->getType() === "rent") {
            $data['duration'] = $course->getDuration()->format("P%yY%mM%dD");
        }

        $jsonData = json_encode($data, JSON_THROW_ON_ERROR);

        $response = $this->jsonRequest('/api/v1/courses', CURLOPT_POST, $jsonData, true);

        if (!isset($response['code'])) {
            throw new \Exception($response['message'] ?? "Api service error");
        }

        if ($response['code'] !== 201) {
            throw new \Exception($response['message']);
        }
    }

    /**
     * @throws JsonException
     */
    public function editCourse(Course $course): array
    {
        $data = [
            'code' => $course->getCode(),
            'name' => $course->getName(),
            'price' => $course->getPrice(),
            'type' => $course->getType(),
        ];

        if ($course->getType() === "rent") {
            $data['duration'] = $course->getDuration()->format("P%yY%mM%dD");
        }

        $jsonData = json_encode($data, JSON_THROW_ON_ERROR);

        $response = $this->jsonRequest("/api/v1/courses/{$course->getCode()}/edit", CURLOPT_POST, $jsonData, true);

        if (!isset($response['code'])) {
            throw new ServiceUnavailableHttpException();
        }

        return $response;
    }

    /**
     * @throws JsonException
     * @throws \Exception
     */
    public function deleteCourse(Course $course): void
    {
        $response = $this->jsonRequest("/api/v1/courses/{$course->getCode()}/delete", CURLOPT_POST, null, true);

        if (!isset($response['code'])) {
            throw new ServiceUnavailableHttpException();
        }

        if ($response['code'] !== 200 && isset($response['message'])) {
            throw new \Exception($response['message']);
        }
    }

    /**
     * @throws JsonException
     */
    public function getCourses(bool $assoc = false): array
    {
        $response = json_decode($this->request('/api/v1/courses', CURLOPT_HTTPGET), true, 512, JSON_THROW_ON_ERROR);
        if ($assoc) {
            $assocResponse = [];
            foreach ($response as $item) {
                $assocResponse[$item['code']] = $item;
            }
            return $assocResponse;
        }

        return $response;
    }

    /**
     * @throws JsonException
     */
    public function buyCourse(string $code): array
    {
        $response = $this->jsonRequest("/api/v1/courses/$code/buy", CURLOPT_POST, null, true);

        if (!isset($response['code'])) {
            throw new ServiceUnavailableHttpException();
        }

        return $response;
    }

    /**
     * @throws JsonException
     */
    public function getUserCourses(bool $assoc = false): array
    {
        $response = $this->jsonRequest('/api/v1/me/courses', CURLOPT_HTTPGET, null, true);
        if ($assoc) {
            $assocResponse = [];
            foreach ($response as $item) {
                $assocResponse[$item['code']] = $item;
            }
            return $assocResponse;
        }

        return $response;
    }

    public function refreshUser(User $user): User
    {
        $json = $this->request('/api/v1/token/refresh', CURLOPT_POST, "refresh_token=" . $user->getRefreshToken());

        try {
            $response = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            $user->setApiToken($response['token']);
            $user->setRefreshToken($response['refresh_token']);
        } catch (JsonException $e) {
        }

        return $user;
    }

    private function getUser(): ?UserInterface
    {
        if (null === $token = $this->storage->getToken()) {
            return null;
        }

        return $token->getUser();
    }

    private function getApiToken(): string
    {
        if (null === $user = $this->getUser()) {
            return "";
        }

        return $user->getUserIdentifier();
    }

    /**
     * @param int $method CURLOPT_<method>
     * @throws ServiceUnavailableHttpException
     * @throws JsonException
     */
    private function jsonRequest(
        string $urn,
        int $method,
        string $jsonPayload = null,
        bool $authenticated = false
    ): array {
        $headers[] = "Content-Type: application/json";
        if ($authenticated) {
            $headers[] = "Authorization: Bearer " . $this->getApiToken();
        }

        $resp = $this->request($urn, $method, $jsonPayload, $headers);
        return json_decode($resp, true, 512, JSON_THROW_ON_ERROR);
    }

    private function request(string $urn, int $method, string $postFields = null, array $headers = [])
    {
        $url = $this->billingUrl . $urn;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, $method, true);
        if (!is_null($postFields)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        }

        $resp = curl_exec($curl);

        $e = curl_error($curl);
        curl_close($curl);

        if ($e) {
            throw new ServiceUnavailableHttpException(3, $e);
        }

        return $resp;
    }

    private function userFromToken(string $apiToken): User
    {
        $userInfo = $this->JWTManager->parse($apiToken);

        return (new User())
            ->setApiToken($apiToken)
            ->setEmail($userInfo['username'])
            ->setRoles($userInfo['roles'])
        ;
    }
}

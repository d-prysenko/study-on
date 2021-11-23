<?php

namespace App\Service;

use App\Security\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticatorManager;
use Symfony\Component\Security\Http\Authentication\AuthenticatorManagerInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BillingAuthenticationManager implements AuthenticatorManagerInterface, UserAuthenticatorInterface
{
    private TokenStorageInterface $tokenStorage;
    private EventDispatcherInterface $eventDispatcher;
    private string $firewallName;
    private JWTTokenManagerInterface $JWTManager;


    public function __construct(JWTTokenManagerInterface $JWTManager, TokenStorageInterface $tokenStorage, EventDispatcherInterface $eventDispatcher, string $firewallName = 'main')
    {
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;
        $this->firewallName = $firewallName;
        $this->JWTManager = $JWTManager;
    }

    /**
     * @param BadgeInterface[] $badges Optionally, pass some Passport badges to use for the manual login
     */
    public function authenticateUser(UserInterface $user, AuthenticatorInterface $authenticator, Request $request, array $badges = []): ?Response
    {
        $passport =  new SelfValidatingPassport(
            new UserBadge(
                $user->getUserIdentifier(),
                function (string $apiToken) {
                    $userInfo = $this->JWTManager->parse($apiToken);

                    $user = new User();
                    $user->setApiToken($apiToken);
                    $user->setEmail($userInfo['username']);
                    $user->setRoles($userInfo['roles']);
                    return $user;
                }
            ),
            [
                new CsrfTokenBadge('authenticate', $request->get('_csrf_token')),
                new RememberMeBadge()
            ]
        );

        $token = $authenticator->createAuthenticatedToken($passport, $this->firewallName);

        // announce the authenticated token
        $token = $this->eventDispatcher->dispatch(new AuthenticationTokenCreatedEvent($token, $passport))->getAuthenticatedToken();

        // authenticate this in the system
        return $this->handleAuthenticationSuccess($token, $passport, $request, $authenticator);
    }

    private function handleAuthenticationSuccess(TokenInterface $authenticatedToken, PassportInterface $passport, Request $request, AuthenticatorInterface $authenticator): ?Response
    {
        // @deprecated since Symfony 5.3
        $user = $authenticatedToken->getUser();
        if ($user instanceof UserInterface && !method_exists($user, 'getUserIdentifier')) {
            trigger_deprecation('symfony/security-core', '5.3', 'Not implementing method "getUserIdentifier(): string" in user class "%s" is deprecated. This method will replace "getUsername()" in Symfony 6.0.', get_debug_type($authenticatedToken->getUser()));
        }

        $this->tokenStorage->setToken($authenticatedToken);

        $response = $authenticator->onAuthenticationSuccess($request, $authenticatedToken, $this->firewallName);
        if ($authenticator instanceof InteractiveAuthenticatorInterface && $authenticator->isInteractive()) {
            $loginEvent = new InteractiveLoginEvent($request, $authenticatedToken);
            $this->eventDispatcher->dispatch($loginEvent, SecurityEvents::INTERACTIVE_LOGIN);
        }

        $this->eventDispatcher->dispatch($loginSuccessEvent = new LoginSuccessEvent($authenticator, $passport, $authenticatedToken, $request, $response, $this->firewallName));

        return $loginSuccessEvent->getResponse();
    }

    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticateRequest(Request $request): ?Response
    {
        return new Response();
    }
}
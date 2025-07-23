<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class TokenGuardAuthenticator extends AbstractGuardAuthenticator
{
    private string $apiToken;

    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('Authorization');
    }

    public function getCredentials(Request $request)
    {
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7); // Remove "Bearer "
        }

        return null;
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        if ($credentials === $this->apiToken) {
            // Return a dummy user
            return new class implements UserInterface {
                public function getRoles() { return ['ROLE_API']; }
                public function getPassword() {}
                public function getSalt() {}
                public function getUsername() { return 'api_user'; }
                public function eraseCredentials() {}
                public function getUserIdentifier() { return 'api_user'; }
            };
        }

        return null;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $credentials === $this->apiToken;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        return null; // Let the request continue
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new JsonResponse(['error' => 'Authentication failed'], 401);
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new JsonResponse(['error' => 'Authentication required'], 401);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}

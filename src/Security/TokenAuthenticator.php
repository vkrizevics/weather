<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TokenAuthenticator extends AbstractAuthenticator
{
    private string $apiToken;

    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
    }

    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('No Bearer token found.');
        }

        $token = substr($authHeader, 7);
        if ($token !== $this->apiToken) {
            throw new AuthenticationException('Invalid token.');
        }

        return new SelfValidatingPassport(
            new UserBadge('api_user', function () {
                return new class implements UserInterface {
                    public function getRoles() { return ['ROLE_API']; }
                    public function getPassword() {}
                    public function getSalt() {}
                    public function getUserIdentifier() { return 'api_user'; }
                    public function getUsername(): string { return 'api_user'; }
                    public function eraseCredentials() {}
                };
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response {
        return null;
    }

    // public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): \Symfony\Component\HttpFoundation\Response
    // {
    //     return new JsonResponse(['error' => 'Authentication failed'], 401);
    // }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse([
            'error' => 'Authentication failed'
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }
}

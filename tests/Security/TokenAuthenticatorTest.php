<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\TokenAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class TokenAuthenticatorTest extends TestCase
{
    private TokenAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->authenticator = new TokenAuthenticator($_ENV['API_TOKEN']);
    }

    public function testSupportsWithAuthorizationHeader(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ' . $_ENV['API_TOKEN']);

        $authenticator = new TokenAuthenticator($_ENV['API_TOKEN']);

        $this->assertTrue($authenticator->supports($request));
    }

    public function testUserIdentifierAndUsernameMethodsAreCorrect(): void
    {
        $request = new Request([], [], [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $_ENV['API_TOKEN']
        ]);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);

        /** @var UserBadge $userBadge */
        $userBadge = $passport->getBadge(UserBadge::class);
        $user = $userBadge->getUser();

        $this->assertInstanceOf(UserInterface::class, $user);
        $this->assertEquals('api_user', $user->getUserIdentifier());
        $this->assertEquals('api_user', $user->getUsername());
    }
}


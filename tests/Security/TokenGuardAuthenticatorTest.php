<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\TokenGuardAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class TokenGuardAuthenticatorTest extends TestCase
{
    private const VALID_TOKEN = 'MY_SECRET_TOKEN';

    public function testSupportsWithAuthorizationHeader(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ' . self::VALID_TOKEN);

        $authenticator = new TokenGuardAuthenticator(self::VALID_TOKEN);

        $this->assertTrue($authenticator->supports($request));
    }
}


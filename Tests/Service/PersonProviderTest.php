<?php

namespace Rollbar\Symfony\RollbarBundle\Tests\Service;

use ArrayObject;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rollbar\Symfony\RollbarBundle\Service\PersonProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PersonProviderTest extends TestCase
{
    private MockObject|TokenStorageInterface $tokenStorage;
    private MockObject|NormalizerInterface $normalizer;
    private PersonProvider $personProvider;

    public function setUp(): void
    {
        $this->tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)
            ->onlyMethods(['getToken', 'setToken'])
            ->getMock();

        $this->normalizer = $this->getMockBuilder(NormalizerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['normalize', 'supportsNormalization'])
            ->getMock();

        $this->personProvider = new PersonProvider($this->tokenStorage, $this->normalizer);
    }

    public function testNullToken(): void
    {
        $this->tokenStorage->method('getToken')
            ->willReturn(null);

        $info = $this->personProvider->getUserInfo();

        $this->assertEquals(null, $info);
    }

    public function testNullUser(): void
    {
        $token = $this->getMockBuilder(UsernamePasswordToken::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser'])
            ->getMock();

        $token->method('getUser')
            ->willReturn(null);

        $this->tokenStorage->method('getToken')
            ->willReturn($token);

        $info = $this->personProvider->getUserInfo();

        $this->assertEquals(null, $info);
    }

    public static function normalizedUserData(): Generator
    {
        yield 'normalized data: null' => [null, null];
        yield 'normalized data: int' => [12, 12];
        yield 'normalized data: float' => [12.153, 12.153];
        yield 'normalized data: string' => ['john.doe@example.com', 'john.doe@example.com'];
        yield 'normalized data: array' => [
            ['email' => 'john.doe@example.com', 'name' => 'John'],
            ['email' => 'john.doe@example.com', 'name' => 'John'],
        ];
        yield 'normalized data: ArrayObject' => [
            new ArrayObject(['email' => 'john.doe@example.com', 'name' => 'John']),
            new ArrayObject(['email' => 'john.doe@example.com', 'name' => 'John']),
        ];
        yield 'normalized data: bool' => [true, true];
    }

    /**
     * @dataProvider normalizedUserData
     */
    public function testUserInTokenStorage($normalizedData, $expected): void
    {
        $token = $this->getMockBuilder(UsernamePasswordToken::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser'])
            ->getMock();

        $token->method('getUser')
            ->willReturn(new InMemoryUser('john-doe', 'pass'));

        $this->tokenStorage->method('getToken')
            ->willReturn($token);

        $this->normalizer->method('normalize')
            ->willReturn($normalizedData);

        $info = $this->personProvider->getUserInfo();

        $this->assertEquals($expected, $info);
    }
}

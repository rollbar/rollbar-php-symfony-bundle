<?php

namespace Rollbar\Symfony\RollbarBundle\Tests\Payload;

use Rollbar\Symfony\RollbarBundle\Payload\ErrorItem;
use Rollbar\Symfony\RollbarBundle\Payload\Generator;
use Rollbar\Symfony\RollbarBundle\Payload\TraceChain;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;

class GeneratorTest extends KernelTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();
    }

    public function testGetContainer(): void
    {
        $generator = $this->getGenerator();

        $result = $generator->getContainer();

        $this->assertInstanceOf(ContainerInterface::class, $result);
    }

    public function testGetKernel(): void
    {
        $generator = $this->getGenerator();

        $kernel = $generator->getKernel();

        $this->assertEquals(static::$kernel, $kernel);
    }

    /**
     * Get class method.
     *
     * @throws \ReflectionException
     */
    protected static function getClassMethod(string $class, string $method): \ReflectionMethod
    {
        $class  = new \ReflectionClass($class);
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * Test getServerInfo.
     * @throws \ReflectionException
     */
    public function testGetServerInfo(): void
    {
        $generator = $this->getGenerator();

        $method = static::getClassMethod(Generator::class, 'getServerInfo');
        $data   = $method->invoke($generator);

        $this->assertArrayHasKey('host', $data);
        $this->assertArrayHasKey('root', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('file', $data);
        $this->assertArrayHasKey('argv', $data);

        $this->assertEquals($data['host'], gethostname());
        $this->assertEquals($data['root'], static::$kernel->getProjectDir());
        $this->assertEquals($data['user'], get_current_user());
    }

    /**
     * Test getRequestInfo.
     * @throws \ReflectionException
     */
    public function testGetRequestInfo(): void
    {
        $container = static::$kernel->getContainer();
        $generator = $this->getGenerator();

        $request = $container->get('request_stack')->getCurrentRequest();
        if (empty($request)) {
            $request = new Request();
        }

        $method = static::getClassMethod(Generator::class, 'getRequestInfo');
        $data   = $method->invoke($generator);

        $this->assertArrayHasKey('url', $data);
        $this->assertArrayHasKey('method', $data);
        $this->assertArrayHasKey('headers', $data);
        $this->assertArrayHasKey('query_string', $data);
        $this->assertArrayHasKey('body', $data);
        $this->assertArrayHasKey('user_ip', $data);

        $this->assertEquals($data['url'], $request->getRequestUri());
        $this->assertEquals($data['method'], $request->getMethod());
        $this->assertEquals($data['headers'], $request->headers->all());
        $this->assertEquals($data['query_string'], $request->getQueryString());
        $this->assertEquals($data['body'], $request->getContent());
        $this->assertEquals($data['user_ip'], $request->getClientIp());
    }

    /**
     * Test getErrorPayload.
     * @throws \Exception
     */
    public function testGetErrorPayload(): void
    {
        $generator = $this->getGenerator();

        $serverMethod = static::getClassMethod(Generator::class, 'getServerInfo');
        $serverInfo   = $serverMethod->invoke($generator);

        $requestMethod = static::getClassMethod(Generator::class, 'getRequestInfo');
        $requestInfo   = $requestMethod->invoke($generator);

        $item = new ErrorItem();
        $code = E_ERROR;
        $msg  = 'testGetErrorPayload';
        $file = __FILE__;
        $line = random_int(1, 10);

        [$message, $payload] = $generator->getErrorPayload($code, $msg, $file, $line);

        $this->assertEquals($msg, $message);

        $this->assertArrayHasKey('body', $payload);
        $this->assertArrayHasKey('request', $payload);
        $this->assertArrayHasKey('environment', $payload);
        $this->assertArrayHasKey('framework', $payload);
        $this->assertArrayHasKey('server', $payload);

        $body = ['trace' => $item($code, $msg, $file, $line)];

        $this->assertEquals($body, $payload['body']);
        $this->assertEquals($requestInfo, $payload['request']);
        $this->assertEquals(static::$kernel->getEnvironment(), $payload['environment']);
        $this->assertEquals(Kernel::VERSION, $payload['framework']);
        $this->assertEquals($serverInfo, $payload['server']);
    }

    /**
     * Test getExceptionPayload.
     * @throws \ReflectionException
     */
    public function testGetExceptionPayload(): void
    {
        $generator = $this->getGenerator();

        $serverMethod = static::getClassMethod(Generator::class, 'getServerInfo');
        $serverInfo   = $serverMethod->invoke($generator);

        $requestMethod = static::getClassMethod(Generator::class, 'getRequestInfo');
        $requestInfo   = $requestMethod->invoke($generator);

        $msg       = 'getExceptionPayload';
        $code      = E_ERROR;
        $exception = new \Exception($msg, $code);
        $chain     = new TraceChain();

        [$message, $payload] = $generator->getExceptionPayload($exception);

        $this->assertStringContainsString($msg, $message);

        $this->assertArrayHasKey('body', $payload);
        $this->assertArrayHasKey('request', $payload);
        $this->assertArrayHasKey('environment', $payload);
        $this->assertArrayHasKey('framework', $payload);
        $this->assertArrayHasKey('server', $payload);

        $body = ['trace_chain' => $chain($exception)];

        $this->assertEquals($body, $payload['body']);
        $this->assertEquals($requestInfo, $payload['request']);
        $this->assertEquals(static::$kernel->getEnvironment(), $payload['environment']);
        $this->assertEquals(Kernel::VERSION, $payload['framework']);
        $this->assertEquals($serverInfo, $payload['server']);
    }

    private function getGenerator(): Generator
    {
        $container = static::$kernel->getContainer();
        /** @var $generator Generator */
        $generator = $container->get('test.' . Generator::class);
        return $generator;
    }
}

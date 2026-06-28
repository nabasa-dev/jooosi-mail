<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JooosiMailDeps\Symfony\Component\Mailer\Test;

use JooosiMailDeps\PHPUnit\Framework\Attributes\DataProvider;
use JooosiMailDeps\PHPUnit\Framework\TestCase;
use JooosiMailDeps\Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use JooosiMailDeps\Symfony\Component\Mailer\Transport\Dsn;
use JooosiMailDeps\Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use JooosiMailDeps\Symfony\Component\Mailer\Transport\TransportInterface;
abstract class AbstractTransportFactoryTestCase extends TestCase
{
    protected const USER = 'u$er';
    protected const PASSWORD = 'pa$s';
    abstract public function getFactory(): TransportFactoryInterface;
    /**
     * @psalm-return iterable<array{0: Dsn, 1: bool}>
     */
    abstract public static function supportsProvider(): iterable;
    /**
     * @psalm-return iterable<array{0: Dsn, 1: TransportInterface}>
     */
    abstract public static function createProvider(): iterable;
    /**
     * @psalm-return iterable<array{0: Dsn, 1?: string|null}>
     */
    abstract public static function unsupportedSchemeProvider(): iterable;
    /**
     * @dataProvider supportsProvider
     */
    #[DataProvider('supportsProvider')]
    public function testSupports(Dsn $dsn, bool $supports)
    {
        $factory = $this->getFactory();
        $this->assertSame($supports, $factory->supports($dsn));
    }
    /**
     * @dataProvider createProvider
     */
    #[DataProvider('createProvider')]
    public function testCreate(Dsn $dsn, TransportInterface $transport)
    {
        $factory = $this->getFactory();
        $this->assertEquals($transport, $factory->create($dsn));
        if (str_contains('smtp', $dsn->getScheme())) {
            $this->assertStringMatchesFormat($dsn->getScheme() . '://%S' . $dsn->getHost() . '%S', (string) $transport);
        }
    }
    /**
     * @dataProvider unsupportedSchemeProvider
     */
    #[DataProvider('unsupportedSchemeProvider')]
    public function testUnsupportedSchemeException(Dsn $dsn, ?string $message = null)
    {
        $factory = $this->getFactory();
        $this->expectException(UnsupportedSchemeException::class);
        if (null !== $message) {
            $this->expectExceptionMessage($message);
        }
        $factory->create($dsn);
    }
}

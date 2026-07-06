<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Rector\Rule\DowngradeAsymmetricVisibilityRector;

use Iterator;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

/**
 * @since 1.0.7
 */
final class DowngradeAsymmetricVisibilityRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData
     *
     * @since 1.0.7
     */
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    /**
     * @return Iterator<string, mixed>
     *
     * @since 1.0.7
     */
    public static function provideData(): Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    /**
     * @since 1.0.7
     */
    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/config.php';
    }
}

<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Service;

use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions */
final class DomainOptionsUnitTest extends TestCase
{
    /**
     * @covers ::default
     * @covers ::exceptions
     */
    public function testBasicFunctionality(): void
    {
        $sut = self::createDomainOptions();

        self::assertSame(TranslationTestHelper::DOMAIN_DEFAULT, $sut->default(), 'Returned correct default domain.');
        self::assertSame(TranslationTestHelper::DOMAIN_EXCEPTIONS, $sut->exceptions(), 'Returned correct typecast exceptions domain.');
    }
}

<?php

declare(strict_types=1);
namespace TYPO3\Documentation\Screenshots\Tests\Unit\Comparison;

/*
 * This file is part of the TYPO3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\Documentation\Screenshots\Comparison\TextFileComparison;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TextFileComparisonTest extends UnitTestCase
{
    protected string $vfsPath;
    protected string $vfsPathPlaceholder = '::vfs-path::';
    protected string $fixturePath = __DIR__ . '/../Fixtures';

    protected function setUp(): void
    {
        parent::setUp();

        $this->vfsPath = vfsStream::setup('public')->url();

        Environment::initialize(
            new ApplicationContext('Testing'),
            true,
            false,
            '',
            '',
            '',
            '',
            '',
            ''
        );
    }

    /**
     * @test
     * @dataProvider resolveDataProvider
     *
     * @param array $config
     * @param array $expected
     */
    public function resolve(array $config, array $expected): void
    {
        array_walk($config, [$this, 'resolveVfsPath']);

        $comparison = new TextFileComparison(...$config);
        $comparison->process();

        self::assertEquals($expected['difference'], $comparison->getDifference());
        self::assertEquals($expected['fileActualExists'], $comparison->getFileActual()->isExisting());
        self::assertEquals($expected['fileOriginalExists'], $comparison->getFileOriginal()->isExisting());
        self::assertEquals($expected['fileDiffExists'], $comparison->getFileDiff()->isExisting());
        if (isset($expected['fileDiffContent'])) {
            self::assertEquals($expected['fileDiffContent'], trim(file_get_contents($comparison->getFileDiff()->getPath())));
        }
        self::assertMatchesRegularExpression($expected['fileActualUrlWithCacheBust'], $comparison->getFileActual()->getUriWithCacheBust());
        self::assertMatchesRegularExpression($expected['fileOriginalUrlWithCacheBust'], $comparison->getFileOriginal()->getUriWithCacheBust());
        self::assertMatchesRegularExpression($expected['fileDiffUrlWithCacheBust'], $comparison->getFileDiff()->getUriWithCacheBust());
    }

    protected function resolveVfsPath(&$value): void
    {
        if (is_string($value) && strpos($value, $this->vfsPathPlaceholder) === 0) {
            $value = str_replace($this->vfsPathPlaceholder, $this->vfsPath, $value);
        }
    }

    public function resolveDataProvider(): array
    {
        return [
            'original-file-does-not-exist' => [
                [
                    $this->fixturePath . DIRECTORY_SEPARATOR . 'textfile-actual.rst.txt',
                    $this->vfsPathPlaceholder . DIRECTORY_SEPARATOR . 'textfile-original.rst.txt',
                    $this->vfsPathPlaceholder . DIRECTORY_SEPARATOR . 'diff.txt',
                    '/textfile-actual.rst.txt',
                    '/textfile-original.rst.txt',
                    '/diff.txt',
                ],
                [
                    'difference' => 1,
                    'fileActualExists' => true,
                    'fileOriginalExists' => false,
                    'fileDiffExists' => true,
                    'fileDiffContent' => <<<'NOWDOC'
--- Original
+++ New
@@ @@
+.. Automatic screenshot: Remove this line if you want to manually change this file
+
+.. figure:: /Images/AutomaticScreenshots/Actual.png
+   :class: with-shadow
NOWDOC,
                    'fileActualUrlWithCacheBust' => '#^/textfile-actual.rst.txt\?bust=[0-9]+$#',
                    'fileOriginalUrlWithCacheBust' => '#^/textfile-original.rst.txt$#',
                    'fileDiffUrlWithCacheBust' => '#^/diff.txt\?bust=[0-9]+$#',
                ]
            ],
            'original-and-actual-differ' => [
                [
                    $this->fixturePath . DIRECTORY_SEPARATOR . 'textfile-actual.rst.txt',
                    $this->fixturePath . DIRECTORY_SEPARATOR . 'textfile-original.rst.txt',
                    $this->vfsPathPlaceholder . DIRECTORY_SEPARATOR . 'diff.txt',
                    '/textfile-actual.rst.txt',
                    '/textfile-original.rst.txt',
                    '/diff.txt',
                ],
                [
                    'difference' => 0.5,
                    'fileActualExists' => true,
                    'fileOriginalExists' => true,
                    'fileDiffExists' => true,
                    'fileDiffContent' => <<<'NOWDOC'
--- Original
+++ New
@@ @@
 .. Automatic screenshot: Remove this line if you want to manually change this file
 
-.. figure:: /Images/AutomaticScreenshots/Original.png
+.. figure:: /Images/AutomaticScreenshots/Actual.png
    :class: with-shadow
NOWDOC,
                    'fileActualUrlWithCacheBust' => '#^/textfile-actual.rst.txt\?bust=[0-9]+$#',
                    'fileOriginalUrlWithCacheBust' => '#^/textfile-original.rst.txt\?bust=[0-9]+$#',
                    'fileDiffUrlWithCacheBust' => '#^/diff.txt\?bust=[0-9]+$#',
                ]
            ],
            'both-files-match' => [
                [
                    $this->fixturePath . DIRECTORY_SEPARATOR . 'textfile-actual.rst.txt',
                    $this->fixturePath . DIRECTORY_SEPARATOR . 'textfile-actual.rst.txt',
                    $this->vfsPathPlaceholder . DIRECTORY_SEPARATOR . 'diff.txt',
                    '/textfile-actual.rst.txt',
                    '/textfile-original.rst.txt',
                    '/diff.txt',
                ],
                [
                    'difference' => 0,
                    'fileActualExists' => true,
                    'fileOriginalExists' => true,
                    'fileDiffExists' => false,
                    'fileActualUrlWithCacheBust' => '#^/textfile-actual.rst.txt\?bust=[0-9]+$#',
                    'fileOriginalUrlWithCacheBust' => '#^/textfile-original.rst.txt\?bust=[0-9]+$#',
                    'fileDiffUrlWithCacheBust' => '#^/diff.txt$#',
                ]
            ]
        ];
    }
}

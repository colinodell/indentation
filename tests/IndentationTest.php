<?php

declare(strict_types=1);

/*
 * This file is part of the colinodell/indentation package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * Forked from detect-indent,
 * (c) Sindre Sorhus <sindresorhus@gmail.com> (https://sindresorhus.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ColinODell\Indentation\Tests;

use ColinODell\Indentation\Indentation;
use PHPUnit\Framework\TestCase;

final class IndentationTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $indentation = new Indentation(4, Indentation::TYPE_SPACE);

        self::assertSame(4, $indentation->getAmount());
        self::assertSame(Indentation::TYPE_SPACE, $indentation->getType());
        self::assertSame('    ', (string) $indentation);

        $indentation = new Indentation(1, Indentation::TYPE_TAB);

        self::assertSame(1, $indentation->getAmount());
        self::assertSame(Indentation::TYPE_TAB, $indentation->getType());
        self::assertSame("\t", (string) $indentation);
    }

    /**
     * @dataProvider provideCasesForTestDetect
     */
    public function testDetect(string $filename, Indentation $expected, string $asString): void
    {
        $actual = Indentation::detect($filename);
        self::assertEquals($expected, $actual);
        self::assertSame($asString, (string) $actual);
    }

    /**
     * @return iterable<array<mixed>>
     */
    public function provideCasesForTestDetect(): iterable
    {
        yield 'Detect space indentation' => [
            $this->loadFixture('space.js'),
            new Indentation(4, Indentation::TYPE_SPACE),
            '    ',
        ];

        yield 'Detect tab indentation' => [
            $this->loadFixture('tab.js'),
            new Indentation(1, Indentation::TYPE_TAB),
            "\t",
        ];

        yield 'Detect multiple tabs' => [
            $this->loadFixture('tab-four.js'),
            new Indentation(4, Indentation::TYPE_TAB),
            "\t\t\t\t",
        ];

        yield 'Detect equal tabs and spaces' => [
            $this->loadFixture('mixed-tab.js'),
            new Indentation(1, Indentation::TYPE_TAB),
            "\t",
        ];

        yield 'Detect indent of a file with mostly spaces' => [
            $this->loadFixture('mixed-space.js'),
            new Indentation(4, Indentation::TYPE_SPACE),
            '    ',
        ];

        yield 'Detect indent of a weirdly indented vendor prefixed CSS' => [
            $this->loadFixture('vendor-prefixed-css.css'),
            new Indentation(4, Indentation::TYPE_SPACE),
            '    ',
        ];

        yield 'Return 0 when these is no indentation' => [
            '<ul></ul>',
            new Indentation(0, Indentation::TYPE_UNKNOWN),
            '',
        ];

        yield 'Indentation for fifty-fifty indented files with spaces first' => [
            $this->loadFixture('fifty-fifty-space-first.js'),
            new Indentation(4, Indentation::TYPE_SPACE),
            '    ',
        ];

        yield 'Indentation for fifty-fifty indented files with tabs first' => [
            $this->loadFixture('fifty-fifty-tab-first.js'),
            new Indentation(1, Indentation::TYPE_TAB),
            "\t",
        ];

        yield 'Indentation for files with spaces and tabs last' => [
            $this->loadFixture('space-tab-last.js'),
            new Indentation(1, Indentation::TYPE_TAB),
            "\t",
        ];

        yield 'Indentation of a file with single line comments' => [
            $this->loadFixture('single-space-ignore.js'),
            new Indentation(4, Indentation::TYPE_SPACE),
            '    ',
        ];

        yield 'Indentation for files with single spaces only' => [
            $this->loadFixture('single-space-only.js'),
            new Indentation(1, Indentation::TYPE_SPACE),
            ' ',
        ];
    }

    /**
     * @dataProvider provideCasesForTestChange
     */
    public function testChange(string $contents, Indentation $indentation, string $expected): void
    {
        $actual = Indentation::change($contents, $indentation);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array<mixed>>
     */
    public function provideCasesForTestChange(): iterable
    {
        yield 'Empty string' => [
            '',
            new Indentation(4, Indentation::TYPE_SPACE),
            '',
        ];

        yield 'No indentation' => [
            '<ul></ul>',
            new Indentation(4, Indentation::TYPE_SPACE),
            '<ul></ul>',
        ];

        yield 'Two spaces to four spaces' => [
            "<div>\n  <ul>\n    <li>yay</li>\n  </ul>\n</div>",
            new Indentation(4, Indentation::TYPE_SPACE),
            "<div>\n    <ul>\n        <li>yay</li>\n    </ul>\n</div>",
        ];

        yield 'Four spaces to two spaces' => [
            "<div>\n    <ul>\n        <li>yay</li>\n    </ul>\n</div>",
            new Indentation(2, Indentation::TYPE_SPACE),
            "<div>\n  <ul>\n    <li>yay</li>\n  </ul>\n</div>",
        ];

        yield 'Two spaces to tabs' => [
            "<div>\n  <ul>\n    <li>yay</li>\n  </ul>\n</div>",
            new Indentation(1, Indentation::TYPE_TAB),
            "<div>\n\t<ul>\n\t\t<li>yay</li>\n\t</ul>\n</div>",
        ];

        yield 'Four spaces to tabs' => [
            "<div>\n    <ul>\n        <li>yay</li>\n    </ul>\n</div>",
            new Indentation(1, Indentation::TYPE_TAB),
            "<div>\n\t<ul>\n\t\t<li>yay</li>\n\t</ul>\n</div>",
        ];

        yield 'Tabs to four spaces' => [
            "<div>\n\t<ul>\n\t\t<li>yay</li>\n\t</ul>\n</div>",
            new Indentation(4, Indentation::TYPE_SPACE),
            "<div>\n    <ul>\n        <li>yay</li>\n    </ul>\n</div>",
        ];

        yield 'Two tabs to two spaces' => [
            "<div>\n\t\t<ul>\n\t\t\t\t<li>yay</li>\n\t\t</ul>\n</div>",
            new Indentation(2, Indentation::TYPE_SPACE),
            "<div>\n  <ul>\n    <li>yay</li>\n  </ul>\n</div>",
        ];

        yield 'Newlines are preserved' => [
            "\n<div>\n  <ul>\r\n    <li>yay</li>\r  </ul>\n</div>\n\n",
            new Indentation(4, Indentation::TYPE_SPACE),
            "\n<div>\n    <ul>\r\n        <li>yay</li>\r    </ul>\n</div>\n\n",
        ];
    }

    /**
     * @dataProvider provideCasesForTestIndent
     */
    public function testIndent(string $input, Indentation $indentation, string $expected): void
    {
        $actual = Indentation::indent($input, $indentation);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array<mixed>>
     */
    public function provideCasesForTestIndent(): iterable
    {
        yield 'Empty string' => [
            '',
            new Indentation(4, Indentation::TYPE_SPACE),
            '',
        ];

        yield 'No indentation' => [
            '<ul></ul>',
            new Indentation(0, Indentation::TYPE_SPACE),
            '<ul></ul>',
        ];

        yield 'Unknown indentation' => [
            '<ul></ul>',
            new Indentation(4, Indentation::TYPE_UNKNOWN),
            '<ul></ul>',
        ];

        yield 'Add two spaces' => [
            "<ul>\n\n  <li>yay</li>\n\n</ul>\n",
            new Indentation(2, Indentation::TYPE_SPACE),
            "  <ul>\n\n    <li>yay</li>\n\n  </ul>\n",
        ];

        yield 'Add four spaces' => [
            "<ul>\n\n  <li>yay</li>\n\n</ul>\n",
            new Indentation(4, Indentation::TYPE_SPACE),
            "    <ul>\n\n      <li>yay</li>\n\n    </ul>\n",
        ];

        yield 'Add one tab' => [
            "<ul>\n\n  <li>yay</li>\n\n</ul>\n",
            new Indentation(1, Indentation::TYPE_TAB),
            "\t<ul>\n\n\t  <li>yay</li>\n\n\t</ul>\n",
        ];
    }

    /**
     * @dataProvider provideCasesForTestUnindent
     */
    public function testUnindent(string $input, string $expected): void
    {
        $actual = Indentation::unindent($input);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array<mixed>>
     */
    public function provideCasesForTestUnindent(): iterable
    {
        yield 'Empty string' => [
            '',
            '',
        ];

        yield 'No indentation' => [
            '<ul></ul>',
            '<ul></ul>',
        ];

        yield 'All lines have 1 leading space' => [
            " Hello\n\n World\n",
            "Hello\n\nWorld\n",
        ];

        yield 'All lines have 2 leading spaces' => [
            "  Hello\n\n  World\n",
            "Hello\n\nWorld\n",
        ];

        yield 'All lines have 1 leading tab' => [
            "\tHello\n\n\tWorld\n",
            "Hello\n\nWorld\n",
        ];

        yield 'All lines have 2 leading tabs' => [
            "\t\tHello\n\n\t\tWorld\n",
            "Hello\n\nWorld\n",
        ];

        yield 'Only trim first two spaces' => [
            "  <ul>\n    <li>yay</li>\n  </ul>\n",
            "<ul>\n  <li>yay</li>\n</ul>\n",
        ];

        yield 'Leading indent of 4 spaces + lots of 2-space-indented contents' => [
            <<<INPUT
    <ul>
      <li>
        This content
        is indented
        by 2 spaces.
      </li>
      <li>
        But the entire
        code block
        has an extra
        4 spaces of indentation
        that we should strip.
      </li>
    </ul>
INPUT,
            <<<EXPECTED
<ul>
  <li>
    This content
    is indented
    by 2 spaces.
  </li>
  <li>
    But the entire
    code block
    has an extra
    4 spaces of indentation
    that we should strip.
  </li>
</ul>
EXPECTED,
        ];

        yield 'Leading indent of 5 spaces + lots of 2-space-indented contents' => [
            <<<INPUT
     <ul>
       <li>
         This content
         is indented
         by 2 spaces.
       </li>
       <li>
         But the entire
         code block
         has an extra
         5 spaces of indentation
         that we should strip.
       </li>
     </ul>
INPUT,
            <<<EXPECTED
<ul>
  <li>
    This content
    is indented
    by 2 spaces.
  </li>
  <li>
    But the entire
    code block
    has an extra
    5 spaces of indentation
    that we should strip.
  </li>
</ul>
EXPECTED,
        ];

        yield 'Single leading space with multi-line code comments' => [
            " /**\n  * Test\n  */\n",
            "/**\n * Test\n */\n",
        ];

        yield 'Mixed leading indentation (tabs then spaces)' => [
            "\tHello\n  World!\n",
            "\tHello\n  World!\n",
        ];

        yield 'Mixed leading indentation (spaces then tabs)' => [
            "  Hello\n\tWorld!\n",
            "  Hello\n\tWorld!\n",
        ];
    }

    private function loadFixture(string $filename): string
    {
        $fixture = \file_get_contents(__DIR__ . '/fixtures/' . $filename);
        if ($fixture === false) {
            $this->fail('Fixture file not found');
        }

        return $fixture;
    }
}

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

namespace ColinODell\Indentation;

final class Indentation
{
    public const TYPE_SPACE   = 'space';
    public const TYPE_TAB     = 'tab';
    public const TYPE_UNKNOWN = 'unknown';

    public int $amount;

    /** @var self::TYPE_* */
    public string $type;

    /**
     * @param self::TYPE_* $type
     */
    public function __construct(int $amount, string $type)
    {
        $this->amount = $amount;
        $this->type   = $type;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return self::TYPE_*
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function __toString(): string
    {
        if ($this->amount === 0 || $this->type === self::TYPE_UNKNOWN) {
            return '';
        }

        $indentCharacter = $this->type === self::TYPE_SPACE ? ' ' : "\t";

        return \str_repeat($indentCharacter, $this->amount);
    }

    /**
     * Detect the indentation of the given string.
     */
    public static function detect(string $string): Indentation
    {
        // Identify indents while skipping single space indents to avoid common edge cases (e.g. code comments)
        $indents = self::makeIndentsMap($string, true);
        // If no indents are identified, run again and include all indents for comprehensive detection
        if (\count($indents) === 0) {
            $indents = self::makeIndentsMap($string, false);
        }

        $keyOfMostUsedIndent = self::getMostUsedKey($indents);
        if ($keyOfMostUsedIndent === null) {
            return new self(0, self::TYPE_UNKNOWN);
        }

        [$amount, $type] = self::decodeIndentsKey($keyOfMostUsedIndent);

        return new self($amount, $type);
    }

    public static function change(string $string, Indentation $newStyle): string
    {
        $oldStyle = self::detect($string);

        if ($oldStyle->type === self::TYPE_UNKNOWN || $oldStyle->amount === 0) {
            return $string;
        }

        $lines = \preg_split('/(\R)/', $string, flags: \PREG_SPLIT_DELIM_CAPTURE);
        if ($lines === false) {
            throw new \InvalidArgumentException('Bad input string');
        }

        $newContent = '';
        foreach ($lines as $i => $line) {
            // Newline characters are in the odd-numbered positions
            if ($i % 2 === 1) {
                $newContent .= $line;
                continue;
            }

            if (\preg_match('/^(?:' . \preg_quote($oldStyle->__toString(), '/') . ')+/', $line, $matches) !== 1) {
                $newContent .= $line;
                continue;
            }

            $indentLevel = (int) (\strlen($matches[0]) / $oldStyle->amount);
            $newContent .= \str_repeat($newStyle->__toString(), $indentLevel) . \substr($line, $indentLevel * $oldStyle->amount);
        }

        return $newContent;
    }

    /**
     * @return array<string, array{0: int, 1: int}>
     */
    private static function makeIndentsMap(string $string, bool $ignoreSingleSpaces): array
    {
        $indents = [];

        // Remember the size of previous line's indentation
        $previousSize       = 0;
        $previousIndentType = null;

        // Indents key (ident type + size of the indents/unindents)
        $key = null;

        $lines = \preg_split('/\R/', $string);
        if ($lines === false) {
            throw new \InvalidArgumentException('Invalid string');
        }

        foreach ($lines as $line) {
            if ($line === '') {
                // Ignore empty lines
                continue;
            }

            // Detect either spaces or tabs but not both to properly handle tabs for indentation and spaces for alignment
            if (\preg_match('/^(?:( )+|\t+)/', $line, $matches) !== 1) {
                $previousSize       = 0;
                $previousIndentType = '';
                continue;
            }

            $indent     = \strlen($matches[0]);
            $indentType = isset($matches[1]) ? self::TYPE_SPACE : self::TYPE_TAB;
            // Ignore single space unless it's the only indent detected to prevent common false positives
            if ($ignoreSingleSpaces && $indentType === self::TYPE_SPACE && $indent === 1) {
                continue;
            }

            if ($indentType !== $previousIndentType) {
                $previousSize = 0;
            }

            $previousIndentType = $indentType;
            $weight             = 0;
            $indentDifference   = $indent - $previousSize;
            $previousSize       = $indent;

            // Previous line have same indent?
            if ($indentDifference === 0) {
                $weight++;
                // We use the key from previous loop
                \assert(isset($key) && \is_string($key));
            } else {
                $key = self::encodeIndentsKey($indentType, $indentDifference > 0 ? $indentDifference : -$indentDifference);
            }

            // Update the stats
            if (! isset($indents[$key])) {
                $indents[$key] = [1, 0];
            } else {
                $indents[$key][0]++;
                $indents[$key][1] += $weight;
            }
        }

        return $indents;
    }

    /**
     * Encode the indent type and amount as a string (e.g. 's4') for use as a compound key in the indents map.
     */
    private static function encodeIndentsKey(string $indentType, int $indentAmount): string
    {
        $typeCharacter = $indentType === self::TYPE_SPACE ? 's' : 't';

        return $typeCharacter . $indentAmount;
    }

    /**
     * Extract the indent type and amount from a key of the indents map.
     *
     * @return array{0: int, 1: self::TYPE_*}
     */
    private static function decodeIndentsKey(string $indentsKey): array
    {
        $keyHasTypeSpace = $indentsKey[0] === 's';
        $type            = $keyHasTypeSpace ? self::TYPE_SPACE : self::TYPE_TAB;

        $amount = \intval(\substr($indentsKey, 1));

        return [$amount, $type];
    }

    /**
     * Return the key (e.g. 's4') from the indents map that represents the most common indent,
     * or return undefined if there are no indents.
     *
     * @param array<string, array{int, int}> $indents
     */
    private static function getMostUsedKey(array $indents): string|null
    {
        $result    = null;
        $maxUsed   = 0;
        $maxWeight = 0;

        foreach ($indents as $key => [$usedCount, $weight]) {
            if ($usedCount <= $maxUsed && ($usedCount !== $maxUsed || $weight <= $maxWeight)) {
                continue;
            }

            $maxUsed   = $usedCount;
            $maxWeight = $weight;
            $result    = $key;
        }

        return $result;
    }
}

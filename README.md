# indentation

[![Latest Version](https://img.shields.io/packagist/v/colinodell/indentation.svg?style=flat-square)](https://packagist.org/packages/colinodell/indentation)
[![Total Downloads](https://img.shields.io/packagist/dt/colinodell/indentation.svg?style=flat-square)](https://packagist.org/packages/colinodell/indentation)
[![Software License](https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/workflow/status/colinodell/indentation/Tests/main.svg?style=flat-square)](https://github.com/colinodell/indentation/actions?query=workflow%3ATests+branch%3Amain)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/colinodell/indentation.svg?style=flat-square)](https://scrutinizer-ci.com/g/colinodell/indentation/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/colinodell/indentation.svg?style=flat-square)](https://scrutinizer-ci.com/g/colinodell/indentation)
[![Psalm Type Coverage](https://shepherd.dev/github/colinodell/indentation/coverage.svg)](https://shepherd.dev/github/colinodell/indentation)
[![Sponsor development of this project](https://img.shields.io/badge/sponsor%20this%20package-%E2%9D%A4-ff69b4.svg?style=flat-square)](https://www.colinodell.com/sponsor)

**PHP library to detect and manipulate the indentation of files and strings**

## Installation

```sh
composer require --dev colinodell/indentation
```

## Usage

### Detecting the indentation of a string or file

```php
use ColinODell\Indentation\Indentation;

$indentation = Indentation::detect(file_get_contents('composer.json'));

assert($indentation->getAmount() === 4);
assert($indentation->getType() === Indentation::TYPE_SPACE);
assert((string)$indentation === '    ');
```

### Changing the indentation of a string or file

```php
use ColinODell\Indentation\Indentation;

$composerJson = file_get_contents('composer.json');
$composerJson = Indentation::change($composerJson, new Indentation(1, Indentation::TYPE_TAB));
file_put_contents('composer.json', $composerJson);
```

### Adding leading indentation to all lines

Need to add indent all lines by some amount?

```php
use ColinODell\Indentation\Indentation;

$codeExample = file_get_contents('file.php');
$indented = Indentation::indent($codeExample, new Indentation(4, Indentation::TYPE_SPACE));
```

Now you can embed the [indented code](https://spec.commonmark.org/0.30/#indented-code-blocks) into a Markdown document!
(Hint: This works great with the [league/commonmark](https://commonmark.thephpleague.com/) library.)

### Removing leading indentation from all lines

Imagine you have a file where every line is indented by at least 4 spaces:

```
    /**
     * Just a silly example
     */
    class Cat extends Animal
    {
        // ...
    }
```

You can trim that leading indentation while preserving the nested indentation with the `unindent()` method:

```php
use ColinODell\Indentation\Indentation;

$contents = file_get_contents('Cat.php');
$trimmed = Indentation::unindent($contents);
file_put_contents('Cat.php', $trimmed);
```

Giving you:

```
/**
 * Just a silly example
 */
class Cat extends Animal
{
    // ...
}
```

Note how the leading 4 spaces are removed but all other indentation (like in the docblock and method body) is preserved.

## Detection Algorithm

The current algorithm looks for the most common difference between two consecutive non-empty lines.

In the following example, even if the 4-space indentation is used 3 times whereas the 2-space one is used 2 times, it is detected as less used because there were only 2 differences with this value instead of 4 for the 2-space indentation:

```css
html {
  box-sizing: border-box;
}

body {
  background: gray;
}

p {
    line-height: 1.3em;
    margin-top: 1em;
    text-indent: 2em;
}
```

[Source.](https://medium.com/@heatherarthur/detecting-code-indentation-eff3ed0fb56b#3918)

Furthermore, if there are more than one most used difference, the indentation with the most lines is selected.

In the following example, the indentation is detected as 4-spaces:

```css
body {
  background: gray;
}

p {
    line-height: 1.3em;
    margin-top: 1em;
    text-indent: 2em;
}
```

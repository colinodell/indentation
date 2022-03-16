# indentation

[![Latest Version](https://img.shields.io/packagist/v/colinodell/indentation.svg?style=flat-square)](https://packagist.org/packages/colinodell/indentation)
[![Total Downloads](https://img.shields.io/packagist/dt/colinodell/indentation.svg?style=flat-square)](https://packagist.org/packages/colinodell/indentation)
[![Software License](https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/workflow/status/colinodell/indentation/Tests/main.svg?style=flat-square)](https://github.com/colinodell/indentation/actions?query=workflow%3ATests+branch%3Amain)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/colinodell/indentation.svg?style=flat-square)](https://scrutinizer-ci.com/g/colinodell/indentation/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/colinodell/indentation.svg?style=flat-square)](https://scrutinizer-ci.com/g/colinodell/indentation)
[![Sponsor development of this project](https://img.shields.io/badge/sponsor%20this%20package-%E2%9D%A4-ff69b4.svg?style=flat-square)](https://www.colinodell.com/sponsor)

**PHP library to detect and manipulate the indentation of files and strings**

## Installation

    composer require --dev colinodell/indentation

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
$composerJson = Indentation::change($composerJson, new Indentation(Indentation::TYPE_TAB, 1));
file_put_contents('composer.json', $composerJson);
```

[![Latest Stable Version](https://poser.pugx.org/raigu/ordered-lists-sync/v/stable)](https://packagist.org/packages/raigu/ordered-data-sync)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![build](https://github.com/raigu/ordered-lists-sync/workflows/build/badge.svg)](https://github.com/raigu/ordered-data-sync/actions)
[![codecov](https://codecov.io/gh/raigu/ordered-lists-sync/branch/main/graph/badge.svg?token=43B0X95CZ3)](https://codecov.io/gh/raigu/ordered-data-sync)

Package for ordered data synchronization. Suitable for large datasets.


# Compatibility 

* PHP 7.4, 8.0

# Installations

```bash
$ composer require raigu/ordered-lists-sync
```

# Usage

```php
$synchronization = new \Raigu\OrderedDataSynchronization\Synchronization();
$synchronization(
    new ArrayIterator(['A', 'B', 'D']), // source
    new ArrayIterator(['B', 'C', 'D']), // Target
    function ($element) { echo "ADD: {$element}\n"; },
    function ($element) { echo "REMOVE: {$element}\n"; }
);
```

Will output:

```
ADD: A
REMOVE: C
```

# Algorithm

Algorithm takes two iterators as input: source and target.
Source is the list based on which the target must be kept in sync.
The target is the list that represents currents state and is used to detect what element
has been added and which is removed.

Requirements for iterators:

* iterator must return ordered lists
* source and target must use exactly same ordering (important when source and target are from different databases with different collation)
* elements returned by iterator can be either string or implement [\Stringable](https://www.php.net/Stringable) interface.
  When using `\Stringable` then the `__toString` method must return a value used in comparison functions. This value
  must contain all values that are required to keep in sync.

Algorithm works same way like we use dictionary. If all words are ordered, then we know exactly where it should be
or detect if the word is missing. Simplified example which will not cover all cases. `V` denotes position of the iterator.

```text
        V
Source: A, B, D
        V
Target: B, C, D
```

A < B => if source is before target, then this means that value is missing in target. If there would be an A in target,
then it should be here before B. But it is not. Therefore, it is missing and should be added. Add A and move source cursor:

```text
           V
Source: A, B, D
        V
Target: B, C, D
```

B = B => if the source and target are equal, then they are sync. Move both cursors.

```text
              V
Source: A, B, D
           V
Target: B, C, D
```

D > C => if source is after the target, then this means that value has been removed from source. Therefore, remove C and
move the cursor forward:

```text
              V
Source: A, B, D
              V
Target: B, C, D
```

D = D => they are in sync. Move both cursors. 


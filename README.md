[![Latest Stable Version](http://poser.pugx.org/raigu/ordered-lists-sync/v/stable)](https://packagist.org/packages/raigu/ordered-lists-sync)
[![build](https://github.com/raigu/ordered-lists-sync/workflows/build/badge.svg)](https://github.com/raigu/ordered-data-sync/actions)
[![codecov](https://codecov.io/gh/raigu/ordered-lists-sync/branch/main/graph/badge.svg?token=43B0X95CZ3)](https://codecov.io/gh/raigu/ordered-data-sync)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Dependents](http://poser.pugx.org/raigu/ordered-lists-sync/dependents)](https://packagist.org/packages/raigu/ordered-lists-sync)

Package for ordered data synchronization. Suitable for large datasets in different environments.


# Compatibility

* PHP 7.4, 8.0

# Installations

```bash
$ composer require raigu/ordered-lists-sync
```

# Usage

`\Raigu\OrderedDataSynchronization\Synchronization` compares source and target lists. It detects which elements have
been added or removed in source compared to target and calls corresponding callback.

The source and target must be of type `Iterator`.

```php
$synchronization = new \Raigu\OrderedDataSynchronization\Synchronization();
$synchronization(
    $source = new ArrayIterator(['A', 'B', 'D']), 
    $target = new ArrayIterator(['B', 'C', 'D']),
    $add = function ($element) { echo "ADD: {$element}\n"; },
    $remove = function ($element) { echo "REMOVE: {$element}\n"; }
);
```

Output:

```
ADD: A
REMOVE: C
```

# Use Cases

Package allows to implement stream based approach. This is possible, because this algorithm iterates thorough source and
target only once. Also, algorithm generates minimal addition and removal operations.

Here are some sample use cases with demo code:

* Keeping a relational database table in sync with another (
  demo: [./demo/database_tables.php](./demo/database_tables.php))
* Keeping local data in sync with data available in internet (
  demo: [./demo/internet_to_database.php](./demo/internet_to_database.php))

# Design

The `\Raigu\OrderedDataSynchronization\Synchronization` has only one purpose. Therefore, it
is [designed](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke) so the instance will be a function,
no method is exposed.

Using `Iterator` type for source and target has several advantages.

First, you can create your own iterators as separate components which are easier to develop and test.

Secondly, more complex problems can be solved. Specially if there are memory constraints.  
You can make source or target as [generator](https://www.php.net/manual/en/language.generators.overview.php), as an
instance implementing [Iterate](https://www.php.net/manual/en/class.iterator.php)
or [IteratorAggregate](https://www.php.net/manual/en/class.iteratoraggregate.php) interface or use
[Sandard PHP Library (SPL) iterators](https://www.php.net/manual/en/spl.iterators.php).

Thirdly, it allows creating components which do not do any job in constructor. This is convenient when using dependency
injection or declarative programming.

# Algorithm

Algorithm works same way like we use dictionary. If all words are ordered, then we know exactly where some word should be.
If we have two dictionaries and start to compare them word by word from start, then we can detect added or removed words.

Example (`V` denotes current position of the iterator):

```text
        V
Source: A, B, D
        V
Target: B, C, D
```

A < B => if source is before target, then this means that value is missing in target. If there would be an A in target,
then it should be here before B. But it is not. Therefore, it is missing and should be added. Add A and move source
cursor:

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


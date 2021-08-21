[![Latest Stable Version](http://poser.pugx.org/raigu/ordered-lists-sync/v)](https://packagist.org/packages/raigu/ordered-lists-sync)
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

# Use Cases

Because the algorithm iterates through source and target lists only once, this allows creating stream based approach. 
This is specially important if there are huge data sets, and they can not be hold in memory. 

Algorithm generates minimal addition and removal operations. It is specially suitable in cases where writing is expensive.
Also, this way it has low affect to the implementation that depends on target data because changes are minimal.


Using source and target type as Iterator gives two good property.

First, you can create your own iterator as separate components which are easier to develop and test. 
You can use [generator](https://www.php.net/manual/en/language.generators.overview.php)
, or as class implementing [Iterate](https://www.php.net/manual/en/class.iterator.php)
or [IteratorAggregate](https://www.php.net/manual/en/class.iteratoraggregate.php) interface.
Also [Sandard PHP Library (SPL) iterators](https://www.php.net/manual/en/spl.iterators.php) can be used for example to
make regular array usable with this component.

Secondly, you can create a components which do not do any job in constructor. It is considered best practise in OOP
if the constructors does not do the actual job, just initializes the instance. This property is specially handy 
if you are using some dependency injection or declarative programming.


Here are some sample use cases with demo code:

* Keeping a relational database table in sync with another (
  demo: [./demo/database_tables.php](./demo/database_tables.php))
* Keeping local data in sync with data available in internet (
  demo: [./demo/internet_to_database.php](./demo/internet_to_database.php))


# Algorithm


Algorithm takes two iterators as input: source and target. Source is the list based on which the target must be kept in
sync. The target is the list that represents currents state and is used to detect what element has been added and which
is removed.

Requirements for iterators:

* iterator must return ordered lists
* source and target must use exactly same ordering (important when source and target are from different databases with different collation)
* elements returned by iterator can be either string or implement [\Stringable](https://www.php.net/Stringable) interface.
  When using `\Stringable` then the `__toString` method must return a value used in comparison functions. This value
  must contain all values that are required to keep in sync.
* lists can contain duplicated elements

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


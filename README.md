Package for ordered data synchronization. Suitable for large datasets.

# Usage

```php
$s = new \Raigu\OrderedDataSynchronization\Synchronization();
$s(
    new ArrayIterator(['A', 'B']), // source
    new ArrayIterator(['B', 'C']), // Target
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

If two lists are ordered, then it is possible with one walk through to detect all added or removed elements. It is
basically like dictionary where all words are ordered. For every word you know exactly where it should be. Therefore,
if you place cursor at the beginning of source and target and start to walk through, then you can determine if 
something is added or should be removed. 

Simplified example which will not cover all cases. V denots position of the iterator.

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


<?php

namespace Raigu\OrderedListsSynchronization;

use Iterator;

/**
 * I can detect what elements must be added to a target and what
 * elements must be removed from the target in order to keep
 * the target in sync with the source.
 *
 * Example:
 * <code>
 * $s = new Synchronizer();
 * $s(
 *     new ArrayIterator(['A', 'B']), // source
 *     new ArrayIterator(['B', 'C']), // Target
 *     function ($element) { echo "ADD: {$element}\n"; },
 *     function ($element) { echo "REMOVE: {$element}\n"; }
 * );
 * </code>
 *
 * Will output:
 * ADD: A
 * REMOVE: C
 */
final class Synchronization
{
    public function __invoke(Iterator $source, Iterator $target, callable $add, callable $remove)
    {
        $s = $source->current();
        $t = $target->current();

        while (!is_null($s) or !is_null($t)) {
            if (is_null($t)) {
                // Reached at the end of target. Add all reminding source elements.
                $add($s);
                $source->next();
                $s = $source->current();
            } else if (is_null($s)) {
                // Reached at the end of source. Remove all remaining target elements.
                $remove($t);
                $target->next();
                $t = $target->current();
            } else {
                $sv = strval($s);
                $tv = strval($t);
                if ($sv === $tv) {
                    $source->next();
                    $s = $source->current();
                    $target->next();
                    $t = $target->current();
                } else if ($sv < $tv) {
                    $add($s);
                    $source->next();
                    $s = $source->current();
                } else { // $s > $t
                    $remove($t);
                    $target->next();
                    $t = $target->current();
                }
            }
        }
    }
}
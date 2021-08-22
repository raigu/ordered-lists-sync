<?php
/**
 * This script demonstrates how to keep entities in sync.
 * Entities must have unique id, and the source and target must be ordered by this id.
 *
 * If entity is added into source then add callback is called
 * If entity has been removed from source then remove callback is called
 * If there is a change in entity data, then add and remove callbacks are called.
 *
 * It is not guaranteed that on update the remove callback is called before the add callback.
 * The calling order depends on stringified source and target element comparison result.
 *
 * If it is important that on update the remove callback is called before the add callback
 * (for example the id field index has unique constraints) then you have several options.
 *
 * One way is to gather all removals and additions into memory and process them later.
 * This way you can detect if you need to insert, delete or update.
 *
 * Second one is to make add and remove callbacks more clever.
 *
 * Third one is to add last update date after the id in `__toString` method. But if you
 * have already timestamp then you probably can use some better algorithm which does
 * not require querying whole dataset ;)
 *
 * NB! Pay attention to the id in  `__toString` method. The id is normalized so
 * the stringified entities are ordered the same way as if they were
 * ordered just by id.
 */

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../tests/Stringable.php'; // polyfill for PHP 7

/**
 * An instance representing entity.
 * This is what source and target iterator must return
 * and add and remove callback will have as input.
 */
class Entity implements Stringable
{
    public int $id;
    public string $name;
    public string $email;

    /**
     * @return string returns entity representation usable in strcmp function to compare with other entities.
     */
    public function __toString(): string
    {
        // NB! NB! NB! The id must be in equal length! This way it is avoided
        // that the lists order and entity order are not matching.
        // For example, let's have two entities: id=1,name=B and id=10,name=A.
        // After stringifing them we get "1B" and "10A".
        // The next expression is valid  and "10A" < "1B".
        // But in iterator the 1 is before 10 and therefore the B is before the A.
        $normalizedId = str_pad(strval($this->id), 5, "0", STR_PAD_RIGHT);
        return $normalizedId
            . $this->name
            . $this->email;
    }

    public function __construct(int $id, string $name, string $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
    }
}

$synchronization = new \Raigu\OrderedListsSynchronization\Synchronization();
$synchronization(
    $source = new ArrayIterator([
        new Entity(1, 'John Doe', 'john@doe.com'),
        new Entity(2, 'Jane Doe', 'jane@test.org'),
    ]),
    $source = new ArrayIterator([
        new Entity(1, 'John Doe', 'john@test.com'),
        new Entity(2, 'Jane Doe', 'jane@test.com'),
    ]),
    $add = function (Entity $element) {
        echo "ADD: {$element->id} {$element->name} {$element->email}" . PHP_EOL;
    },
    $remove = function (Entity $element) {
        echo "REMOVE: {$element->id} {$element->name} {$element->email}" . PHP_EOL;
    }
);

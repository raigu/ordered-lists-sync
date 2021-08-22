<?php

namespace Raigu\OrderedListsSynchronization;

/**
 * @covers \Raigu\OrderedListsSynchronization\Synchronization
 */
final class SynchronizationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function elements_can_be_Stringable()
    {
        $sut = new Synchronization();

        $added = [];
        $addSpy = function ($value) use (&$added) {
            $added[] = $value;
        };

        $removed = [];
        $removeSpy = function ($value) use (&$removed) {
            $removed[] = $value;
        };

        $sut(
            new \ArrayIterator([
                new Str('A'),
                new Str('B')
            ]),
            new \ArrayIterator([
                new Str('B'),
                new Str('C')
            ]),
            $addSpy,
            $removeSpy
        );

        $this->assertEquals([new Str('A')], $added);
        $this->assertEquals([new Str('C')], $removed);
    }

    /**
     * @test
     */
    public function handles_duplicates()
    {
        $sut = new Synchronization();

        $added = [];
        $addSpy = function ($value) use (&$added) {
            $added[] = $value;
        };

        $removed = [];
        $removeSpy = function ($value) use (&$removed) {
            $removed[] = $value;
        };

        $sut(
            new \ArrayIterator([
                new Str('A'),
                new Str('A'),
            ]),
            new \ArrayIterator([
                new Str('A'),
            ]),
            $addSpy,
            $removeSpy
        );

        $this->assertEquals([new Str('A')], $added, 'One A was more in source. Should be added to target.');
    }

    /**
     * @testdox #1 Must accept EmptyIterator as source or target
     * @test
     */
    public function must_accept_EmptyIterator()
    {
        $sut = new Synchronization();
        $dummy = function ($value) {};

        $sut(
            new \EmptyIterator,
            new \EmptyIterator,
            $dummy,
            $dummy
        );

        $this->assertTrue(true, 'The script must pass if Empty iterator is used as source and target');
    }

    /**
     * @test
     * @dataProvider samples
     */
    public function regression($source, $target, $expectedAdded, $expectedRemoved)
    {
        $sut = new Synchronization();

        $added = [];
        $addSpy = function ($value) use (&$added) {
            $added[] = $value;
        };

        $removed = [];
        $removeSpy = function ($value) use (&$removed) {
            $removed[] = $value;
        };

        $sut(
            new \ArrayIterator($source),
            new \ArrayIterator($target),
            $addSpy,
            $removeSpy
        );

        $this->assertEquals($expectedAdded, $added);
        $this->assertEquals($expectedRemoved, $removed);
    }

    public function samples(): array
    {
        return [
            // adding
            [[], [], [], []],
            [['A', 'B'], ['A'], ['B'], []],
            [['A', 'B'], ['B'], ['A'], []],
            [['A', 'B', 'C'], ['A', 'C'], ['B'], []],

            // removing
            [[], ['A'], [], ['A']],
            [['A'], ['A', 'B'], [], ['B']],
            [['B'], ['A', 'B'], [], ['A']],
            [['A', 'C'], ['A', 'B', 'C'], [], ['B']],

            // complex
            [['A'], ['B'], ['A'], ['B']],
            [['A', 'B', 'D', 'E', 'G'], ['A', 'C', 'D', 'F'], ['B', 'E', 'G'], ['C', 'F']],
        ];
    }
}
<?php
namespace Skewd\Collection;

use LogicException;
use PHPUnit_Framework_TestCase;

class AttributeCollectionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->subject = AttributeCollection::create();
    }

    public function testGet()
    {
        $collection = $this->subject->set('foo', 'bar');

        $this->assertSame(
            'bar',
            $collection->get('foo')
        );
    }

    public function testGetWithUnknownProperty()
    {
        $this->setExpectedException(
            LogicException::class,
            '.'
        );

        $this->subject->get('foo');
    }

    public function testTryGet()
    {
        $collection = $this->subject->set('foo', 'bar');

        $value = null;

        $this->assertTrue(
            $collection->tryGet('foo', $value)
        );

        $this->assertSame(
            'bar',
            $value
        );
    }

    public function testTryGetWithUnknownProperty()
    {
        $this->assertFalse(
            $this->subject->tryGet('foo', $value)
        );
    }

    public function testSafeGet()
    {
        $collection = $this->subject->set('foo', 'bar');

        $this->assertSame(
            'bar',
            $collection->safeGet('foo')
        );
    }

    public function testSafeGetWithUnknownProperty()
    {
        $this->assertSame(
            'bar',
            $this->subject->safeGet('foo', 'bar')
        );
    }

    public function testSafeGetDefaultsToNull()
    {
        $this->assertNull(
            $this->subject->safeGet('foo')
        );
    }

    public function testHas()
    {
        $this->assertFalse(
            $this->subject->has('foo')
        );

        $this->assertTrue(
            $this->subject->set('foo', 'bar')->has('foo')
        );
    }

    public function testIsEmpty()
    {
        $this->assertTrue(
            $this->subject->isEmpty()
        );

        $this->assertFalse(
            $this->subject->set('foo', 'bar')->isEmpty()
        );
    }

    public function testSet()
    {
        $collection = $this->subject->set('foo', 'bar');

        $this->assertSame(
            ['foo' => 'bar'],
            iterator_to_array($collection)
        );
    }

    public function testSetRetainsOtherValues()
    {
        $collection = $this->subject
            ->set('foo', 'bar')
            ->set('baz', 'qux');

        $this->assertSame(
            ['foo' => 'bar', 'baz' => 'qux'],
            iterator_to_array($collection)
        );
    }

    public function testSetReplacesExistingValue()
    {
        $collection = $this->subject
            ->set('foo', 'bar')
            ->set('foo', 'baz');

        $this->assertSame(
            ['foo' => 'baz'],
            iterator_to_array($collection)
        );
    }

    public function testSetWithNoChange()
    {
        $collectionA = $this->subject->set('foo', 'bar');
        $collectionB = $collectionA->set('foo', 'bar');

        $this->assertSame(
            $collectionA,
            $collectionB
        );
    }

    public function testSetWithInvalidName()
    {
        $this->setExpectedException(
            LogicException::class,
            'Attribute name must be a string.'
        );

        $this->subject->set(100, 'bar');
    }

    public function testSetWithInvalidValue()
    {
        $this->setExpectedException(
            LogicException::class,
            'Attribute value must be a string.'
        );

        $this->subject->set('foo', 100);
    }

    public function testSetMany()
    {
        $attributes = ['foo' => 'bar'];
        $collection = $this->subject->setMany($attributes);

        $this->assertSame(
            $attributes,
            iterator_to_array($collection)
        );
    }

    public function testSetManyRetainsOtherValues()
    {
        $collection = $this->subject
            ->setMany(['foo' => 'bar'])
            ->setMany(['baz' => 'qux']);

        $this->assertSame(
            [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
            iterator_to_array($collection)
        );
    }

    public function testSetManyReplacesExistingValues()
    {
        $collection = $this->subject
            ->setMany(['foo' => 'bar', 'baz' => 'qux'])
            ->setMany(['baz' => 'thud', 'grunt' => 'gorp']);

        $this->assertSame(
            [
                'foo' => 'bar',
                'baz' => 'thud',
                'grunt' => 'gorp',
            ],
            iterator_to_array($collection)
        );
    }

    public function testSetManyWithNoChange()
    {
        $collectionA = $this->subject->setMany(['foo' => 'bar']);
        $collectionB = $collectionA->setMany(['foo' => 'bar']);

        $this->assertSame(
            $collectionA,
            $collectionB
        );
    }

    public function testSetManyWithInvalidName()
    {
        $this->setExpectedException(
            LogicException::class,
            'Attribute name must be a string.'
        );

        $this->subject->setMany([100 => 'bar']);
    }

    public function testSetManyWithInvalidValue()
    {
        $this->setExpectedException(
            LogicException::class,
            'Attribute value must be a string.'
        );

        $this->subject->setMany(['foo' => 100]);
    }

    public function testReplaceAll()
    {
        $collection = $this->subject
            ->set('foo', 'bar')
            ->replaceAll(['baz' => 'qux']);

        $this->assertSame(
            ['baz' => 'qux'],
            iterator_to_array($collection)
        );
    }

    public function testReplaceAllWithNoChange()
    {
        $collectionA = $this->subject->set('foo', 'bar');
        $collectionB = $collectionA->replaceAll(['foo' => 'bar']);

        $this->assertSame(
            $collectionA,
            $collectionB
        );
    }

    public function testReplaceAllWithInvalidValue()
    {
        $this->setExpectedException(
            LogicException::class,
            'Attribute value must be a string.'
        );

        $this->subject->replaceAll(['foo' => 100]);
    }

    public function testRemove()
    {
        $collection = $this->subject
            ->setMany(['foo' => 'bar', 'baz' => 'qux'])
            ->remove('foo');

        $this->assertSame(
            ['baz' => 'qux'],
            iterator_to_array($collection)
        );
    }

    public function testRemoveWithMultipleNames()
    {
        $collection = $this->subject
            ->setMany(['foo' => 'bar', 'baz' => 'qux'])
            ->remove('foo', 'baz');

        $this->assertTrue(
            $collection->isEmpty()
        );
    }

    public function testRemoveWithNoChange()
    {
        $collectionA = $this->subject->set('foo', 'bar');
        $collectionB = $collectionA->remove('baz');

        $this->assertSame(
            $collectionA,
            $collectionB
        );
    }

    public function testRemoveWhenEmpty()
    {
        $collection = $this->subject->remove('baz');

        $this->assertSame(
            $this->subject,
            $collection
        );
    }

    public function testClear()
    {
        $collection = $this->subject
            ->setMany(['foo' => 'bar', 'baz' => 'qux'])
            ->clear();

        $this->assertTrue(
            $collection->isEmpty()
        );
    }

    public function testClearWithNoChange()
    {
        $collection = $this->subject->clear();

        $this->assertSame(
            $this->subject,
            $collection
        );
    }
}

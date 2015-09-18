<?php
namespace Skewd\Common\Session;

use LogicException;
use PHPUnit_Framework_TestCase;

class SessionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->attributes = [
            'a' => 1,
            'b' => 2,
        ];

        $this->subject = Session::create(
            '<id>',
            '<owner>',
            $this->attributes
        );
    }

    public function testId()
    {
        $this->assertSame(
            '<id>',
            $this->subject->id()
        );
    }

    public function testOwner()
    {
        $this->assertSame(
            '<owner>',
            $this->subject->owner()
        );
    }

    public function testVersionIsInitiallyOne()
    {
        $this->assertSame(
            1,
            $this->subject->version()
        );
    }

    public function testAttributes()
    {
        $this->assertSame(
            $this->attributes,
            $this->subject->attributes()
        );
    }

    public function testPropertiesIsInitiallyEmpty()
    {
        $this->assertSame(
            [],
            $this->subject->properties()
        );
    }

    public function testGet()
    {
        $session = $this->subject->set('foo', 'bar');

        $this->assertSame(
            'bar',
            $session->get('foo')
        );
    }

    public function testGetWithUnknownProperty()
    {
        $this->setExpectedException(
            LogicException::class,
            'Session <id> version 1 does not contain a property named "foo".'
        );

        $this->subject->get('foo');
    }

    public function testTryGet()
    {
        $session = $this->subject->set('foo', 'bar');

        $value = null;

        $this->assertTrue(
            $session->tryGet('foo', $value)
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
        $session = $this->subject->set('foo', 'bar');

        $this->assertSame(
            'bar',
            $session->safeGet('foo')
        );
    }

    public function testSafeGetWithUnknownProperty()
    {
        $this->assertSame(
            'bar',
            $this->subject->safeGet('foo', 'bar')
        );
    }

    public function testSafeGetDefaultsToEmptyString()
    {
        $this->assertSame(
            '',
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

    public function testSet()
    {
        $session = $this->subject->set('foo', 'bar');

        $this->assertImmutableSessionValues($session, 2);

        $this->assertSame(
            ['foo' => 'bar'],
            $session->properties()
        );
    }

    public function testSetRetainsOtherValues()
    {
        $session = $this->subject
            ->set('foo', 'bar')
            ->set('baz', 'qux');

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSame(
            ['foo' => 'bar', 'baz' => 'qux'],
            $session->properties()
        );
    }

    public function testSetReplacesExistingValue()
    {
        $session = $this->subject
            ->set('foo', 'bar')
            ->set('foo', 'baz');

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSame(
            ['foo' => 'baz'],
            $session->properties()
        );
    }

    public function testSetWithNoChange()
    {
        $sessionA = $this->subject->set('foo', 'bar');
        $sessionB = $sessionA->set('foo', 'bar');

        $this->assertImmutableSessionValues($sessionB, 2);

        $this->assertSame(
            $sessionA,
            $sessionB
        );
    }

    public function testSetWithInvalidValue()
    {
        $this->setExpectedException(
            LogicException::class,
            'Parameter values must be strings.'
        );

        $this->subject->set('foo', 100);
    }

    public function testSetMany()
    {
        $properties = ['foo' => 'bar'];
        $session = $this->subject->setMany($properties);

        $this->assertImmutableSessionValues($session, 2);

        $this->assertSame(
            $properties,
            $session->properties()
        );
    }

    public function testSetManyRetainsOtherValues()
    {
        $session = $this->subject
            ->setMany(['foo' => 'bar'])
            ->setMany(['baz' => 'qux']);

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSame(
            [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
            $session->properties()
        );
    }

    public function testSetManyReplacesExistingValues()
    {
        $session = $this->subject
            ->setMany(['foo' => 'bar', 'baz' => 'qux'])
            ->setMany(['baz' => 'thud', 'grunt' => 'gorp']);

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSame(
            [
                'foo' => 'bar',
                'baz' => 'thud',
                'grunt' => 'gorp',
            ],
            $session->properties()
        );
    }

    public function testSetManyWithNoChange()
    {
        $sessionA = $this->subject->setMany(['foo' => 'bar']);
        $sessionB = $sessionA->setMany(['foo' => 'bar']);

        $this->assertImmutableSessionValues($sessionB, 2);

        $this->assertSame(
            $sessionA,
            $sessionB
        );
    }

    public function testSetManyWithInvalidValue()
    {
        $this->setExpectedException(
            LogicException::class,
            'Parameter values must be strings.'
        );

        $this->subject->setMany(['foo' => 100]);
    }

    public function testReplaceAll()
    {
        $session = $this->subject
            ->set('foo', 'bar')
            ->replaceAll(['baz' => 'qux']);

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSame(
            ['baz' => 'qux'],
            $session->properties()
        );
    }

    public function testReplaceAllWithNoChange()
    {
        $sessionA = $this->subject->set('foo', 'bar');
        $sessionB = $sessionA->replaceAll(['foo' => 'bar']);

        $this->assertImmutableSessionValues($sessionB, 2);

        $this->assertSame(
            $sessionA,
            $sessionB
        );
    }

    public function testReplaceAllWithInvalidValue()
    {
        $this->setExpectedException(
            LogicException::class,
            'Parameter values must be strings.'
        );

        $this->subject->replaceAll(['foo' => 100]);
    }

    public function testRemove()
    {
        $session = $this->subject
            ->setMany(['foo' => 'bar', 'baz' => 'qux'])
            ->remove('foo');

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSame(
            ['baz' => 'qux'],
            $session->properties()
        );
    }

    public function testRemoveWithMultipleNames()
    {
        $session = $this->subject
            ->setMany(['foo' => 'bar', 'baz' => 'qux'])
            ->remove('foo', 'baz');

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSame(
            [],
            $session->properties()
        );
    }

    public function testRemoveWithNoChange()
    {
        $sessionA = $this->subject->set('foo', 'bar');
        $sessionB = $sessionA->remove('baz');

        $this->assertImmutableSessionValues($sessionB, 2);

        $this->assertSame(
            $sessionA,
            $sessionB
        );
    }

    public function testRemoveWithNoChangeAndEmptyProperties()
    {
        $session = $this->subject->remove('baz');

        $this->assertImmutableSessionValues($session, 1);

        $this->assertSame(
            $this->subject,
            $session
        );
    }

    public function testClear()
    {
        $session = $this->subject
            ->setMany(['foo' => 'bar', 'baz' => 'qux'])
            ->clear();

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSame(
            [],
            $session->properties()
        );
    }

    public function testClearWithNoChange()
    {
        $session = $this->subject->clear();

        $this->assertImmutableSessionValues($session, 1);

        $this->assertSame(
            $this->subject,
            $session
        );
    }

    private function assertImmutableSessionValues(Session $session, $version)
    {
        $this->assertSame(
            '<id>',
            $session->id()
        );

        $this->assertSame(
            $version,
            $session->version()
        );

        $this->assertSame(
            $this->attributes,
            $session->attributes()
        );
    }
}

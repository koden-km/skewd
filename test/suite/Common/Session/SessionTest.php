<?php
namespace Skewd\Common\Session;

use LogicException;
use PHPUnit_Framework_TestCase;
use Skewd\Common\Collection\AttributeCollection;

class SessionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->constants = AttributeCollection::create([
            'a' => '1',
            'b' => '2',
        ]);

        $this->subject = Session::create(
            '<id>',
            '<owner>',
            $this->constants
        );
    }

    public function testCreateAtVersion()
    {
        $constants = AttributeCollection::create(['foo' => 'bar']);
        $variables = AttributeCollection::create(['baz' => 'qux']);

        $session = Session::createAtVersion(
            '<id>',
            '<owner>',
            123,
            $constants,
            $variables
        );

        $this->assertSame(
            '<id>',
            $session->id()
        );

        $this->assertSame(
            '<owner>',
            $session->owner()
        );

        $this->assertSame(
            123,
            $session->version()
        );

        $this->assertSame(
            $constants,
            $session->constants()
        );

        $this->assertSame(
            $variables,
            $session->variables()
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

    public function testConstants()
    {
        $this->assertSame(
            $this->constants,
            $this->subject->constants()
        );
    }

    public function testVariablesIsInitiallyEmpty()
    {
        $this->assertTrue(
            $this->subject->variables()->isEmpty()
        );
    }

    public function testSet()
    {
        $session = $this->subject->set('foo', 'bar');

        $this->assertImmutableSessionValues($session, 2);

        $this->assertSessionVariables(
            ['foo' => 'bar'],
            $session
        );
    }

    public function testSetRetainsOtherValues()
    {
        $session = $this->subject
            ->set('foo', 'bar')
            ->set('baz', 'qux');

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSessionVariables(
            ['foo' => 'bar', 'baz' => 'qux'],
            $session
        );
    }

    public function testSetReplacesExistingValue()
    {
        $session = $this->subject
            ->set('foo', 'bar')
            ->set('foo', 'baz');

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSessionVariables(
            ['foo' => 'baz'],
            $session
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
            'Attribute value must be a string.'
        );

        $this->subject->set('foo', 100);
    }

    public function testSetMany()
    {
        $variables = ['foo' => 'bar'];
        $session = $this->subject->setMany($variables);

        $this->assertImmutableSessionValues($session, 2);

        $this->assertSessionVariables(
            $variables,
            $session
        );
    }

    public function testSetManyRetainsOtherValues()
    {
        $session = $this->subject
            ->setMany(['foo' => 'bar'])
            ->setMany(['baz' => 'qux']);

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSessionVariables(
            [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
            $session
        );
    }

    public function testSetManyReplacesExistingValues()
    {
        $session = $this->subject
            ->setMany(['foo' => 'bar', 'baz' => 'qux'])
            ->setMany(['baz' => 'thud', 'grunt' => 'gorp']);

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSessionVariables(
            [
                'foo' => 'bar',
                'baz' => 'thud',
                'grunt' => 'gorp',
            ],
            $session
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
            'Attribute value must be a string.'
        );

        $this->subject->setMany(['foo' => 100]);
    }

    public function testReplaceAll()
    {
        $session = $this->subject
            ->set('foo', 'bar')
            ->replaceAll(['baz' => 'qux']);

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSessionVariables(
            ['baz' => 'qux'],
            $session
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
            'Attribute value must be a string.'
        );

        $this->subject->replaceAll(['foo' => 100]);
    }

    public function testRemove()
    {
        $session = $this->subject
            ->setMany(['foo' => 'bar', 'baz' => 'qux'])
            ->remove('foo');

        $this->assertImmutableSessionValues($session, 3);

        $this->assertSessionVariables(
            ['baz' => 'qux'],
            $session
        );
    }

    public function testRemoveWithMultipleNames()
    {
        $session = $this->subject
            ->setMany(['foo' => 'bar', 'baz' => 'qux'])
            ->remove('foo', 'baz');

        $this->assertImmutableSessionValues($session, 3);

        $this->assertTrue(
            $session->variables()->isEmpty()
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

    public function testRemoveWithNoChangeAndEmptyVariables()
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

        $this->assertTrue(
            $session->variables()->isEmpty()
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
            $this->constants,
            $session->constants()
        );
    }

    private function assertSessionVariables(array $variables, Session $session)
    {
        $this->assertSame(
            $variables,
            iterator_to_array($session->variables())
        );
    }
}

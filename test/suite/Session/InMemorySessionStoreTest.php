<?php
namespace Skewd\Session;

use PHPUnit_Framework_TestCase;

class InMemorySessionStoreTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->subject = new InMemorySessionStore();
        $this->sessionA = Session::create('<a>', '<owner>');
        $this->sessionB = Session::create('<b>', '<owner>');
    }

    public function testStore()
    {
        $this->assertNull(
            $this->subject->get('<a>')
        );

        $this->subject->store($this->sessionA);

        $this->assertSame(
            $this->sessionA,
            $this->subject->get('<a>')
        );
    }

    public function testUpdate()
    {
        $this->assertNull(
            $this->subject->get('<a>')
        );

        $latest = null;

        $this->assertTrue(
            $this->subject->update($this->sessionA, $latest)
        );

        $this->assertSame(
            $this->sessionA,
            $this->subject->get('<a>')
        );

        $this->assertSame(
            $this->sessionA,
            $latest
        );
    }

    public function testUpdateReplacesOlderSession()
    {
        $this->subject->store($this->sessionA);

        $newerSession = $this->sessionA->set('foo', 'bar');

        $latest = null;

        $this->assertTrue(
            $this->subject->update($newerSession, $latest)
        );

        $this->assertSame(
            $newerSession,
            $this->subject->get('<a>')
        );

        $this->assertSame(
            $newerSession,
            $latest
        );
    }

    public function testUpdateDoesNotReplaceNewerSession()
    {
        $newerSession = $this->sessionA->set('foo', 'bar');

        $this->subject->store($newerSession);

        $latest = null;

        $this->assertFalse(
            $this->subject->update($this->sessionA, $latest)
        );

        $this->assertSame(
            $newerSession,
            $this->subject->get('<a>')
        );

        $this->assertSame(
            $newerSession,
            $latest
        );
    }

    public function testUpdateWithoutLatestParameter()
    {
        $this->assertNull(
            $this->subject->get('<a>')
        );

        $this->assertTrue(
            $this->subject->update($this->sessionA)
        );

        $this->assertSame(
            $this->sessionA,
            $this->subject->get('<a>')
        );
    }

    public function testRemove()
    {
        $this->subject->store($this->sessionA);
        $this->subject->store($this->sessionB);
        $this->subject->remove('<a>');

        $this->assertNull(
            $this->subject->get('<a>')
        );

        $this->assertSame(
            $this->sessionB,
            $this->subject->get('<b>')
        );
    }

    public function testClear()
    {
        $this->subject->store($this->sessionA);
        $this->subject->store($this->sessionB);
        $this->subject->clear();

        $this->assertNull(
            $this->subject->get('<a>')
        );

        $this->assertNull(
            $this->subject->get('<b>')
        );
    }
}

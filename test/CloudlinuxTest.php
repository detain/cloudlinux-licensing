<?php

use PHPUnit\Framework\TestCase;
use Detain\Cloudlinux\Cloudlinux;

/**
 * @covers Cloudlinux
 */
final class CloudlinuxTest extends TestCase {

    public function testCanBeCreatedFromValidEmailAddress() {
        $this->assertInstanceOf(Cloudlinux::class, Cloudlinux::fromString('user@example.com'));
    }

    public function testCannotBeCreatedFromInvalidEmailAddress() {
        $this->expectException(InvalidArgumentException::class);
        Cloudlinux::fromString('invalid');
    }

    public function testCanBeUsedAsString() {
        $this->assertEquals('user@example.com', Cloudlinux::fromString('user@example.com'));
    }
}


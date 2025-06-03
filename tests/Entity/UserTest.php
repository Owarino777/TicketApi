<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserSettersAndGetters()
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setName('Malik');
        $user->setPassword('securepassword');
        $user->setRoles(['ROLE_USER']);

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('Malik', $user->getName());
        $this->assertSame('securepassword', $user->getPassword());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }
}

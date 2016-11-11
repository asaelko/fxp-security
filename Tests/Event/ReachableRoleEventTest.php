<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Tests\Event;

use Sonatra\Component\Security\Event\ReachableRoleEvent;
use Symfony\Component\Security\Core\Role\Role;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ReachableRoleEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $roles = array(
            new Role('ROLE_FOO'),
            new Role('ROLE_BAR'),
        );

        $event = new ReachableRoleEvent($roles);
        $this->assertSame($roles, $event->getReachableRoles());

        $roles[] = new Role('ROLE_BAZ');
        $event->setReachableRoles($roles);
        $this->assertSame($roles, $event->getReachableRoles());
    }
}

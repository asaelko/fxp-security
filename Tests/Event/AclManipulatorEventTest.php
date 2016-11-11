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

use Sonatra\Component\Security\Acl\Model\PermissionContextInterface;
use Sonatra\Component\Security\Event\AclManipulatorEvent;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class AclManipulatorEventTest extends \PHPUnit_Framework_TestCase
{
    public function testModel()
    {
        /* @var PermissionContextInterface $ctx */
        $ctx = $this->getMockBuilder(PermissionContextInterface::class)->getMock();

        $event = new AclManipulatorEvent($ctx);

        $this->assertSame($ctx, $event->getPermissionContext());
    }
}

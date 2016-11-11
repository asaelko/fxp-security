<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Event;

use Sonatra\Component\Security\Event\Traits\ReachableRoleEventTrait;

/**
 * The pre reachable role event.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class PreReachableRoleEvent extends AbstractEditableSecurityEvent
{
    use ReachableRoleEventTrait;

    /**
     * Constructor.
     *
     * @param \Symfony\Component\Security\Core\Role\RoleInterface[] $reachableRoles The reachable roles
     */
    public function __construct(array $reachableRoles)
    {
        $this->reachableRoles = $reachableRoles;
    }
}

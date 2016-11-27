<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Expression;

use Sonatra\Component\Security\Event\GetExpressionVariablesEvent;
use Sonatra\Component\Security\ExpressionVariableEvents;
use Sonatra\Component\Security\Identity\IdentityUtils;
use Sonatra\Component\Security\Identity\SecurityIdentityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Variable storage of expression.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ExpressionVariableStorage implements ExpressionVariableStorageInterface, EventSubscriberInterface
{
    /**
     * @var SecurityIdentityManagerInterface|null
     */
    private $sim;

    /**
     * @var array<string, mixed>
     */
    private $variables = array();

    /**
     * Constructor.
     *
     * @param array<string, mixed>                  $variables The expression variables
     * @param SecurityIdentityManagerInterface|null $sim       The security identity manager
     */
    public function __construct(array $variables = array(),
                                SecurityIdentityManagerInterface $sim = null)
    {
        $this->sim = $sim;

        foreach ($variables as $name => $value) {
            $this->add($name, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ExpressionVariableEvents::GET => array('inject', 0),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function add($name, $value)
    {
        $this->variables[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($name)
    {
        unset($this->variables[$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return isset($this->variables[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        return $this->has($name)
            ? $this->variables[$name]
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll()
    {
        return $this->variables;
    }

    /**
     * {@inheritdoc}
     */
    public function inject(GetExpressionVariablesEvent $event)
    {
        $token = $event->getToken();

        $event->addVariables(array_merge($this->variables, array(
            'token' => $token,
            'user' => $token->getUser(),
            'roles' => $this->getAllRoles($token),
        )));
    }

    /**
     * Get all roles.
     *
     * @param TokenInterface $token The token
     *
     * @return string[]
     */
    private function getAllRoles(TokenInterface $token)
    {
        if (null !== $this->sim) {
            $sids = $this->sim->getSecurityIdentities($token);

            return IdentityUtils::filterRolesIdentities($sids);
        }

        return array_map(function (RoleInterface $role) {
            return $role->getRole();
        }, $token->getRoles());
    }
}
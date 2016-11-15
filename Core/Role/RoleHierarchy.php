<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Core\Role;

use Doctrine\Common\Persistence\ManagerRegistry as ManagerRegistryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Sonatra\Component\Security\Event\PostReachableRoleEvent;
use Sonatra\Component\Security\Event\PreReachableRoleEvent;
use Sonatra\Component\Security\Model\RoleHierarchicalInterface;
use Sonatra\Component\Security\ReachableRoleEvents;
use Symfony\Component\Security\Core\Role\RoleHierarchy as BaseRoleHierarchy;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sonatra\Component\Security\Exception\SecurityException;

/**
 * RoleHierarchy defines a role hierarchy.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class RoleHierarchy extends BaseRoleHierarchy
{
    /**
     * @var ManagerRegistryInterface
     */
    private $registry;

    /**
     * @var string
     */
    private $roleClassname;

    /**
     * @var array
     */
    private $cacheExec;

    /**
     * @var CacheItemPoolInterface|null
     */
    private $cache;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Constructor.
     *
     * @param array                       $hierarchy     An array defining the hierarchy
     * @param ManagerRegistryInterface    $registry
     * @param string                      $roleClassname
     * @param CacheItemPoolInterface|null $cache
     */
    public function __construct(array $hierarchy, ManagerRegistryInterface $registry, $roleClassname,
                                CacheItemPoolInterface $cache = null)
    {
        parent::__construct($hierarchy);

        $this->registry = $registry;
        $this->roleClassname = $roleClassname;
        $this->cacheExec = array();
        $this->cache = $cache;
    }

    /**
     * Set event dispatcher.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * Returns an array of all roles reachable by the given ones, but only defined in global configuration.
     *
     * @param RoleInterface[] $roles An array of RoleInterface instances
     *
     * @return RoleInterface[] An array of RoleInterface instances
     */
    public function getConfigReachableRoles(array $roles)
    {
        return parent::getReachableRoles($roles);
    }

    /**
     * Returns an array of all roles reachable by the given ones.
     *
     * @param RoleInterface[] $roles An array of RoleInterface instances
     *
     * @return RoleInterface[] An array of RoleInterface instances
     *
     * @throws SecurityException When the role class is not an instance of '\Symfony\Component\Security\Core\Role\RoleInterface'
     */
    public function getReachableRoles(array $roles)
    {
        if (0 === count($roles)) {
            return $roles;
        }

        $roleNames = array();
        $nRoles = array();
        $item = null;

        foreach ($roles as $role) {
            if (!is_string($role) && !($role instanceof RoleInterface)) {
                $roleClass = 'Symfony\Component\Security\Core\Role\RoleInterface';

                throw new SecurityException(sprintf('The Role class must be an instance of "%s"', $roleClass));
            }

            $roleNames[] = ($role instanceof RoleInterface) ? $role->getRole() : $role;
            $nRoles[] = ($role instanceof RoleInterface) ? $role : new Role((string) $role);
        }

        $roles = $nRoles;
        $id = $this->getUniqueId($roleNames);

        // find the hierarchy in execution cache
        if (isset($this->cacheExec[$id])) {
            return $this->cacheExec[$id];
        }

        // find the hierarchy in cache
        if (null !== $this->cache) {
            $item = $this->cache->getItem($id);
            $reachableRoles = $item->get();

            if ($item->isHit() && null !== $reachableRoles) {
                return $reachableRoles;
            }
        }

        // build hierarchy
        $reachableRoles = parent::getReachableRoles($roles);
        $em = $this->registry->getManagerForClass($this->roleClassname);
        $repo = $em->getRepository($this->roleClassname);
        $entityRoles = array();
        $filters = array();
        $isPermEnabled = true;

        if (null !== $this->eventDispatcher) {
            $event = new PreReachableRoleEvent($reachableRoles);
            $this->eventDispatcher->dispatch(ReachableRoleEvents::PRE, $event);
            $reachableRoles = $event->getReachableRoles();
            $isPermEnabled = $event->isPermissionEnabled();
        }

        if (interface_exists('Doctrine\ORM\EntityManagerInterface') && $em instanceof EntityManagerInterface) {
            $filters = array_keys($em->getFilters()->getEnabledFilters());

            foreach ($filters as $name) {
                $em->getFilters()->disable($name);
            }
        }

        if (count($roleNames) > 0) {
            $entityRoles = $repo->findBy(array('name' => $roleNames));
        }

        /* @var RoleHierarchicalInterface $eRole */
        foreach ($entityRoles as $eRole) {
            $reachableRoles = array_merge($reachableRoles, $this->getReachableRoles($eRole->getChildren()->toArray()));
        }

        if (count($filters) > 0) {
            foreach ($filters as $name) {
                $em->getFilters()->enable($name);
            }
        }

        // cleaning double
        $existingRoles = array();
        $finalRoles = array();

        foreach ($reachableRoles as $role) {
            if (!in_array($role->getRole(), $existingRoles)) {
                if (!($role instanceof Role)) {
                    $role = new Role($role->getRole());
                }

                $existingRoles[] = $role->getRole();
                $finalRoles[] = $role;
            }
        }

        // insert in cache
        if (null !== $this->cache && $item instanceof CacheItemInterface) {
            $item->set($finalRoles);
            $this->cache->save($item);
        }

        $this->cacheExec[$id] = $finalRoles;

        if (null !== $this->eventDispatcher) {
            $event = new PostReachableRoleEvent($finalRoles, $isPermEnabled);
            $this->eventDispatcher->dispatch(ReachableRoleEvents::POST, $event);
            $finalRoles = $event->getReachableRoles();
        }

        return $finalRoles;
    }

    /**
     * Get the unique id.
     *
     * @param array $roleNames The role names
     *
     * @return string
     */
    protected function getUniqueId(array $roleNames)
    {
        return sha1(implode('|', $roleNames));
    }
}

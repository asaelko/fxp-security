<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Model;

use Doctrine\Common\Collections\Collection;

/**
 * Interface for role hierarchisable.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface RoleHierarchisableInterface extends RoleInterface
{
    /**
     * Add a parent on the current role.
     *
     * @param RoleHierarchisableInterface $role
     *
     * @return self
     */
    public function addParent(RoleHierarchisableInterface $role);

    /**
     * Remove a parent on the current role.
     *
     * @param RoleHierarchisableInterface $parent
     *
     * @return self
     */
    public function removeParent(RoleHierarchisableInterface $parent);

    /**
     * Gets all parent.
     *
     * @return Collection|RoleHierarchisableInterface[]
     */
    public function getParents();

    /**
     * Gets all parent names.
     *
     * @return array
     */
    public function getParentNames();

    /**
     * Check if role has parent.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasParent($name);

    /**
     * Add a child on the current role.
     *
     * @param RoleHierarchisableInterface $role
     *
     * @return self
     */
    public function addChild(RoleHierarchisableInterface $role);

    /**
     * Remove a child on the current role.
     *
     * @param RoleHierarchisableInterface $child
     *
     * @return self
     */
    public function removeChild(RoleHierarchisableInterface $child);

    /**
     * Gets all children.
     *
     * @return Collection|RoleHierarchisableInterface[]
     */
    public function getChildren();

    /**
     * Gets all children names.
     *
     * @return array
     */
    public function getChildrenNames();

    /**
     * Check if role has child.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasChild($name);
}

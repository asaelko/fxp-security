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
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * User interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface UserInterface extends AdvancedUserInterface
{
    /**
     * Get the organizations of user.
     *
     * @return Collection|OrganizationUserInterface[]
     */
    public function getUserOrganizations();

    /**
     * Get the organization names of user.
     *
     * @return string[]
     */
    public function getUserOrganizationNames();

    /**
     * Check the presence of username in organization.
     *
     * @param string $name The name of organization
     *
     * @return bool
     */
    public function hasUserOrganization($name);

    /**
     * Add a organization user in user.
     *
     * @param OrganizationUserInterface $organizationUser The organization user
     *
     * @return self
     */
    public function addUserOrganization(OrganizationUserInterface $organizationUser);

    /**
     * Remove a organization user in user.
     *
     * @param OrganizationUserInterface $organizationUser The organization user
     *
     * @return self
     */
    public function removeUserOrganization(OrganizationUserInterface $organizationUser);
}

<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Acl\Domain;

use Sonatra\Component\Security\Acl\Util\ClassUtils;
use Sonatra\Component\Security\Core\Organizational\OrganizationalContextInterface;
use Sonatra\Component\Security\Model\Traits\GroupableInterface;
use Sonatra\Component\Security\Model\OrganizationInterface;
use Sonatra\Component\Security\Model\OrganizationUserInterface;
use Sonatra\Component\Security\Model\Traits\RoleableInterface;
use Sonatra\Component\Security\Model\Traits\UserOrganizationUsersInterface;
use Sonatra\Component\Security\Model\UserInterface;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * A SecurityIdentity implementation used for actual organization.
 *
 * For used the standard ACL Provider, the organization security identity is a
 * UserSecurityIdentity with the organization class name.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
final class OrganizationSecurityIdentity
{
    /**
     * Creates a organization security identity from a OrganizationInterface.
     *
     * @param OrganizationInterface $organization The organization
     *
     * @return UserSecurityIdentity
     */
    public static function fromAccount(OrganizationInterface $organization)
    {
        return new UserSecurityIdentity($organization->getName(), ClassUtils::getRealClass($organization));
    }

    /**
     * Creates a organization security identity from a TokenInterface.
     *
     * @param TokenInterface                      $token         The token
     * @param OrganizationalContextInterface|null $context       The organizational context
     * @param RoleHierarchyInterface|null         $roleHierarchy The role hierarchy
     *
     * @return UserSecurityIdentity[]
     */
    public static function fromToken(TokenInterface $token, OrganizationalContextInterface $context = null,
                                     RoleHierarchyInterface $roleHierarchy = null)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return array();
        }

        return null !== $context
            ? static::getSecurityIdentityForCurrentOrganization($context, $roleHierarchy)
            : static::getSecurityIdentityForAllOrganizations($user, $roleHierarchy);
    }

    /**
     * Get the security identities for all organizations of user.
     *
     * @param UserInterface               $user          The user
     * @param RoleHierarchyInterface|null $roleHierarchy The role hierarchy
     *
     * @return UserSecurityIdentity[]
     */
    protected static function getSecurityIdentityForAllOrganizations(UserInterface $user, $roleHierarchy = null)
    {
        $sids = array();

        if ($user instanceof UserOrganizationUsersInterface) {
            foreach ($user->getUserOrganizations() as $userOrg) {
                $sids[] = self::fromAccount($userOrg->getOrganization());
                $sids = array_merge($sids, static::getOrganizationGroups($userOrg));
                $roles = static::getOrganizationRoles($userOrg, $roleHierarchy);

                foreach ($roles as $role) {
                    $sids[] = new RoleSecurityIdentity($role->getRole());
                }
            }
        }

        return $sids;
    }

    /**
     * Get the security identities for the current organization of user.
     *
     * @param OrganizationalContextInterface $context       The organizational context
     * @param RoleHierarchyInterface|null    $roleHierarchy The role hierarchy
     *
     * @return UserSecurityIdentity[]
     */
    protected static function getSecurityIdentityForCurrentOrganization(OrganizationalContextInterface $context,
                                                                        $roleHierarchy = null)
    {
        $sids = array();

        $org = $context->getCurrentOrganization();
        if ($org) {
            $sids[] = self::fromAccount($org);
        }

        $userOrg = $context->getCurrentOrganizationUser();
        if (null !== $userOrg) {
            $sids = array_merge($sids, static::getOrganizationGroups($userOrg));
            $roles = static::getOrganizationRoles($userOrg, $roleHierarchy);
            foreach ($roles as $role) {
                $sids[] = new RoleSecurityIdentity($role->getRole());
            }
        }

        return $sids;
    }

    /**
     * Get the organization roles of user.
     *
     * @param OrganizationUserInterface   $user
     * @param RoleHierarchyInterface|null $roleHierarchy
     *
     * @return RoleInterface[]
     */
    protected static function getOrganizationRoles(OrganizationUserInterface $user, $roleHierarchy = null)
    {
        $roles = array();

        if ($user instanceof RoleableInterface && $user instanceof OrganizationUserInterface) {
            $roles = $user->getRoles();
            $org = $user->getOrganization();
            $id = strtoupper($org->getName());
            $existingRoles = $roles;

            foreach ($roles as $i => $role) {
                $roles[$i] = new Role($roles[$i].'__'.$id);
            }

            if ($org instanceof RoleableInterface) {
                foreach ($org->getRoles() as $orgRole) {
                    if (!in_array((string) $orgRole, $existingRoles)) {
                        $roles[] = new Role((string) $orgRole);
                    }
                }
            }

            if ($roleHierarchy instanceof RoleHierarchyInterface) {
                $roles = $roleHierarchy->getReachableRoles($roles);
            }
        }

        return $roles;
    }

    /**
     * Get the security identities for organization groups of user.
     *
     * @param OrganizationUserInterface $user
     *
     * @return array
     */
    protected static function getOrganizationGroups(OrganizationUserInterface $user)
    {
        $sids = array();

        if ($user instanceof GroupableInterface) {
            foreach ($user->getGroups() as $group) {
                try {
                    $sids[] = GroupSecurityIdentity::fromAccount($group);
                } catch (\InvalidArgumentException $invalid) {
                    // ignore, group has no group security identity
                }
            }
        }

        return $sids;
    }
}

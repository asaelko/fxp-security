<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Identity;

use Sonatra\Component\Security\Organizational\OrganizationalUtil;

/**
 * Identity utils.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
abstract class IdentityUtils
{
    /**
     * Merge the security identities.
     *
     * @param SecurityIdentityInterface[] $sids    The security identities
     * @param SecurityIdentityInterface[] $newSids The new security identities
     *
     * @return SecurityIdentityInterface[]
     */
    public static function merge(array $sids, array $newSids)
    {
        $existingSids = array();

        foreach ($sids as $sid) {
            $existingSids[] = $sid->getType().'::'.$sid->getIdentifier();
        }

        foreach ($newSids as $sid) {
            $key = $sid->getType().'::'.$sid->getIdentifier();

            if (!in_array($key, $existingSids)) {
                $sids[] = $sid;
                $existingSids[] = $key;
            }
        }

        return $sids;
    }

    /**
     * Filter the role identities and convert to strings.
     *
     * @param SecurityIdentityInterface[] $sids The security identities
     *
     * @return string[]
     */
    public static function filterRolesIdentities(array $sids)
    {
        $roles = array();

        foreach ($sids as $sid) {
            if ($sid instanceof RoleSecurityIdentity && false === strpos($sid->getIdentifier(), 'IS_')) {
                $roles[] = OrganizationalUtil::format($sid->getIdentifier());
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * Check if the security identity is valid.
     *
     * @param SecurityIdentityInterface $sid The security identity
     *
     * @return bool
     */
    public static function isValid(SecurityIdentityInterface $sid)
    {
        return !$sid instanceof RoleSecurityIdentity
            || ($sid instanceof RoleSecurityIdentity && false === strpos($sid->getIdentifier(), 'IS_'));
    }
}

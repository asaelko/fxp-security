<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Organizational;

use Sonatra\Component\Security\Exception\RuntimeException;
use Sonatra\Component\Security\Model\OrganizationInterface;
use Sonatra\Component\Security\Model\OrganizationUserInterface;
use Sonatra\Component\Security\Model\UserInterface;
use Sonatra\Component\Security\OrganizationalTypes;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Organizational Context.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class OrganizationalContext implements OrganizationalContextInterface
{
    /**
     * @var string
     */
    protected $optionalFilterType = OrganizationalTypes::OPTIONAL_FILTER_WITH_ORG;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var OrganizationInterface|null
     */
    protected $organization;

    /**
     * @var OrganizationUserInterface|null
     */
    protected $organizationUser;

    /**
     * @param TokenStorageInterface $tokenStorage The token storage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentOrganization($organization)
    {
        $this->getToken('organization');

        if (null === $organization || false === $organization || $organization instanceof OrganizationInterface) {
            $this->organization = $organization;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentOrganization()
    {
        if (null === $this->organization) {
            $token = $this->tokenStorage->getToken();
            $user = null !== $token ? $token->getUser() : null;

            if ($user instanceof UserInterface && method_exists($user, 'getOrganization')) {
                $org = $user->getOrganization();

                if ($org instanceof OrganizationInterface) {
                    return $org;
                }
            }
        }

        return false !== $this->organization ? $this->organization : null;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentOrganizationUser($organizationUser)
    {
        $token = $this->getToken('organization user');
        $user = $token->getUser();
        $this->organizationUser = null;
        $org = null;

        if ($user instanceof UserInterface && $organizationUser instanceof OrganizationUserInterface
                && $user->getUsername() === $organizationUser->getUser()->getUsername()) {
            $this->organizationUser = $organizationUser;
            $org = $organizationUser->getOrganization();
        }
        $this->setCurrentOrganization($org);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentOrganizationUser()
    {
        return $this->organizationUser;
    }

    /**
     * {@inheritdoc}
     */
    public function isOrganization()
    {
        return null !== $this->getCurrentOrganization()
            && !$this->getCurrentOrganization()->isUserOrganization()
            && null !== $this->getCurrentOrganizationUser();
    }

    /**
     * {@inheritdoc}
     */
    public function setOptionalFilterType($type)
    {
        $this->optionalFilterType = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionalFilterType()
    {
        return $this->optionalFilterType;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptionalFilterType($type)
    {
        return is_string($this->optionalFilterType) && $type === $this->optionalFilterType;
    }

    /**
     * Get the token.
     *
     * @param string $type The type name
     *
     * @return TokenInterface
     *
     * @throws
     */
    protected function getToken($type)
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new RuntimeException(sprintf('The current %s cannot be added in security token because the security token is empty', $type));
        }

        return $token;
    }
}

<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Doctrine\ORM\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sonatra\Component\Security\Acl\Domain\OrmFilterRuleContextDefinition;
use Sonatra\Component\Security\Acl\Domain\AbstractRuleOrmFilterDefinition;
use Sonatra\Component\Security\Acl\Model\RuleOrmFilterDefinitionInterface;
use Sonatra\Component\Security\Doctrine\ORM\Listener\AclListener;
use Sonatra\Component\Security\Exception\RuntimeException;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;

/**
 * Acl filter.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class AclFilter extends SQLFilter
{
    protected $listener;
    protected $em;

    /**
     * {@inheritdoc}
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $arm = $this->getListener()->getAclRuleManager();
        $class = $targetEntity->getName();
        $rule = $arm->getRule(BasicPermissionMap::PERMISSION_VIEW, $class);

        if ($arm->hasFilterDefinition($rule, AbstractRuleOrmFilterDefinition::TYPE)) {
            /* @var RuleOrmFilterDefinitionInterface $definition */
            $definition = $arm->getFilterDefinition($rule, AbstractRuleOrmFilterDefinition::TYPE);
            $identities = $this->getListener()->getSecurityIdentities();
            $rcd = new OrmFilterRuleContextDefinition($identities, $targetEntity, $targetTableAlias);

            return $definition->addFilterConstraint($rcd);
        }

        return '';
    }

    /**
     * Get the ACL Doctrine ORM Listener.
     *
     * @return AclListener
     *
     * @throws RuntimeException
     */
    protected function getListener()
    {
        if (null === $this->listener) {
            $em = $this->getEntityManager();
            $evm = $em->getEventManager();

            foreach ($evm->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof AclListener) {
                        $this->listener = $listener;
                        break 2;
                    }
                }
            }

            if (null === $this->listener) {
                throw new RuntimeException('Listener "AclListener" was not added to the EventManager!');
            }
        }

        return $this->listener;
    }

    /**
     * Get the entity manager in parent class.
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (null === $this->em) {
            $refl = new \ReflectionProperty('Doctrine\ORM\Query\Filter\SQLFilter', 'em');
            $refl->setAccessible(true);
            $this->em = $refl->getValue($this);
        }

        return $this->em;
    }
}

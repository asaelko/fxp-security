<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Acl\Rule\Definition;

use Sonatra\Component\Security\Acl\Domain\AbstractRuleDefinition;
use Sonatra\Component\Security\Acl\Domain\RuleContextDefinition;
use Sonatra\Component\Security\Acl\Model\AclRuleManagerInterface;
use Sonatra\Component\Security\Acl\Model\RuleContextDefinitionInterface;
use Sonatra\Component\Security\Acl\Util\AclUtils;
use Sonatra\Component\Security\Exception\InvalidArgumentException;

/**
 * The Parent ACL Rule Definition.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ParentDefinition extends AbstractRuleDefinition
{
    /**
     * @var AclRuleManagerInterface
     */
    protected $arm;

    /**
     * Constructor.
     *
     * @param AclRuleManagerInterface $arm The ACL rule manager
     */
    public function __construct(AclRuleManagerInterface $arm)
    {
        $this->arm = $arm;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'parent';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes()
    {
        return array(static::TYPE_CLASS, static::TYPE_OBJECT);
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(RuleContextDefinitionInterface $rcd)
    {
        if (null === $rcd->getField()) {
            throw new InvalidArgumentException('The rule definition "parent" must be only associated with class field');
        }

        $rcd = new RuleContextDefinition(
            $rcd->getSecurityIdentities(),
            $rcd->getObjectIdentity(),
            $rcd->getMasks(),
            null,
            $rcd->getObject()
        );
        $type = AclUtils::convertToAclName($rcd->getMasks()[0]);
        $defName = $this->arm->getRule($type[0], $rcd->getObjectIdentity()->getType());
        $def = $this->arm->getDefinition($defName);

        return $def->isGranted($rcd);
    }
}

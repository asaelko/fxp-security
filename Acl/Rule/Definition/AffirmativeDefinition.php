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
use Sonatra\Component\Security\Acl\Model\AclRuleManagerInterface;
use Sonatra\Component\Security\Acl\Model\RuleContextDefinitionInterface;

/**
 * The Affirmative ACL Rule Definition.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class AffirmativeDefinition extends AbstractRuleDefinition
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
        return 'affirmative';
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
        $oDef = $this->arm->getDefinition('object');
        $cDef = $this->arm->getDefinition('class');

        return $oDef->isGranted($rcd)
                || $cDef->isGranted($rcd);
    }
}

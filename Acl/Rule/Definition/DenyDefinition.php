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
use Sonatra\Component\Security\Acl\Model\RuleContextDefinitionInterface;

/**
 * The Deny ACL Rule Definition.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DenyDefinition extends AbstractRuleDefinition
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'deny';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(RuleContextDefinitionInterface $rcd)
    {
        return false;
    }
}

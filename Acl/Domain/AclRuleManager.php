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

use Sonatra\Component\Security\Acl\Util\AclUtils;
use Sonatra\Component\Security\Acl\Util\ClassUtils;
use Sonatra\Component\Security\Exception\SecurityException;
use Sonatra\Component\Security\Acl\Model\AclRuleManagerInterface;
use Sonatra\Component\Security\Acl\DependencyInjection\RuleExtensionInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * ACL Rule Manager.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class AclRuleManager implements AclRuleManagerInterface
{
    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * @var RuleExtensionInterface
     */
    protected $ruleExtension;

    /**
     * @var string
     */
    protected $defaultRule;

    /**
     * @var string
     */
    protected $disabledRule;

    /**
     * @var array
     */
    protected $rules;

    /**
     * @var bool
     */
    protected $isDisabled;

    /**
     * @var array
     */
    private $cache;

    /**
     * Constructor.
     *
     * @param PropertyAccessorInterface $propertyAccessor
     * @param RuleExtensionInterface    $ruleExtension
     * @param string                    $defaultRule
     * @param string                    $disabledRule
     * @param array                     $rules
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor,
            RuleExtensionInterface $ruleExtension,
            $defaultRule,
            $disabledRule,
            array $rules = array())
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->ruleExtension = $ruleExtension;
        $this->defaultRule = $defaultRule;
        $this->disabledRule = $disabledRule;
        $this->rules = $rules;
        $this->isDisabled = false;
        $this->cache = array();
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultRule($rule)
    {
        $this->defaultRule = $rule;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultRule()
    {
        return $this->defaultRule;
    }

    /**
     * {@inheritdoc}
     */
    public function setDisabledRule($rule)
    {
        $this->disabledRule = $rule;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisabledRule()
    {
        return $this->disabledRule;
    }

    /**
     * {@inheritdoc}
     */
    public function isDisabled()
    {
        return $this->isDisabled;
    }

    /**
     * {@inheritdoc}
     */
    public function enable()
    {
        $this->isDisabled = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function disable()
    {
        $this->isDisabled = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRule($rule, $type, $classname, $fieldname = null)
    {
        $classname = ClassUtils::getRealClass($classname);
        $rule = $this->validateRuleName($rule);
        $type = $this->validateTypeName($type);
        $cacheName = strtolower("$type::$classname:$fieldname");

        $this->cache[$cacheName] = $rule;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRule($type, $classname, $fieldname = null)
    {
        if ($this->isDisabled()) {
            return $this->disabledRule;
        }

        $classname = ClassUtils::getRealClass($classname);
        $cacheName = strtolower("$type::$classname:$fieldname");

        if (isset($this->cache[$cacheName])) {
            return $this->cache[$cacheName];
        }

        $type = $this->validateTypeName($type);
        $rule = null;

        if (null !== $fieldname
                && isset($this->rules[$classname]['fields'][$fieldname]['rules'][$type])) {
            $rule = $this->rules[$classname]['fields'][$fieldname]['rules'][$type];
            $rule = $rule !== '' ? $rule : null;
        }

        if (null === $rule
                && null !== $fieldname
                && isset($this->rules[$classname]['fields'][$fieldname]['rules'])) {
            $rule = $this->getParentRule($type, $this->rules[$classname]['fields'][$fieldname]['rules']);
            $rule = $rule !== '' ? $rule : null;
        }

        if (null === $rule
                && null !== $fieldname
                && isset($this->rules[$classname]['fields'][$fieldname]['default'])) {
            $rule = $this->rules[$classname]['fields'][$fieldname]['default'];
            $rule = $rule !== '' ? $rule : null;
        }

        if (null === $rule
                && null !== $fieldname
                && isset($this->rules[$classname]['default_fields'])) {
            $rule = $this->rules[$classname]['default_fields'];
            $rule = $rule !== '' ? $rule : null;
        }

        if (null === $rule
                && isset($this->rules[$classname]['rules'][$type])) {
            $rule = $this->rules[$classname]['rules'][$type];
            $rule = $rule !== '' ? $rule : null;
        }

        if (null === $rule
                && isset($this->rules[$classname]['rules'])) {
            $rule = $this->getParentRule($type, $this->rules[$classname]['rules']);
            $rule = $rule !== '' ? $rule : null;
        }

        if (null === $rule
                && isset($this->rules[$classname]['default'])) {
            $rule = $this->rules[$classname]['default'];
            $rule = $rule !== '' ? $rule : null;
        }

        if (null === $rule) {
            $rule = $this->defaultRule;
        }

        //save in cache and return value of cache
        $this->setRule($rule, $type, $classname, $fieldname);

        return $this->getRule($type, $classname, $fieldname);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaster($domainObject)
    {
        if (is_object($domainObject)) {
            $classname = ClassUtils::getRealClass($domainObject);

            if (isset($this->rules[$classname]['master'])) {
                $master = $this->rules[$classname]['master'];
                $value = $this->propertyAccessor->getValue($domainObject, $master);
                $domainObject = is_object($value) ? $value : $domainObject;
            }
        }

        return $domainObject;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition($name)
    {
        return $this->ruleExtension->getDefinition($name);
    }

    /**
     * {@inheritdoc}
     */
    public function hasDefinition($name)
    {
        return $this->ruleExtension->hasDefinition($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterDefinition($name, $type)
    {
        return $this->ruleExtension->getFilterDefinition($name, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function hasFilterDefinition($name, $type)
    {
        return $this->ruleExtension->hasFilterDefinition($name, $type);
    }

    /**
     * Validate the rule name with existing constant.
     *
     * @param string $rule
     *
     * @return string
     *
     * @throws SecurityException When the rule in configuration of Sonatra ACL Rules does not exist
     */
    protected function validateRuleName($rule)
    {
        if (!$this->hasDefinition($rule)) {
            throw new SecurityException(sprintf('The rule "%s" in configuration of Sonatra ACL Rules does not exist', $rule));
        }

        return $rule;
    }

    /**
     * Validate the type name with existing constant.
     *
     * @param string $type
     *
     * @return string
     *
     * @throws SecurityException When the type in configuration of Sonatra ACL Rules does not exist
     */
    protected function validateTypeName($type)
    {
        $type = strtoupper($type);

        if (!defined(AclUtils::getMaskBuilderClass().'::MASK_'.$type)) {
            throw new SecurityException(sprintf('The type "%s" in configuration of Sonatra ACL Rules does not exist', $type));
        }

        return $type;
    }

    /**
     * Get the parent decision rule.
     *
     * @param string $type
     * @param array  $rules
     *
     * @return string|null
     */
    protected function getParentRule($type, array $rules)
    {
        $pRules = AclUtils::getParentRules($type);
        $rule = null;

        foreach ($pRules as $pRule) {
            if (isset($rules[$pRule])) {
                $rule = $rules[$pRule];
                break;
            }
        }

        return $rule;
    }
}

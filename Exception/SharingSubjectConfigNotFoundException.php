<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Exception;

/**
 * SharingSubjectConfigNotFoundException for the Security component.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class SharingSubjectConfigNotFoundException extends InvalidArgumentException implements ExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string $class The class name
     */
    public function __construct($class)
    {
        parent::__construct(sprintf('The sharing subject configuration for the class "%s" is not found', $class));
    }
}
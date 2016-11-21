<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Tests\Model\Traits;

use Sonatra\Component\Security\Model\OrganizationInterface;
use Sonatra\Component\Security\Model\Traits\OrganizationalOptionalTrait;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class OrganizationalOptionalTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testModel()
    {
        /* @var OrganizationInterface $org */
        $org = $this->getMockBuilder(OrganizationInterface::class)->getMock();

        /* @var OrganizationalOptionalTrait $model */
        $model = $this->getMockForTrait(OrganizationalOptionalTrait::class);
        $model->setOrganization($org);

        $this->assertSame($org, $model->getOrganization());

        $model->setOrganization(null);
        $this->assertNull($model->getOrganization());
    }
}
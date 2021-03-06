<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Tests\Authorization\Voter;

use Fxp\Component\Security\Authorization\Voter\OrganizationVoter;
use Fxp\Component\Security\Identity\OrganizationSecurityIdentity;
use Fxp\Component\Security\Identity\SecurityIdentityManagerInterface;
use Fxp\Component\Security\Tests\Fixtures\Model\MockOrganization;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class OrganizationVoterTest extends TestCase
{
    /**
     * @var SecurityIdentityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sidManager;

    /**
     * @var OrganizationVoter
     */
    protected $voter;

    protected function setUp()
    {
        $this->sidManager = $this->getMockBuilder(SecurityIdentityManagerInterface::class)->getMock();
        $this->voter = new OrganizationVoter($this->sidManager, null);
    }

    public function getAccessResults()
    {
        return [
            [['ORG_FOO'], VoterInterface::ACCESS_GRANTED],
            [['ORG_BAR'], VoterInterface::ACCESS_DENIED],
            [['TEST_FOO'], VoterInterface::ACCESS_ABSTAIN],
        ];
    }

    /**
     * @dataProvider getAccessResults
     *
     * @param string[] $attributes The voter attributes
     * @param int      $access     The access status of voter
     */
    public function testExtractRolesWithAccessGranted(array $attributes, $access)
    {
        /* @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();

        $sids = [
            new OrganizationSecurityIdentity(MockOrganization::class, 'FOO'),
        ];

        if (VoterInterface::ACCESS_ABSTAIN !== $access) {
            $this->sidManager->expects($this->atLeast(2))
                ->method('getSecurityIdentities')
                ->willReturn($sids);
        }

        $this->assertSame($access, $this->voter->vote($token, null, $attributes));
        $this->assertSame($access, $this->voter->vote($token, null, $attributes));
    }
}

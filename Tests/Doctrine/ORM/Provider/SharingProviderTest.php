<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Tests\Doctrine\ORM\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sonatra\Component\Security\Doctrine\ORM\Provider\SharingProvider;
use Sonatra\Component\Security\Identity\RoleSecurityIdentity;
use Sonatra\Component\Security\Identity\SecurityIdentityManagerInterface;
use Sonatra\Component\Security\Identity\SubjectIdentity;
use Sonatra\Component\Security\Identity\UserSecurityIdentity;
use Sonatra\Component\Security\Sharing\SharingIdentityConfig;
use Sonatra\Component\Security\Sharing\SharingManagerInterface;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockObject;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockOrgOptionalRole;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockOrgRequiredRole;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockRole;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockSharing;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockUserRoleable;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class SharingProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $roleRepo;

    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sharingRepo;

    /**
     * @var SecurityIdentityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sidManager;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;

    /**
     * @var SharingManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sharingManager;

    /**
     * @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $qb;

    /**
     * @var Query|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $query;

    protected function setUp()
    {
        $this->roleRepo = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $this->sharingRepo = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $this->sidManager = $this->getMockBuilder(SecurityIdentityManagerInterface::class)->getMock();
        $this->tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $this->sharingManager = $this->getMockBuilder(SharingManagerInterface::class)->getMock();
        $this->qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();

        $this->query = $this->getMockForAbstractClass(
            AbstractQuery::class,
            array(),
            '',
            false,
            false,
            true,
            array(
                'getResult',
                'execute',
            )
        );
    }

    public function testGetPermissionRoles()
    {
        $roles = array(
            'ROLE_USER',
        );
        $result = array(
            new MockRole('ROLE_USER'),
        );

        $this->roleRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(0))
            ->method('addSelect')
            ->with('p')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(1))
            ->method('leftJoin')
            ->with('r.permissions', 'p')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(2))
            ->method('where')
            ->with('UPPER(r.name) IN (:roles)')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(3))
            ->method('setParameter')
            ->with('roles', $roles)
            ->willReturn($this->qb);

        $this->qb->expects($this->at(4))
            ->method('orderBy')
            ->with('p.class', 'asc')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(5))
            ->method('addOrderBy')
            ->with('p.field', 'asc')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(6))
            ->method('addOrderBy')
            ->with('p.operation', 'asc')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(7))
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        $provider = $this->createProvider();
        $this->assertSame($result, $provider->getPermissionRoles($roles));
    }

    public function getOrganizationalRoleTypes()
    {
        return array(
            array(MockOrgRequiredRole::class),
            array(MockOrgOptionalRole::class),
        );
    }

    /**
     * @dataProvider getOrganizationalRoleTypes
     *
     * @param string $roleClass The classname of role
     */
    public function testGetPermissionRolesWithOrganizationalRole($roleClass)
    {
        $roles = array(
            'ROLE_USER',
            'ROLE_USER__FOO',
            'ROLE_ADMIN__BAZ',
        );
        $result = array(
            new MockRole('ROLE_USER'),
            new MockRole('ROLE_USER'),
            new MockRole('ROLE_ADMIN'),
        );

        $this->roleRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(0))
            ->method('addSelect')
            ->with('p')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(1))
            ->method('leftJoin')
            ->with('r.permissions', 'p')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(2))
            ->method('where')
            ->with('(UPPER(r.name) in (:roles) AND r.organization = NULL) OR (UPPER(r.name) IN (:foo_roles) AND LOWER(o.name) = :foo_name) OR (UPPER(r.name) IN (:baz_roles) AND LOWER(o.name) = :baz_name)')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(3))
            ->method('setParameter')
            ->with('roles', array('ROLE_USER'))
            ->willReturn($this->qb);

        $this->qb->expects($this->at(4))
            ->method('setParameter')
            ->with('foo_roles', array('ROLE_USER'))
            ->willReturn($this->qb);

        $this->qb->expects($this->at(5))
            ->method('setParameter')
            ->with('foo_name', 'foo')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(6))
            ->method('setParameter')
            ->with('baz_roles', array('ROLE_ADMIN'))
            ->willReturn($this->qb);

        $this->qb->expects($this->at(7))
            ->method('setParameter')
            ->with('baz_name', 'baz')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(8))
            ->method('orderBy')
            ->with('p.class', 'asc')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(9))
            ->method('addOrderBy')
            ->with('p.field', 'asc')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(10))
            ->method('addOrderBy')
            ->with('p.operation', 'asc')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(11))
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        $provider = $this->createProvider($roleClass);
        $this->assertSame($result, $provider->getPermissionRoles($roles));
    }

    public function testGetPermissionRolesWithEmptyRoles()
    {
        $this->roleRepo->expects($this->never())
            ->method('createQueryBuilder');

        $provider = $this->createProvider();
        $this->assertSame(array(), $provider->getPermissionRoles(array()));
    }

    public function testGetSharingEntries()
    {
        $subjects = array(
            SubjectIdentity::fromObject(new MockObject('foo', 42)),
            SubjectIdentity::fromObject(new MockObject('bar', 23)),
        );
        $result = array();

        $this->sharingRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(0))
            ->method('addSelect')
            ->with('p')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(1))
            ->method('leftJoin')
            ->with('s.permissions', 'p')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(2))
            ->method('where')
            ->with('(s.subjectClass = :subject0_class AND s.subjectId = :subject0_id) OR (s.subjectClass = :subject1_class AND s.subjectId = :subject1_id)')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(3))
            ->method('setParameter')
            ->with('subject0_class', MockObject::class)
            ->willReturn($this->qb);

        $this->qb->expects($this->at(4))
            ->method('setParameter')
            ->with('subject0_id', 42)
            ->willReturn($this->qb);

        $this->qb->expects($this->at(5))
            ->method('setParameter')
            ->with('subject1_class', MockObject::class)
            ->willReturn($this->qb);

        $this->qb->expects($this->at(6))
            ->method('setParameter')
            ->with('subject1_id', 23)
            ->willReturn($this->qb);

        $this->qb->expects($this->at(7))
            ->method('andWhere')
            ->with('s.enabled = TRUE AND (s.startedAt IS NULL OR s.startedAt <= :now) AND (s.endedAt IS NULL OR s.endedAt >= :now)')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(8))
            ->method('setParameter')
            ->with('now')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(9))
            ->method('orderBy')
            ->with('p.class', 'asc')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(10))
            ->method('addOrderBy')
            ->with('p.field', 'asc')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(11))
            ->method('addOrderBy')
            ->with('p.operation', 'asc')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(12))
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        $provider = $this->createProvider();
        $this->assertSame($result, $provider->getSharingEntries($subjects));
    }

    public function testGetSharingEntriesWithEmptySubjects()
    {
        $this->sharingRepo->expects($this->never())
            ->method('createQueryBuilder');

        $provider = $this->createProvider();
        $this->assertSame(array(), $provider->getSharingEntries(array()));
    }

    /**
     * @group bug
     */
    public function testGetPermissionRolesWithSecurityIdentities()
    {
        $sids = array(
            new RoleSecurityIdentity('ROLE_USER'),
            new UserSecurityIdentity('user.test'),
        );
        $subjects = array(
            SubjectIdentity::fromObject(new MockObject('foo', 42)),
            SubjectIdentity::fromObject(new MockObject('bar', 23)),
        );
        $result = array();

        $this->sharingManager->expects($this->at(0))
            ->method('getIdentityConfig')
            ->with(RoleSecurityIdentity::TYPE)
            ->willReturn(new SharingIdentityConfig(MockRole::class, RoleSecurityIdentity::TYPE));

        $this->sharingManager->expects($this->at(1))
            ->method('getIdentityConfig')
            ->with(UserSecurityIdentity::TYPE)
            ->willReturn(new SharingIdentityConfig(MockUserRoleable::class, UserSecurityIdentity::TYPE));

        $this->sharingRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(0))
            ->method('addSelect')
            ->with('p')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(1))
            ->method('leftJoin')
            ->with('s.permissions', 'p')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(2))
            ->method('where')
            ->with('(s.subjectClass = :subject0_class AND s.subjectId = :subject0_id) OR (s.subjectClass = :subject1_class AND s.subjectId = :subject1_id)')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(3))
            ->method('setParameter')
            ->with('subject0_class', MockObject::class)
            ->willReturn($this->qb);

        $this->qb->expects($this->at(4))
            ->method('setParameter')
            ->with('subject0_id', 42)
            ->willReturn($this->qb);

        $this->qb->expects($this->at(5))
            ->method('setParameter')
            ->with('subject1_class', MockObject::class)
            ->willReturn($this->qb);

        $this->qb->expects($this->at(6))
            ->method('setParameter')
            ->with('subject1_id', 23)
            ->willReturn($this->qb);

        $this->qb->expects($this->at(7))
            ->method('andWhere')
            ->with('(s.identityClass = :sid0_class AND s.identityName IN (:sid0_ids)) OR (s.identityClass = :sid1_class AND s.identityName IN (:sid1_ids))')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(8))
            ->method('setParameter')
            ->with('sid0_class', MockRole::class)
            ->willReturn($this->qb);

        $this->qb->expects($this->at(9))
            ->method('setParameter')
            ->with('sid0_ids', array('ROLE_USER'))
            ->willReturn($this->qb);

        $this->qb->expects($this->at(10))
            ->method('setParameter')
            ->with('sid1_class', MockUserRoleable::class)
            ->willReturn($this->qb);

        $this->qb->expects($this->at(11))
            ->method('setParameter')
            ->with('sid1_ids', array('user.test'))
            ->willReturn($this->qb);

        $this->qb->expects($this->at(12))
            ->method('andWhere')
            ->with('s.enabled = TRUE AND (s.startedAt IS NULL OR s.startedAt <= :now) AND (s.endedAt IS NULL OR s.endedAt >= :now)')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(13))
            ->method('setParameter')
            ->with('now')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(14))
            ->method('orderBy')
            ->with('p.class', 'asc')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(15))
            ->method('addOrderBy')
            ->with('p.field', 'asc')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(16))
            ->method('addOrderBy')
            ->with('p.operation', 'asc')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(17))
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        $provider = $this->createProvider();
        $this->assertSame($result, $provider->getSharingEntries($subjects, $sids));
    }

    /**
     * @expectedException \Sonatra\Component\Security\Exception\InvalidArgumentException
     * @expectedExceptionMessage The "setSharingManager()" must be called before
     */
    public function testGetSharingEntriesWithoutSharingManager()
    {
        $sids = array(
            new RoleSecurityIdentity('ROLE_USER'),
            new UserSecurityIdentity('user.test'),
        );
        $subjects = array(
            SubjectIdentity::fromObject(new MockObject('foo', 42)),
            SubjectIdentity::fromObject(new MockObject('bar', 23)),
        );

        $this->sharingRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(0))
            ->method('addSelect')
            ->with('p')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(1))
            ->method('leftJoin')
            ->with('s.permissions', 'p')
            ->willReturn($this->qb);

        $provider = $this->createProvider(MockRole::class, MockSharing::class, false);
        $provider->getSharingEntries($subjects, $sids);
    }

    public function testRenameIdentity()
    {
        $this->sharingRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('s')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(0))
            ->method('update')
            ->with(MockSharing::class, 's')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(1))
            ->method('set')
            ->with('s.identityName', ':newName')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(2))
            ->method('where')
            ->with('s.identityClass = :type')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(3))
            ->method('andWhere')
            ->with('s.identityName = :oldName')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(4))
            ->method('setParameter')
            ->with('type', MockRole::class)
            ->willReturn($this->qb);

        $this->qb->expects($this->at(5))
            ->method('setParameter')
            ->with('oldName', 'ROLE_FOO')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(6))
            ->method('setParameter')
            ->with('newName', 'ROLE_BAR')
            ->willReturn($this->qb);

        $this->qb->expects($this->at(7))
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn('RESULT');

        $provider = $this->createProvider();
        $provider->renameIdentity(MockRole::class, 'ROLE_FOO', 'ROLE_BAR');
    }

    protected function createProvider($roleClass = MockRole::class, $sharingClass = MockSharing::class, $addManager = true)
    {
        $this->roleRepo->expects($this->any())
            ->method('getClassName')
            ->willReturn($roleClass);

        $this->sharingRepo->expects($this->any())
            ->method('getClassName')
            ->willReturn($sharingClass);

        $provider = new SharingProvider(
            $this->roleRepo,
            $this->sharingRepo,
            $this->sidManager,
            $this->tokenStorage
        );

        if ($addManager) {
            $provider->setSharingManager($this->sharingManager);
        }

        return $provider;
    }
}

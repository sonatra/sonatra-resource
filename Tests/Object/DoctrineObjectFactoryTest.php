<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Tests\Object;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Fxp\Component\Resource\Object\DoctrineObjectFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests case for Doctrine object factory.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DoctrineObjectFactoryTest extends TestCase
{
    public function testCreate()
    {
        /* @var EntityManagerInterface|MockObject $em */
        $em = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $of = new DoctrineObjectFactory($em);

        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn(new ClassMetadata(\stdClass::class));

        $val = $of->create(\stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $val);
    }
}

<?php
namespace Aoe\FeloginBruteforceProtection\Tests\Unit\Domain\Service;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Kevin Schu <dev@aoe.com>, AOE GmbH
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Aoe\FeloginBruteforceProtection\Domain\Service\RestrictionIdentifierFabric;
use Aoe\FeloginBruteforceProtection\Domain\Service\RestrictionIdentifierFrontendName;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use Aoe\FeloginBruteforceProtection\Domain\Service\RestrictionService;
use Aoe\FeloginBruteforceProtection\System\Configuration;
use \TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * @package Aoe\FeloginBruteforceProtection\Domain\Service
 */
class RestrictionServiceFrontendNameAbstract extends UnitTestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var FrontendUserAuthentication
     */
    protected $frontendUserAuthentication;

    /**
     * @var RestrictionIdentifierFabric
     */
    private $restrictionIdentifierFabric;

    /**
     * @var RestrictionIdentifierFrontendName
     */
    private $restrictionIdentifier;
    /**
     * @var RestrictionService
     */
    private $restriction;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    public function setUp()
    {
        $this->configuration = $this->getMock(
            'Aoe\FeloginBruteforceProtection\System\Configuration',
            array(),
            array(),
            '',
            false
        );
        $this->configuration->expects($this->any())->method('isLoggingEnabled')->will($this->returnValue(false));
        $this->frontendUserAuthentication = $this->getMock('TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication');
        $this->configuration->expects($this->any())->method('getIdentificationIdentifier')->will($this->returnValue(2));
        $this->restrictionIdentifierFabric = new RestrictionIdentifierFabric();
        $this->restrictionIdentifier = $this->restrictionIdentifierFabric->getRestrictionIdentifier(
            $this->configuration,
            $this->frontendUserAuthentication
        );
        $this->restriction = new RestrictionService($this->restrictionIdentifier);
        $this->inject(
            $this->restriction,
            'persistenceManager',
            $this->getMock('\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager')
        );
        $logger = $this->getMock('\Aoe\FeloginBruteforceProtection\Service\Logger\Logger', array('log'));
        $this->inject($this->restriction, 'logger', $logger);
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    public function tearDown()
    {
        unset($this->frontendUserAuthentication);
        unset($this->configuration);
        unset($this->restrictionIdentifier);
    }

    /**
     * @test
     */
    public function isClientRestricted()
    {
        $this->configuration->expects($this->any())->method('getMaximumNumberOfFailures')->will($this->returnValue(10));
        $this->configuration->expects($this->any())->method('getResetTime')->will($this->returnValue(300));
        $this->configuration->expects($this->any())->method('getRestrictionTime')->will($this->returnValue(3000));
        $entryRepository = $this->getMock(
            'Aoe\FeloginBruteforceProtection\Domain\Repository\EntryRepository',
            array('findOneByIdentifier', 'remove'),
            array(),
            '',
            false
        );
        $entry = $this->getMock('Aoe\FeloginBruteforceProtection\Domain\Model\Entry');
        $entry->expects($this->any())->method('getFailures')->will($this->returnValue(0));
        $entry->expects($this->any())->method('getCrdate')->will($this->returnValue(time() - 400));
        $entryRepository->expects($this->any())->method('findOneByIdentifier')->will($this->returnValue($entry));
        $this->inject($this->restriction, 'entryRepository', $entryRepository);
        $this->inject($this->restriction, 'configuration', $this->configuration);

        $this->assertFalse($this->restriction->isClientRestricted());
    }

    /**
     * @test
     */
    public function isClientRestrictedWithFailures()
    {
        $this->configuration->expects($this->any())->method('getMaximumNumberOfFailures')->will($this->returnValue(10));
        $this->configuration->expects($this->any())->method('getResetTime')->will($this->returnValue(300));
        $this->configuration->expects($this->any())->method('getRestrictionTime')->will($this->returnValue(3000));
        $entry = $this->getMock('Aoe\FeloginBruteforceProtection\Domain\Model\Entry', array(), array(), '', false);
        $entry->expects($this->any())->method('getFailures')->will($this->returnValue(10));
        $entry->expects($this->any())->method('getCrdate')->will($this->returnValue(time() - 400));
        $entry->expects($this->any())->method('getTstamp')->will($this->returnValue(time() - 400));
        $entryRepository = $this->getMock(
            'Aoe\FeloginBruteforceProtection\Domain\Repository\EntryRepository',
            array('findOneByIdentifier', 'remove'),
            array(),
            '',
            false
        );
        $entryRepository->expects($this->any())->method('findOneByIdentifier')->will($this->returnValue($entry));
        $this->inject($this->restriction, 'entryRepository', $entryRepository);
        $this->inject($this->restriction, 'configuration', $this->configuration);
        $this->assertTrue($this->restriction->isClientRestricted());
    }

    /**
     * @test
     */
    public function isClientRestrictedWithFailuresAndTimeout()
    {
        $this->configuration->expects($this->any())->method('getMaximumNumberOfFailures')->will($this->returnValue(10));
        $this->configuration->expects($this->any())->method('getResetTime')->will($this->returnValue(300));
        $this->configuration->expects($this->any())->method('getRestrictionTime')->will($this->returnValue(3000));
        $entry = $this->getMock('Aoe\FeloginBruteforceProtection\Domain\Model\Entry', array(), array(), '', false);
        $entry->expects($this->any())->method('getFailures')->will($this->returnValue(10));
        $entry->expects($this->any())->method('getCrdate')->will($this->returnValue(time() - 400));
        $entry->expects($this->any())->method('getTstamp')->will($this->returnValue(time() - 4000));
        $entryRepository = $this->getMock(
            'Aoe\FeloginBruteforceProtection\Domain\Repository\EntryRepository',
            array('findOneByIdentifier', 'remove'),
            array(),
            '',
            false
        );
        $entryRepository->expects($this->any())->method('findOneByIdentifier')->will($this->returnValue($entry));
        $this->inject($this->restriction, 'entryRepository', $entryRepository);
        $this->inject($this->restriction, 'configuration', $this->configuration);
        $this->assertFalse($this->restriction->isClientRestricted());
    }

    /**
     * @test
     */
    public function isClientRestrictedWithLessFailures()
    {
        $this->configuration->expects($this->any())->method('getMaximumNumberOfFailures')->will($this->returnValue(10));
        $this->configuration->expects($this->any())->method('getResetTime')->will($this->returnValue(300));
        $this->configuration->expects($this->any())->method('getRestrictionTime')->will($this->returnValue(3000));
        $entry = $this->getMock('Aoe\FeloginBruteforceProtection\Domain\Model\Entry', array(), array(), '', false);
        $entry->expects($this->any())->method('getFailures')->will($this->returnValue(5));
        $entry->expects($this->any())->method('getCrdate')->will($this->returnValue(time() - 400));
        $entry->expects($this->any())->method('getTstamp')->will($this->returnValue(time() - 400));
        $entryRepository = $this->getMock(
            'Aoe\FeloginBruteforceProtection\Domain\Repository\EntryRepository',
            array('findOneByIdentifier', 'remove'),
            array(),
            '',
            false
        );
        $entryRepository->expects($this->any())->method('findOneByIdentifier')->will($this->returnValue($entry));
        $this->inject($this->restriction, 'entryRepository', $entryRepository);
        $this->inject($this->restriction, 'configuration', $this->configuration);
        $this->assertFalse($this->restriction->isClientRestricted());
    }

    /**
     * @test
     */
    public function doesCheckPreconditionsReturnTrue()
    {
        $this->assertTrue($this->restrictionIdentifier->checkPreconditions());
    }
}

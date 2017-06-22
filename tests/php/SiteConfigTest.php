<?php

namespace SilverStripe\SiteConfig\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Dev\SapphireTest;

/**
 * @package siteconfig
 * @subpackage tests
 */
class SiteConfigTest extends SapphireTest
{
    protected static $fixture_file = 'SiteConfigTest.yml';

    protected static $illegal_extensions = array(
        SiteTree::class => array('SiteTreeSubsites'),
    );

    public function testCanCreateRootPages()
    {
        /** @var SiteConfig $config */
        $config = $this->objFromFixture(SiteConfig::class, 'default');

        // Log in without pages admin access
        $this->logInWithPermission('CMS_ACCESS_AssetAdmin');
        $this->assertFalse($config->canCreateTopLevel());

        // Login with necessary edit permission
        $perms = SiteConfig::config()->get('required_permission');
        $this->logInWithPermission(reset($perms));
        $this->assertTrue($config->canCreateTopLevel());
    }

    public function testCanViewPages()
    {
        /** @var SiteConfig $config */
        $config = $this->objFromFixture(SiteConfig::class, 'default');
        $this->assertTrue($config->canViewPages());
    }

    public function testCanEdit()
    {
        $config = $this->objFromFixture(SiteConfig::class, 'default');

        // Unrelated permissions don't allow siteconfig
        $this->logInWithPermission('CMS_ACCESS_AssetAdmin');
        $this->assertFalse($config->canEdit());

        // Only those with edit permission can do this
        $this->logInWithPermission('EDIT_SITECONFIG');
        $this->assertTrue($config->canEdit());
    }

    public function testCanEditPages()
    {
        /** @var SiteConfig $config */
        $config = $this->objFromFixture(SiteConfig::class, 'default');

        // Log in without pages admin access
        $this->logInWithPermission('CMS_ACCESS_AssetAdmin');
        $this->assertFalse($config->canEditPages());

        // Login with necessary edit permission
        $perms = SiteConfig::config()->get('required_permission');
        $this->logInWithPermission(reset($perms));
        $this->assertTrue($config->canEditPages());
    }
}

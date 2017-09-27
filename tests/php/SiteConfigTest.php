<?php

namespace SilverStripe\SiteConfig\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;

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

        // Admin trumps all
        $this->logInWithPermission('ADMIN');
        $this->assertTrue($config->canCreateTopLevel());

        // Log in without pages admin access
        $this->logInWithPermission('CMS_ACCESS_AssetAdmin');
        $this->assertFalse($config->canCreateTopLevel());

        // Login with necessary edit permission
        $perms = SiteConfig::config()->get('required_permission');
        $this->logInWithPermission(reset($perms));
        $this->assertTrue($config->canCreateTopLevel());

        // "OnlyTheseUsers" restricts to the correct groups
        $config->CanCreateTopLevelType = 'OnlyTheseUsers';
        $this->logInWithPermission('ADMIN');
        $this->assertTrue($config->canCreateTopLevel());
        $this->logInWithPermission('CMS_ACCESS_AssetAdmin');
        $this->assertFalse($config->canCreateTopLevel());
        $config->CreateTopLevelGroups()->add(Security::getCurrentUser()->Groups()->First());
        $this->assertTrue($config->canCreateTopLevel());
    }

    public function testCanViewPages()
    {
        /** @var SiteConfig $config */
        $config = $this->objFromFixture(SiteConfig::class, 'default');

        // "Anyone" can view
        $this->logInWithPermission('ADMIN');
        $this->assertTrue($config->canViewPages());
        $this->logInWithPermission('CMS_ACCESS_AssetAdmin');
        $this->assertTrue($config->canViewPages());

        // "LoggedInUsers" can view
        $config->CanViewType = 'LoggedInUsers';
        $this->logOut();
        $this->assertFalse($config->canViewPages());

        // "OnlyTheseUsers" restricts to the correct groups
        $config->CanViewType = 'OnlyTheseUsers';
        $this->logInWithPermission('ADMIN');
        $this->assertTrue($config->canViewPages());
        $this->logInWithPermission('CMS_ACCESS_AssetAdmin');
        $this->assertFalse($config->canViewPages());
        $config->ViewerGroups()->add(Security::getCurrentUser()->Groups()->First());
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

        // Admin can alway edit
        $this->logInWithPermission('ADMIN');
        $this->assertTrue($config->canEditPages());

        // Log in without pages admin access
        $this->logInWithPermission('CMS_ACCESS_AssetAdmin');
        $this->assertFalse($config->canEditPages());

        // Login with necessary edit permission
        $perms = SiteConfig::config()->get('required_permission');
        $this->logInWithPermission(reset($perms));
        $this->assertTrue($config->canEditPages());

        // "OnlyTheseUsers" restricts to the correct groups
        $config->CanEditType = 'OnlyTheseUsers';
        $this->logInWithPermission('ADMIN');
        $this->assertTrue($config->canEditPages());
        $this->logInWithPermission('CMS_ACCESS_AssetAdmin');
        $this->assertFalse($config->canEditPages());
        $config->EditorGroups()->add(Security::getCurrentUser()->Groups()->First());
        $this->assertTrue($config->canEditPages());
    }
}

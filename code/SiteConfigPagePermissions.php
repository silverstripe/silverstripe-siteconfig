<?php

namespace SilverStripe\SiteConfig;

use SilverStripe\Security\Member;
use SilverStripe\Security\DefaultPermissionChecker;

/**
 * Root permission provider for pages in the SiteTree root
 */
class SiteConfigPagePermissions implements DefaultPermissionChecker
{
    /**
     * Can root be edited?
     *
     * @param Member $member
     * @return bool
     */
    public function canEdit(Member $member = null)
    {
        return SiteConfig::current_site_config()->canEditPages($member);
    }

    /**
     * Can root be viewed?
     *
     * @param Member $member
     * @return bool
     */
    public function canView(Member $member = null)
    {
        return SiteConfig::current_site_config()->canViewPages($member);
    }

    /**
     * Can root be deleted?
     *
     * @param Member $member
     * @return bool
     */
    public function canDelete(Member $member = null)
    {
        // Same as canEdit
        return $this->canEdit($member);
    }

    /**
     * Can root objects be created?
     *
     * @param Member $member
     * @return bool
     */
    public function canCreate(Member $member = null)
    {
        return SiteConfig::current_site_config()->canCreateTopLevel();
    }
}

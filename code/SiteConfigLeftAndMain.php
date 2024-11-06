<?php

namespace SilverStripe\SiteConfig;

use SilverStripe\Admin\SingleRecordAdmin;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\View\Requirements;

class SiteConfigLeftAndMain extends SingleRecordAdmin
{
    private static string $url_segment = 'settings';

    private static int $menu_priority = -1;

    private static string $menu_title = 'Settings';

    private static string $menu_icon_class = 'font-icon-cog';

    private static string $model_class = SiteConfig::class;

    private static array $required_permission_codes = [
        'EDIT_SITECONFIG',
    ];

    public function init()
    {
        parent::init();
        // Add JS required for some aspects of the access tab
        if (class_exists(SiteTree::class)) {
            Requirements::javascript('silverstripe/cms: client/dist/js/bundle.js');
        }
    }
}

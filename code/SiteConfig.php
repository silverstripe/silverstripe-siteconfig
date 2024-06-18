<?php

namespace SilverStripe\SiteConfig;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\View\TemplateGlobalProvider;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\Forms\SearchableMultiDropdownField;
use SilverStripe\Security\InheritedPermissions;

/**
 * SiteConfig
 *
 * @property string Title Title of the website.
 * @property string Tagline Tagline of the website.
 * @property string CanViewType Type of restriction used for view permissions.
 * @property string CanEditType Type of restriction used for edit permissions.
 * @property string CanCreateTopLevelType Type of restriction used for creation of root-level pages.
 * @method ManyManyList<Group> CreateTopLevelGroups()
 * @method ManyManyList<Group> EditorGroups()
 * @method ManyManyList<Group> ViewerGroups()
 * @method ManyManyList<Member> CreateTopLevelMembers()
 * @method ManyManyList<Member> EditorMembers()
 * @method ManyManyList<Member> ViewerMembers()
 */
class SiteConfig extends DataObject implements PermissionProvider, TemplateGlobalProvider
{
    private static $db = [
        "Title" => "Varchar(255)",
        "Tagline" => "Varchar(255)",
        "CanViewType" => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers, OnlyTheseMembers', 'Anyone')",
        "CanEditType" => "Enum('LoggedInUsers, OnlyTheseUsers, OnlyTheseMembers', 'LoggedInUsers')",
        "CanCreateTopLevelType" => "Enum('LoggedInUsers, OnlyTheseUsers, OnlyTheseMembers', 'LoggedInUsers')",
    ];

    private static $many_many = [
        "ViewerGroups" => Group::class,
        "EditorGroups" => Group::class,
        "CreateTopLevelGroups" => Group::class,
        "ViewerMembers" => Member::class,
        "EditorMembers" => Member::class,
        "CreateTopLevelMembers" => Member::class,
    ];

    private static $defaults = [
        "CanViewType" => "Anyone",
        "CanEditType" => "LoggedInUsers",
        "CanCreateTopLevelType" => "LoggedInUsers",
    ];

    private static $table_name = 'SiteConfig';

    /**
     * Default permission to check for 'LoggedInUsers' to create or edit pages
     *
     * @var array
     * @config
     */
    private static $required_permission = [
        'CMS_ACCESS_CMSMain',
        'CMS_ACCESS_LeftAndMain'
    ];

    public function populateDefaults()
    {
        $this->Title = _t(SiteConfig::class . '.SITENAMEDEFAULT', "Your Site Name");
        $this->Tagline = _t(SiteConfig::class . '.TAGLINEDEFAULT', "your tagline here");

        // Allow these defaults to be overridden
        parent::populateDefaults();
    }

    /**
     * Get the fields that are sent to the CMS.
     *
     * In your extensions: updateCMSFields($fields).
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $mapFn = function ($groups = []) {
            $map = [];
            foreach ($groups as $group) {
                // Listboxfield values are escaped, use ASCII char instead of &raquo;
                $map[$group->ID] = $group->getBreadcrumbs(' > ');
            }
            asort($map);
            return $map;
        };
        $groupsMap = $mapFn(Group::get());
        $viewAllGroupsMap = $mapFn(Permission::get_groups_by_permission(['SITETREE_VIEW_ALL', 'ADMIN']));
        $editAllGroupsMap = $mapFn(Permission::get_groups_by_permission(['SITETREE_EDIT_ALL', 'ADMIN']));

        $fields = FieldList::create(
            TabSet::create(
                "Root",
                $tabMain = Tab::create(
                    'Main',
                    $titleField = TextField::create("Title", _t(SiteConfig::class . '.SITETITLE', "Site title")),
                    $taglineField = TextField::create(
                        "Tagline",
                        _t(SiteConfig::class . '.SITETAGLINE', "Site Tagline/Slogan")
                    )
                ),
                $tabAccess = Tab::create(
                    'Access',
                    $viewersOptionsField = OptionsetField::create(
                        "CanViewType",
                        _t(SiteConfig::class . '.VIEWHEADER', "Who can view pages on this site?")
                    ),
                    $viewerGroupsField = ListboxField::create(
                        "ViewerGroups",
                        _t('SilverStripe\\CMS\\Model\\SiteTree.VIEWERGROUPS', "Viewer Groups")
                    )
                        ->setSource($groupsMap)
                        ->setAttribute(
                            'data-placeholder',
                            _t('SilverStripe\\CMS\\Model\\SiteTree.GroupPlaceholder', 'Click to select group')
                        ),
                    $viewerMembersField = SearchableMultiDropdownField::create(
                        "ViewerMembers",
                        _t(SiteConfig::class . '.VIEWERMEMBERS', "Viewer Users"),
                        Member::get()
                    )
                        ->setIsLazyLoaded(true)
                        ->setUseSearchContext(true),
                    $editorsOptionsField = OptionsetField::create(
                        "CanEditType",
                        _t(SiteConfig::class . '.EDITHEADER', "Who can edit pages on this site?")
                    ),
                    $editorGroupsField = ListboxField::create(
                        "EditorGroups",
                        _t('SilverStripe\\CMS\\Model\\SiteTree.EDITORGROUPS', "Editor Groups")
                    )
                        ->setSource($groupsMap)
                        ->setAttribute(
                            'data-placeholder',
                            _t('SilverStripe\\CMS\\Model\\SiteTree.GroupPlaceholder', 'Click to select group')
                        ),
                    $editorMembersField = SearchableMultiDropdownField::create(
                        "EditorMembers",
                        _t(SiteConfig::class . '.EDITORMEMBERS', "Editor Users"),
                        Member::get(),
                    )
                        ->setIsLazyLoaded(true)
                        ->setUseSearchContext(true),
                    $topLevelCreatorsOptionsField = OptionsetField::create(
                        "CanCreateTopLevelType",
                        _t(SiteConfig::class . '.TOPLEVELCREATE', "Who can create pages in the root of the site?")
                    ),
                    $topLevelCreatorsGroupsField = ListboxField::create(
                        "CreateTopLevelGroups",
                        _t(SiteConfig::class . '.TOPLEVELCREATORGROUPS2', "Top level creator groups")
                    )
                        ->setSource($groupsMap)
                        ->setAttribute(
                            'data-placeholder',
                            _t('SilverStripe\\CMS\\Model\\SiteTree.GroupPlaceholder', 'Click to select group')
                        ),
                    $topLevelCreatorsMembersField = SearchableMultiDropdownField::create(
                        "CreateTopLevelMembers",
                        _t(SiteConfig::class . '.TOPLEVELCREATORUSERS', "Top level creator users"),
                        Member::get()
                    )
                        ->setIsLazyLoaded(true)
                        ->setUseSearchContext(true)
                )
            ),
            HiddenField::create('ID')
        );

        $viewersOptionsSource = [];
        $viewersOptionsSource["Anyone"] = _t('SilverStripe\\CMS\\Model\\SiteTree.ACCESSANYONE', "Anyone");
        $viewersOptionsSource["LoggedInUsers"] = _t(
            'SilverStripe\\CMS\\Model\\SiteTree.ACCESSLOGGEDIN',
            "Logged-in users"
        );
        $viewersOptionsSource["OnlyTheseUsers"] = _t(
            'SilverStripe\\CMS\\Model\\SiteTree.ACCESSONLYTHESE',
            "Only these groups (choose from list)"
        );
        $viewersOptionsSource[InheritedPermissions::ONLY_THESE_MEMBERS] = _t(
            SiteConfig::class . '.ACCESSONLYTHESEMEMBERS',
            "Only these users (choose from list)"
        );
        $viewersOptionsField->setSource($viewersOptionsSource);

        if ($viewAllGroupsMap) {
            $viewerGroupsField->setDescription(_t(
                'SilverStripe\\CMS\\Model\\SiteTree.VIEWER_GROUPS_FIELD_DESC',
                'Groups with global view permissions: {groupList}',
                ['groupList' => implode(', ', array_values($viewAllGroupsMap))]
            ));
        }

        if ($editAllGroupsMap) {
            $editorGroupsField->setDescription(_t(
                'SilverStripe\\CMS\\Model\\SiteTree.EDITOR_GROUPS_FIELD_DESC',
                'Groups with global edit permissions: {groupList}',
                ['groupList' => implode(', ', array_values($editAllGroupsMap))]
            ));
        }

        $editorsOptionsSource = [];
        $editorsOptionsSource["LoggedInUsers"] = _t(
            'SilverStripe\\CMS\\Model\\SiteTree.EDITANYONE',
            "Anyone who can log-in to the CMS"
        );
        $editorsOptionsSource["OnlyTheseUsers"] = _t(
            'SilverStripe\\CMS\\Model\\SiteTree.EDITONLYTHESE',
            "Only these groups (choose from list)"
        );
        $editorsOptionsSource[InheritedPermissions::ONLY_THESE_MEMBERS] = _t(
            SiteConfig::class . '.EDITONLYTHESEMEMBERS',
            "Only these users (choose from list)"
        );
        $editorsOptionsField->setSource($editorsOptionsSource);

        $topLevelCreatorsOptionsField->setSource($editorsOptionsSource);

        if (!Permission::check('EDIT_SITECONFIG')) {
            $fields->makeFieldReadonly($taglineField);
            $fields->makeFieldReadonly($titleField);
            // Hide and remove appropriate viewer fields
            $fields->makeFieldReadonly($viewersOptionsField);
            if ($this->CanViewType === InheritedPermissions::ONLY_THESE_USERS) {
                $fields->makeFieldReadonly($viewerGroupsField);
                $fields->removeByName('ViewerMembers');
            } elseif ($this->CanViewType === InheritedPermissions::ONLY_THESE_MEMBERS) {
                $fields->makeFieldReadonly($viewerMembersField);
                $fields->removeByName('ViewerGroups');
            } else {
                $fields->removeByName('ViewerGroups');
                $fields->removeByName('ViewerMembers');
            }
            // Hide and remove appropriate editor fields
            $fields->makeFieldReadonly($editorsOptionsField);
            if ($this->CanEditType === InheritedPermissions::ONLY_THESE_USERS) {
                $fields->makeFieldReadonly($editorGroupsField);
                $fields->removeByName('EditorMembers');
            } elseif ($this->CanEditType === InheritedPermissions::ONLY_THESE_MEMBERS) {
                $fields->makeFieldReadonly($editorMembersField);
                $fields->removeByName('EditorGroups');
            } else {
                $fields->removeByName('EditorGroups');
                $fields->removeByName('EditorMembers');
            }
            // Hide and remove appropriate top-level creator fields
            $fields->makeFieldReadonly($topLevelCreatorsOptionsField);
            if ($this->CanCreateTopLevelType === InheritedPermissions::ONLY_THESE_USERS) {
                $fields->makeFieldReadonly($topLevelCreatorsGroupsField);
                $fields->removeByName('CreateTopLevelMembers');
            } elseif ($this->CanCreateTopLevelType === InheritedPermissions::ONLY_THESE_MEMBERS) {
                $fields->makeFieldReadonly($topLevelCreatorsMembersField);
                $fields->removeByName('CreateTopLevelGroups');
            } else {
                $fields->removeByName('CreateTopLevelGroups');
                $fields->removeByName('CreateTopLevelMembers');
            }
        }

        if (file_exists(BASE_PATH . '/install.php')) {
            $fields->addFieldToTab(
                'Root.Main',
                LiteralField::create(
                    'InstallWarningHeader',
                    '<div class="alert alert-warning">' . _t(
                        'SilverStripe\\CMS\\Model\\SiteTree.REMOVE_INSTALL_WARNING',
                        'Warning: You should remove install.php from this SilverStripe install for security reasons.'
                    ) . '</div>'
                ),
                'Title'
            );
        }

        $tabMain->setTitle(_t(SiteConfig::class . '.TABMAIN', "Main"));
        $tabAccess->setTitle(_t(SiteConfig::class . '.TABACCESS', "Access"));
        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    /**
     * Get the actions that are sent to the CMS.
     *
     * In your extensions: updateEditFormActions($actions)
     *
     * @return FieldList
     */
    public function getCMSActions()
    {
        if (Permission::check('ADMIN') || Permission::check('EDIT_SITECONFIG')) {
            $actions = FieldList::create(
                FormAction::create(
                    'save_siteconfig',
                    _t('SilverStripe\\CMS\\Controllers\\CMSMain.SAVE', 'Save')
                )->addExtraClass('btn-primary font-icon-save')
            );
        } else {
            $actions = FieldList::create();
        }

        $this->extend('updateCMSActions', $actions);

        return $actions;
    }

    /**
     * @return string
     */
    public function CMSEditLink()
    {
        return SiteConfigLeftAndMain::singleton()->Link();
    }

    /**
     * Get the current sites SiteConfig, and creates a new one through
     * {@link make_site_config()} if none is found.
     *
     * @return SiteConfig
     */
    public static function current_site_config()
    {
        $siteConfig = DataObject::get_one(SiteConfig::class);
        if (!$siteConfig) {
            $siteConfig = SiteConfig::make_site_config();
        }

        static::singleton()->extend('updateCurrentSiteConfig', $siteConfig);

        return $siteConfig;
    }

    /**
     * Setup a default SiteConfig record if none exists.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        $config = DataObject::get_one(SiteConfig::class);

        if (!$config) {
            SiteConfig::make_site_config();

            DB::alteration_message("Added default site config", "created");
        }
    }

    /**
     * Create SiteConfig with defaults from language file.
     *
     * @return SiteConfig
     */
    public static function make_site_config()
    {
        $config = SiteConfig::create();
        $config->write();

        return $config;
    }

    /**
     * Can a user view this SiteConfig instance?
     *
     * @param Member $member
     * @return boolean
     */
    public function canView($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        $extended = $this->extendedCan('canView', $member);
        if ($extended !== null) {
            return $extended;
        }

        // Assuming all that can edit this object can also view it
        return $this->canEdit($member);
    }

    /**
     * Can a user view pages on this site? This method is only
     * called if a page is set to Inherit, but there is nothing
     * to inherit from.
     *
     * @param Member $member
     * @return boolean
     */
    public function canViewPages($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        if ($member && Permission::checkMember($member, "ADMIN")) {
            return true;
        }

        $extended = $this->extendedCan('canViewPages', $member);
        if ($extended !== null) {
            return $extended;
        }

        if (!$this->CanViewType || $this->CanViewType == 'Anyone') {
            return true;
        }

        // check for any logged-in users
        if ($this->CanViewType === 'LoggedInUsers' && $member) {
            return true;
        }

        // check for specific groups
        if ($this->CanViewType === 'OnlyTheseUsers' && $member && $member->inGroups($this->ViewerGroups())) {
            return true;
        }

        // check for specific users
        if ($this->CanViewType === InheritedPermissions::ONLY_THESE_MEMBERS
            && $member
            && $this->ViewerMembers()->filter('ID', $member->ID)->count() > 0
        ) {
            return true;
        }

        return false;
    }

    /**
     * Can a user edit pages on this site? This method is only
     * called if a page is set to Inherit, but there is nothing
     * to inherit from, or on new records without a parent.
     *
     * @param Member $member
     * @return boolean
     */
    public function canEditPages($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        if ($member && Permission::checkMember($member, "ADMIN")) {
            return true;
        }

        $extended = $this->extendedCan('canEditPages', $member);
        if ($extended !== null) {
            return $extended;
        }

        // check for any logged-in users with CMS access
        if ($this->CanEditType === 'LoggedInUsers'
            && Permission::checkMember($member, $this->config()->get('required_permission'))
        ) {
            return true;
        }

        // check for specific groups
        if ($this->CanEditType === 'OnlyTheseUsers' && $member && $member->inGroups($this->EditorGroups())) {
            return true;
        }

        // check for specific users
        if ($this->CanEditType === InheritedPermissions::ONLY_THESE_MEMBERS
            && $member
            && $this->EditorMembers()->filter('ID', $member->ID)->count() > 0
        ) {
            return true;
        }

        return false;
    }

    public function canEdit($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        $extended = $this->extendedCan('canEdit', $member);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::checkMember($member, "EDIT_SITECONFIG");
    }

    /**
     * @return array
     */
    public function providePermissions()
    {
        return [
            'EDIT_SITECONFIG' => [
                'name' => _t(SiteConfig::class . '.EDIT_PERMISSION', 'Manage site configuration'),
                'category' => _t(
                    'SilverStripe\\Security\\Permission.PERMISSIONS_CATEGORY',
                    'Roles and access permissions'
                ),
                'help' => _t(
                    SiteConfig::class . '.EDIT_PERMISSION_HELP',
                    'Ability to edit global access settings/top-level page permissions.'
                ),
                'sort' => 400
            ]
        ];
    }

    /**
     * Can a user create pages in the root of this site?
     *
     * @param Member $member
     * @return boolean
     */
    public function canCreateTopLevel($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        if ($member && Permission::checkMember($member, "ADMIN")) {
            return true;
        }

        $extended = $this->extendedCan('canCreateTopLevel', $member);
        if ($extended !== null) {
            return $extended;
        }

        // check for any logged-in users with CMS permission
        if ($this->CanCreateTopLevelType === 'LoggedInUsers'
            && Permission::checkMember($member, $this->config()->get('required_permission'))
        ) {
            return true;
        }

        // check for specific groups
        if ($this->CanCreateTopLevelType === 'OnlyTheseUsers'
            && $member
            && $member->inGroups($this->CreateTopLevelGroups())
        ) {
            return true;
        }

        // check for specific users
        if ($this->CanCreateTopLevelType === InheritedPermissions::ONLY_THESE_MEMBERS
            && $member
            && $this->CreateTopLevelMembers()->filter('ID', $member->ID)->count() > 0
        ) {
            return true;
        }

        return false;
    }

    /**
     * Add $SiteConfig to all SSViewers
     */
    public static function get_template_global_variables()
    {
        return [
            'SiteConfig' => 'current_site_config',
        ];
    }
}

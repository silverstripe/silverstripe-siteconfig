<?php

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionProvider;


/**
 * SiteConfig
 *
 * @property string Title Title of the website.
 * @property string Tagline Tagline of the website.
 * @property string CanViewType Type of restriction used for view permissions.
 * @property string CanEditType Type of restriction used for edit permissions.
 * @property string CanCreateTopLevelType Type of restriction used for creation of root-level pages.
 *
 * @method ManyManyList ViewerGroups() List of groups that can view SiteConfig.
 * @method ManyManyList EditorGroups() List of groups that can edit SiteConfig.
 * @method ManyManyList CreateTopLevelGroups() List of groups that can create root-level pages.
 *
 * @package siteconfig
 */
class SiteConfig extends DataObject implements PermissionProvider, TemplateGlobalProvider
{
    private static $db = array(
        "Title" => "Varchar(255)",
        "Tagline" => "Varchar(255)",
        "CanViewType" => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers', 'Anyone')",
        "CanEditType" => "Enum('LoggedInUsers, OnlyTheseUsers', 'LoggedInUsers')",
        "CanCreateTopLevelType" => "Enum('LoggedInUsers, OnlyTheseUsers', 'LoggedInUsers')",
    );

    private static $many_many = array(
        "ViewerGroups" => "SilverStripe\\Security\\Group",
        "EditorGroups" => "SilverStripe\\Security\\Group",
        "CreateTopLevelGroups" => "SilverStripe\\Security\\Group"
    );

    private static $defaults = array(
        "CanViewType" => "Anyone",
        "CanEditType" => "LoggedInUsers",
        "CanCreateTopLevelType" => "LoggedInUsers",
    );

    /**
     * Default permission to check for 'LoggedInUsers' to create or edit pages
     *
     * @var array
     * @config
     */
    private static $required_permission = array('CMS_ACCESS_CMSMain', 'CMS_ACCESS_LeftAndMain');


    public function populateDefaults()
    {
        $this->Title = _t('SiteConfig.SITENAMEDEFAULT', "Your Site Name");
        $this->Tagline = _t('SiteConfig.TAGLINEDEFAULT', "your tagline here");

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
        $groupsMap = array();

        foreach (Group::get() as $group) {
            // Listboxfield values are escaped, use ASCII char instead of &raquo;
            $groupsMap[$group->ID] = $group->getBreadcrumbs(' > ');
        }
        asort($groupsMap);

        $fields = new FieldList(
            new TabSet("Root",
                $tabMain = new Tab('Main',
                    $titleField = new TextField("Title", _t('SiteConfig.SITETITLE', "Site title")),
                    $taglineField = new TextField("Tagline", _t('SiteConfig.SITETAGLINE', "Site Tagline/Slogan"))
                ),
                $tabAccess = new Tab('Access',
                    $viewersOptionsField = new OptionsetField("CanViewType", _t('SiteConfig.VIEWHEADER', "Who can view pages on this site?")),
                    $viewerGroupsField = ListboxField::create("ViewerGroups", _t('SiteTree.VIEWERGROUPS', "Viewer Groups"))
                        ->setSource($groupsMap)
                        ->setAttribute(
                            'data-placeholder',
                            _t('SiteTree.GroupPlaceholder', 'Click to select group')
                        ),
                    $editorsOptionsField = new OptionsetField("CanEditType", _t('SiteConfig.EDITHEADER', "Who can edit pages on this site?")),
                    $editorGroupsField = ListboxField::create("EditorGroups", _t('SiteTree.EDITORGROUPS', "Editor Groups"))
                        ->setSource($groupsMap)
                        ->setAttribute(
                            'data-placeholder',
                            _t('SiteTree.GroupPlaceholder', 'Click to select group')
                        ),
                    $topLevelCreatorsOptionsField = new OptionsetField("CanCreateTopLevelType", _t('SiteConfig.TOPLEVELCREATE', "Who can create pages in the root of the site?")),
                    $topLevelCreatorsGroupsField = ListboxField::create("CreateTopLevelGroups", _t('SiteTree.TOPLEVELCREATORGROUPS', "Top level creators"))
                        ->setSource($groupsMap)
                        ->setAttribute(
                            'data-placeholder',
                            _t('SiteTree.GroupPlaceholder', 'Click to select group')
                        )
                )
            ),
            new HiddenField('ID')
        );

        $viewersOptionsSource = array();
        $viewersOptionsSource["Anyone"] = _t('SiteTree.ACCESSANYONE', "Anyone");
        $viewersOptionsSource["LoggedInUsers"] = _t('SiteTree.ACCESSLOGGEDIN', "Logged-in users");
        $viewersOptionsSource["OnlyTheseUsers"] = _t('SiteTree.ACCESSONLYTHESE', "Only these people (choose from list)");
        $viewersOptionsField->setSource($viewersOptionsSource);

        $editorsOptionsSource = array();
        $editorsOptionsSource["LoggedInUsers"] = _t('SiteTree.EDITANYONE', "Anyone who can log-in to the CMS");
        $editorsOptionsSource["OnlyTheseUsers"] = _t('SiteTree.EDITONLYTHESE', "Only these people (choose from list)");
        $editorsOptionsField->setSource($editorsOptionsSource);

        $topLevelCreatorsOptionsField->setSource($editorsOptionsSource);

        if (!Permission::check('EDIT_SITECONFIG')) {
            $fields->makeFieldReadonly($viewersOptionsField);
            $fields->makeFieldReadonly($viewerGroupsField);
            $fields->makeFieldReadonly($editorsOptionsField);
            $fields->makeFieldReadonly($editorGroupsField);
            $fields->makeFieldReadonly($topLevelCreatorsOptionsField);
            $fields->makeFieldReadonly($topLevelCreatorsGroupsField);
            $fields->makeFieldReadonly($taglineField);
            $fields->makeFieldReadonly($titleField);
        }

        if (file_exists(BASE_PATH . '/install.php')) {
            $fields->addFieldToTab("Root.Main", new LiteralField("InstallWarningHeader",
                "<p class=\"message warning\">" . _t("SiteTree.REMOVE_INSTALL_WARNING",
                "Warning: You should remove install.php from this SilverStripe install for security reasons.")
                . "</p>"), "Title");
        }

        $tabMain->setTitle(_t('SiteConfig.TABMAIN', "Main"));
        $tabAccess->setTitle(_t('SiteConfig.TABACCESS', "Access"));
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
            $actions = new FieldList(
                FormAction::create('save_siteconfig', _t('CMSMain.SAVE', 'Save'))
                    ->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
            );
        } else {
            $actions = new FieldList();
        }

        $this->extend('updateCMSActions', $actions);

        return $actions;
    }

    /**
     * @return string
     */
    public function CMSEditLink()
    {
        return singleton('CMSSettingsController')->Link();
    }

    /**
     * Get the current sites SiteConfig, and creates a new one through
     * {@link make_site_config()} if none is found.
     *
     * @return SiteConfig
     */
    public static function current_site_config()
    {
        if ($siteConfig = DataObject::get_one('SiteConfig')) {
            return $siteConfig;
        }

        return self::make_site_config();
    }

    /**
     * Setup a default SiteConfig record if none exists.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        $config = DataObject::get_one('SiteConfig');

        if (!$config) {
            self::make_site_config();

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
            $member = Member::currentUserID();
        }
        if ($member && is_numeric($member)) {
            $member = DataObject::get_by_id('SilverStripe\\Security\\Member', $member);
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
            $member = Member::currentUserID();
        }
        if ($member && is_numeric($member)) {
            $member = DataObject::get_by_id('SilverStripe\\Security\\Member', $member);
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
            $member = Member::currentUserID();
        }
        if ($member && is_numeric($member)) {
            $member = DataObject::get_by_id('SilverStripe\\Security\\Member', $member);
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
            && Permission::checkMember($member, $this->config()->required_permission)
        ) {
            return true;
        }

            // check for specific groups
        if ($this->CanEditType === 'OnlyTheseUsers' && $member && $member->inGroups($this->EditorGroups())) {
            return true;
        }

        return false;
    }

    public function canEdit($member = null)
    {
        if (!$member) {
            $member = Member::currentUserID();
        }
        if ($member && is_numeric($member)) {
            $member = DataObject::get_by_id('SilverStripe\\Security\\Member', $member);
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
        return array(
            'EDIT_SITECONFIG' => array(
                'name' => _t('SiteConfig.EDIT_PERMISSION', 'Manage site configuration'),
                'category' => _t('Permissions.PERMISSIONS_CATEGORY', 'Roles and access permissions'),
                'help' => _t('SiteConfig.EDIT_PERMISSION_HELP', 'Ability to edit global access settings/top-level page permissions.'),
                'sort' => 400
            )
        );
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
            $member = Member::currentUserID();
        }
        if ($member && is_numeric($member)) {
            $member = DataObject::get_by_id('SilverStripe\\Security\\Member', $member);
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
            && Permission::checkMember($member, $this->config()->required_permission)
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

        return false;
    }

    /**
     * Add $SiteConfig to all SSViewers
     */
    public static function get_template_global_variables()
    {
        return array(
            'SiteConfig' => 'current_site_config',
        );
    }
}

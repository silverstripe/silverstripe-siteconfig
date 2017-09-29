<?php

namespace SilverStripe\SiteConfig;

use SilverStripe\Admin\LeftAndMainFormRequestHandler;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Tab;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;
use SilverStripe\Security\DeletedMember;

class SiteConfigHistoryController extends SiteConfigLeftAndMain
{

    private static $url_segment = 'settings/history';

    private static $url_rule = '/$Action/$ID/$VersionID/$OtherVersionID';

    private static $menu_title = 'Site Config History';

    private static $allowed_actions = array(
        'show',
        'compare'
    );

    private static $url_handlers = array(
        '$Action/$ID/$VersionID/$OtherVersionID' => 'handleAction',
    );

    private static $url_priority = 41;

    public function getResponseNegotiator()
    {
        $negotiator = parent::getResponseNegotiator();
        $controller = $this;
        $negotiator->setCallback('CurrentForm', function () use (&$controller) {
            $form = $controller->getEditForm();
            if ($form) {
                return $form->forTemplate();
            } else {
                return $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
            }
        });
        $negotiator->setCallback('default', function () use (&$controller) {
            return $controller->renderWith($controller->getViewer('show'));
        });
        return $negotiator;
    }

    /**
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function show($request)
    {
        // Record id and version for this request
        $id = $this->getRequest()->param('ID');
        if (!$id) {
            $id = SiteConfig::current_site_config()->ID;
        }
        $siteConfig = DataObject::get_by_id(SiteConfig::class, $id);

        $versionID = $request->param('VersionID');
        if (!$versionID) {
            $versionID = $siteConfig->Version;
        }

        // Show id
        $form = $this->getEditForm($id, null, $versionID);

        $negotiator = $this->getResponseNegotiator();
        $controller = $this;
        $negotiator->setCallback('CurrentForm', function () use (&$controller, &$form) {
            return $form
                ? $form->forTemplate()
                : $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
        });
        $negotiator->setCallback('default', function () use (&$controller, &$form) {
            return $controller
                ->customise(array('EditForm' => $form))
                ->renderWith($controller->getViewer('show'));
        });

        return $negotiator->respond($request);
    }

    /**
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function compare($request)
    {
        $form = $this->CompareVersionsForm(
            $request->param('VersionID'),
            $request->param('OtherVersionID')
        );

        $negotiator = $this->getResponseNegotiator();
        $controller = $this;
        $negotiator->setCallback('CurrentForm', function () use (&$controller, &$form) {
            return $form ? $form->forTemplate() :
                $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
        });
        $negotiator->setCallback('default', function () use (&$controller, &$form) {
            return $controller->customise(array('EditForm' => $form))->renderWith($controller->getViewer('show'));
        });

        return $negotiator->respond($request);
    }

    public function getSilverStripeNavigator()
    {
        return false;
    }

    /**
     * @param HTTPRequest $request
     * @return Form
     */
    public function EditForm($request = null)
    {
        if ($request) {
            // Validate VersionID is present
            $versionID = $request->param('VersionID');
            if (!isset($versionID)) {
                $this->httpError(400);
                return null;
            }
        }
        return parent::EditForm($request);
    }

    /**
     * Returns the read only version of the edit form. Detaches all {@link FormAction}
     * instances attached since only action relates to revert.
     *
     * Permission checking is done at the {@link CMSMain::getEditForm()} level.
     *
     * @param int $id ID of the record to show
     * @param array $fields optional
     * @param int $versionID
     * @param int $compareID Compare mode
     *
     * @return Form
     */
    public function getEditForm($id = null, $fields = null, $versionID = null, $compareID = null)
    {
        if (!$id) {
            $id = SiteConfig::current_site_config()->ID;
        }
        $siteConfig = DataObject::get_by_id(SiteConfig::class, $id);

        if (!$versionID) {
            return $this->EmptyForm();
        }

        $record = Versioned::get_version(SiteConfig::class, $id, $versionID);
        if (!$record) {
            return $this->EmptyForm();
        }

        // Get edit form
        $form = parent::getEditForm();

        // Respect permission failures from parent implementation
        if (!($form instanceof Form)) {
            return $form;
        }

        // Clear actions
        $form->setActions(new FieldList());

        $fields = $form->Fields();
        $fields->removeByName("Status");
        $fields->push(new HiddenField("ID"));
        $fields->push(new HiddenField("Version"));
        $fields = $fields->makeReadonly();

        if ($compareID) {
            $link = Controller::join_links(
                $this->Link('show')
            );

            $view = _t('SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.VIEW', "view");

            $message = _t(
                'SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.COMPARINGVERSION',
                "Comparing versions {version1} and {version2}.",
                array(
                    'version1' => sprintf(
                        '%s (<a href="%s">%s</a>)',
                        $versionID,
                        Controller::join_links($link, $versionID),
                        $view
                    ),
                    'version2' => sprintf(
                        '%s (<a href="%s">%s</a>)',
                        $compareID,
                        Controller::join_links($link, $compareID),
                        $view
                    )
                )
            );
        } else {
            if ($record->isLatestVersion()) {
                $message = _t(
                    'SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.VIEWINGLATEST',
                    'Currently viewing the latest version.'
                );
            } else {
                $message = _t(
                    'SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.VIEWINGVERSION',
                    "Currently viewing version {version}.",
                    array('version' => $versionID)
                );
            }
        }

        /** @var Tab $mainTab */
        $mainTab = $fields->fieldByName('Root.Main');
        $mainTab->unshift(
            new LiteralField('CurrentlyViewingMessage', ArrayData::create(array(
                'Content' => DBField::create_field('HTMLFragment', $message),
                'Classes' => 'notice'
            ))->renderWith($this->getTemplatesWithSuffix('_notice')))
        );

        $negotiator = $this->getResponseNegotiator();
        /** @var Form $form */
        $form = Form::create(
            $this,
            'EditForm',
            $fields,
            new FieldList()
        )->setHTMLID('Form_EditForm');
        $form->addExtraClass('flexbox-area-grow fill-height cms-content cms-edit-form');
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');
        $form->loadDataFrom($record);
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

        $this->extend('updateEditForm', $form);

        // History form has both ID and VersionID as suffixes
        $form->setRequestHandler(
            LeftAndMainFormRequestHandler::create($form, [$id, $versionID])
        );

        return $form;
    }


    /**
     * Version select form. Main interface between selecting versions to view
     * and comparing multiple versions.
     *
     * Because we can reload the page directly to a compare view (history/compare/1/2/3)
     * this form has to adapt to those parameters as well.
     *
     * @return Form
     */
    public function VersionsForm()
    {
        $id = $this->getRequest()->param('ID');
        if (!$id) {
            $id = SiteConfig::current_site_config()->ID;
        }
        $record = DataObject::get_by_id(SiteConfig::class, $id);

        $versionsHtml = '';

        $action = $this->getRequest()->param('Action');
        $versionID = $this->getRequest()->param('VersionID');
        $otherVersionID = $this->getRequest()->param('OtherVersionID');

        $showUnpublishedChecked = 0;
        $compareModeChecked = ($action == "compare");

        if ($record) {
            $versions = $record->allVersions();
            $versionID = (!$versionID) ? $record->Version : $versionID;

            if ($versions) {
                foreach ($versions as $k => $version) {
                    $active = false;

                    if ($version->AuthorID && !$version->Author) {
                        $version->DeletedAuthor = DataObject::get_by_id(DeletedMember::class, $version->AuthorID);
                    }
                    if ($version->PublisherID && !$version->Publisher) {
                        $version->DeletedPublisher = DataObject::get_by_id(DeletedMember::class, $version->PublisherID);
                    }

                    if ($version->Version == $versionID || $version->Version == $otherVersionID) {
                        $active = true;
                    }

                    $version->Active = ($active);
                }
            }

            $vd = new ViewableData();

            $versionsHtml = $vd->customise(array(
                'Versions' => $versions
            ))->renderWith($this->getTemplatesWithSuffix('_versions'));
        }

        $fields = new FieldList(
            new CheckboxField(
                'CompareMode',
                _t('SilverStripe\\CMS\\Controllers\\CMSPageHistoryController.COMPAREMODE', 'Compare mode (select two)'),
                $compareModeChecked
            ),
            new LiteralField('VersionsHtml', $versionsHtml),
            $hiddenID = new HiddenField('ID', false, "")
        );

        $form = Form::create(
            $this,
            'VersionsForm',
            $fields,
            new FieldList()
        )->setHTMLID('Form_VersionsForm');
        $form->loadDataFrom($this->getRequest()->requestVars());
        $hiddenID->setValue($id);
        $form->unsetValidator();

        $form
            ->addExtraClass('cms-versions-form') // placeholder, necessary for $.metadata() to work
            ->setAttribute('data-link-tmpl-compare', Controller::join_links($this->Link('compare'), '%s', '%s', '%s'))
            ->setAttribute('data-link-tmpl-show', Controller::join_links($this->Link('show'), '%s', '%s'));

        return $form;
    }

    /**
     * @param int $versionID
     * @param int $otherVersionID
     * @return mixed
     */
    public function CompareVersionsForm($versionID, $otherVersionID)
    {
        if ($versionID > $otherVersionID) {
            $toVersion = $versionID;
            $fromVersion = $otherVersionID;
        } else {
            $toVersion = $otherVersionID;
            $fromVersion = $versionID;
        }

        if (!$toVersion || !$fromVersion) {
            return null;
        }

        $id = $this->getRequest()->param('ID');
        if (!$id) {
            $id = SiteConfig::current_site_config()->ID;
        }

        /** @var SiteConfig $siteconfig */
        $siteconfig = DataObject::get_by_id(SiteConfig::class, $id);

        $record = $siteconfig->compareVersions($fromVersion, $toVersion);

        $fromVersionRecord = Versioned::get_version(SiteConfig::class, $id, $fromVersion);
        $toVersionRecord = Versioned::get_version(SiteConfig::class, $id, $toVersion);

        if (!$fromVersionRecord) {
            user_error("Can't find version $fromVersion of siteconfig $id", E_USER_ERROR);
        }

        if (!$toVersionRecord) {
            user_error("Can't find version $toVersion of siteconfig $id", E_USER_ERROR);
        }

        if (!$record) {
            return null;
        }
        $form = $this->getEditForm($id, null, $fromVersion, $toVersion);
        $form->setActions(new FieldList());
        $form->addExtraClass('compare');

        $form->loadDataFrom($record);
        $form->loadDataFrom(array(
            "ID" => $id,
            "Version" => $fromVersion,
        ));

        // Comparison views shouldn't be editable.
        // As the comparison output is HTML and not valid values for the various field types
        $readonlyFields = $this->transformReadonly($form->Fields());
        $form->setFields($readonlyFields);

        return $form;
    }

    /**
     * Replace all data fields with HTML readonly fields to display diff
     *
     * @param FieldList $fields
     * @return FieldList
     */
    public function transformReadonly(FieldList $fields)
    {
        foreach ($fields->dataFields() as $field) {
            if ($field instanceof HiddenField) {
                continue;
            }
            $newField = $field->castedCopy(HTMLReadonlyField::class);
            $fields->replaceField($field->getName(), $newField);
        }
        return $fields;
    }

    public function getTabIdentifier()
    {
        return 'history';
    }
}

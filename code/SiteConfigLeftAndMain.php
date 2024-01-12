<?php

namespace SilverStripe\SiteConfig;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\CMSPreviewable;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Versioned\RecursivePublishable;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

class SiteConfigLeftAndMain extends LeftAndMain
{
    /**
     * @var string
     */
    private static $url_segment = 'settings';

    /**
     * @var string
     */
    private static $url_rule = '/$Action/$ID/$OtherID';

    /**
     * @var int
     */
    private static $menu_priority = -1;

    /**
     * @var string
     */
    private static $menu_title = 'Settings';

    /**
     * @var string
     */
    private static $menu_icon_class = 'font-icon-cog';

    /**
     * @var string
     */
    private static $tree_class = SiteConfig::class;

    /**
     * @var array
     */
    private static $required_permission_codes = array('EDIT_SITECONFIG');

    /**
     * Initialises the {@link SiteConfig} controller.
     */
    public function init()
    {
        parent::init();
        if (class_exists(SiteTree::class)) {
            Requirements::javascript('silverstripe/cms: client/dist/js/bundle.js');
        }
    }

    /**
     * @param null $id Not used.
     * @param null $fields Not used.
     *
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $siteConfig = SiteConfig::current_site_config();
        $fields = $siteConfig->getCMSFields();

        // Tell the CMS what URL the preview should show
        $home = Director::absoluteBaseURL();
        $fields->push(new HiddenField('PreviewURL', 'Preview URL', $home));

        if ($siteConfig instanceof CMSPreviewable || $siteConfig->has_extension(CMSPreviewable::class)) {
            // Added in-line to the form, but plucked into different view by LeftAndMain.Preview.js upon load
            $fields->push($navField = new LiteralField(
                'SilverStripeNavigator',
                $this->getSilverStripeNavigator($siteConfig)
            ));
            $navField->setAllowHTML(true);
        }

        $validator = $siteConfig->getCMSCompositeValidator();

        $actions = $siteConfig->getCMSActions();
        $negotiator = $this->getResponseNegotiator();
        $form = Form::create(
            $this,
            'EditForm',
            $fields,
            $actions,
            $validator
        )->setHTMLID('Form_EditForm');
        $form->setValidationResponseCallback(function (ValidationResult $errors) use ($negotiator, $form) {
            $request = $this->getRequest();
            if ($request->isAjax() && $negotiator) {
                $result = $form->forTemplate();
                return $negotiator->respond($request, array(
                    'CurrentForm' => function () use ($result) {
                        return $result;
                    }
                ));
            }
        });
        $form->addExtraClass('flexbox-area-grow fill-height cms-content cms-edit-form');
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');

        if ($form->Fields()->hasTabSet()) {
            $form->Fields()->findOrMakeTab('Root')->setTemplate('SilverStripe\\Forms\\CMSTabSet');
        }
        $form->setHTMLID('Form_EditForm');
        $form->loadDataFrom($siteConfig);
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));


        // Announce the capability so the frontend can decide whether to allow preview or not.
        if ($siteConfig instanceof CMSPreviewable || $siteConfig->has_extension(CMSPreviewable::class)) {
            $form->addExtraClass('cms-previewable');
        }

        // Use <button> to allow full jQuery UI styling
        $actions = $actions->dataFields();
        if ($actions) {
            /** @var FormAction $action */
            foreach ($actions as $action) {
                $action->setUseButtonTag(true);
            }
        }

        $this->extend('updateEditForm', $form);

        return $form;
    }

    /**
     * Save the current sites {@link SiteConfig} into the database.
     *
     * @param array $data
     * @param Form $form
     * @return String
     */
    public function save_siteconfig($data, $form)
    {
        $data = $form->getData();
        $siteConfig = DataObject::get_by_id(SiteConfig::class, $data['ID']);
        $form->saveInto($siteConfig);
        $siteConfig->write();
        if ($siteConfig->hasExtension(RecursivePublishable::class)) {
            $siteConfig->publishRecursive();
        }
        $this->response->addHeader(
            'X-Status',
            rawurlencode(_t('SilverStripe\\Admin\\LeftAndMain.SAVEDUP', 'Saved.'))
        );
        return $form->forTemplate();
    }


    public function Breadcrumbs($unlinked = false)
    {
        return new ArrayList(array(
            new ArrayData(array(
                'Title' => static::menu_title(),
                'Link' => $this->Link()
            ))
        ));
    }
}

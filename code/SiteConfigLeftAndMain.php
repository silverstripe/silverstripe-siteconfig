<?php

namespace SilverStripe\SiteConfig;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

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
	private static $tree_class = 'SilverStripe\\SiteConfig\\SiteConfig';

	/**
	 * @var array
	 */
	private static $required_permission_codes = array('EDIT_SITECONFIG');


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

		// Added in-line to the form, but plucked into different view by LeftAndMain.Preview.js upon load
        /** @skipUpgrade */
		$fields->push($navField = new LiteralField('SilverStripeNavigator', $this->getSilverStripeNavigator()));
		$navField->setAllowHTML(true);

		// Retrieve validator, if one has been setup (e.g. via data extensions).
		if ($siteConfig->hasMethod("getCMSValidator")) {
			$validator = $siteConfig->getCMSValidator();
		} else {
			$validator = null;
		}

		$actions = $siteConfig->getCMSActions();
		$negotiator = $this->getResponseNegotiator();
		/** @var Form $form */
		$form = Form::create(
			$this, 'EditForm', $fields, $actions, $validator
		)->setHTMLID('Form_EditForm');
		$form->setValidationResponseCallback(function() use ($negotiator, $form) {
			$request = $this->getRequest();
			if($request->isAjax() && $negotiator) {
				$form->setupFormErrors();
				$result = $form->forTemplate();

				return $negotiator->respond($request, array(
					'CurrentForm' => function() use($result) {
						return $result;
					}
				));
			}
		});
		$form->addExtraClass('cms-content center cms-edit-form');
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');

        if ($form->Fields()->hasTabSet()) {
            $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
        }
		$form->setHTMLID('Form_EditForm');
		$form->loadDataFrom($siteConfig);
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

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
		$siteConfig = SiteConfig::current_site_config();
		$form->saveInto($siteConfig);

		try {
			$siteConfig->write();
		} catch(ValidationException $ex) {
			$form->sessionMessage($ex->getResult()->message(), 'bad');
			return $this->getResponseNegotiator()->respond($this->request);
		}

		$this->response->addHeader('X-Status', rawurlencode(_t('LeftAndMain.SAVEDUP', 'Saved.')));

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

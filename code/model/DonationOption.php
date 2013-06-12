<?php

class DonationOption extends DataObject {

	static $db = array(
		'Title' => 'Varchar',
		'Countries' => 'Text'
	);

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$countries = DataObject::get('EcommerceCountry')->map('Code', 'Name');
		$fields->addFieldToTab('Root.Main', new CheckboxSetField('Countries', null, $countries));
		return $fields;
	}
}
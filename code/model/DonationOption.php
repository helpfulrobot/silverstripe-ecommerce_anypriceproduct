<?php

class DonationOption extends DataObject {

	static $db = array(
		'Title' => 'Varchar',
		'Countries' => 'Text'
	);

	static $field_labels = array(
		'Countries' => 'Available to visitors from ... (leave blank to make available to all customers)'
	);


	function getCMSFields() {
		$fields = parent::getCMSFields();
		$countries = DataObject::get('EcommerceCountry');
		if($countries && $countries->count()) {
			$countryList = $countries->map('Code', 'Name');
			$fields->addFieldToTab('Root.Main', new CheckboxSetField('Countries', self::$field_labels["Countries"], $countryList));
		}
		return $fields;
	}
}

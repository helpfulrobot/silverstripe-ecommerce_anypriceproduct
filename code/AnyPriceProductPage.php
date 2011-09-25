<?php
/**
 *
 *@author nicolaas [at] sunnysideup.co.nz
 *@package ecommerce
 *@subpackage products
 *@requires ecommerce
 *
 *
 */


class AnyPriceProductPage extends Product {

	public static $db = array(
		"AmountFieldLabel" => "Varchar(255)",
		"ActionFieldLabel" => "Varchar(255)",
		"MinimumAmount" => "Decimal(9,2)",
		"MaximumAmount" => "Decimal(9,2)"
	);

	public static $defaults = array(
		"AmountFieldLabel" => "Enter Amount",
		"ActionFieldLabel" => "Add to cart",
		"MinimumAmount" => 1,
		"MaximumAmount" => 100,
		"AllowPurchase" => false,
		"Price" => 0
	);

	static $add_action = 'Adjustable Price Product';

	static $icon = 'ecommerce_anypriceproduct/images/treeicons/AnyPriceProductPage';

	function canCreate($member = null) {
		return !DataObject::get_one("SiteTree", "ClassName = 'AnyPriceProductPage'");
	}

	function canPurchase() {
		return true;
	}

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldsToTab(
			"Root.Content.AddAmountForm",
			array(
				new TextField("AmountFieldLabel", "Amount Field Label (what amount would you like to pay?)"),
				new TextField("ActionFieldLabel", "Action Field Label (e.g. pay entered amount now)"),
				new CurrencyField("MinimumAmount", "Minimum Amount"),
				new CurrencyField("MaximumAmount", "Maximum Amount")
			)

		);
		// Standard product detail fields
		$fields->removeFieldsFromTab(
			'Root.Content.Main',
			array(
				'Weight',
				'Price',
				'Model'
			)
		);


		// Flags for this product which affect it's behaviour on the site
		$fields->removeFieldsFromTab(
			'Root.Content.Main',
			array(
				'FeaturedProduct'
			)
		);

		return $fields;
	}



}

class AnyPriceProductPage_Controller extends Product_Controller {

	function init() {
		parent::init();
	}

	function AddNewPriceForm() {
		$amount = $this->MinimumAmount;
		if($newAmount = Session::get("AnyPriceProductPageAmount")) {
			$amount = $newAmount;
		}
		$fields = new FieldSet(
			new CurrencyField("Amount", $this->AmountFieldLabel, $amount)
		);

		$actions = new FieldSet(
			new FormAction("doAddNewPriceForm", $this->ActionFieldLabel)
		);

		$requiredFields = new RequiredFields(array("Amount"));
		return new Form(
			$controller = $this,
			$name = "AddNewPriceForm",
			$fields,
			$actions,
			$requiredFields
		);
	}

	function doAddNewPriceForm($data, $form) {
		$amount = $this->parseFloat($data["Amount"]);
		if($this->MinimumAmount && ($amount < $this->MinimumAmount)) {
			$form->sessionMessage(_t("AnyPriceProductPage.ERRORINFORMTOOLOW", "Please enter a higher amount."), "bad");
			Director::redirectBack();
			return;
		}
		elseif($this->MaximumAmount && ($amount > $this->MaximumAmount)) {
			$form->sessionMessage(_t("AnyPriceProductPage.ERRORINFORMTOOHIGH", "Please enter a lower amount."), "bad");
			Director::redirectBack();
			return;
		}
		Session::clear("AnyPriceProductPageAmount");
		$alreadyExistingVariations = DataObject::get_one("ProductVariation", "\"ProductID\" = ".$this->ID." AND \"Price\" = ".$amount);
		//create new one if needed
		if(!$alreadyExistingVariations) {
			Currency::setCurrencySymbol(Payment::site_currency());
			$titleDescriptor = new Currency("titleDescriptor");
			$titleDescriptor->setValue($amount);
			$obj = new ProductVariation();
			$obj->Title = _t("AnyPriceProductPage.PAYMENTFOR", "Payment for: ").$titleDescriptor->Nice();
			$obj->Price = $amount;
			$obj->AllowPurchase = true;
			$obj->ProductID = $this->ID;
			$obj->writeToStage("Stage");
			// line below does not work - suspected bug in Sapphire Versioning System
			//$componentSet->add($obj);
		}
		//check if we have one now
		$ourVariation = DataObject::get_one("ProductVariation", "\"ProductID\" = ".$this->ID." AND \"Price\" = ".$amount);
		if($ourVariation) {
			$shoppingCart = ShoppingCart::singleton();
			$shoppingCart->addBuyable($ourVariation);
		}
		else {
			$form->sessionMessage(_t("AnyPriceProductPage.ERROROTHER", "Sorry, we could not add our entry."), "bad");
			Director::redirectBack();
			return;
		}
		$checkoutPage = DataObject::get_one("CheckoutPage");
		if($checkoutPage) {
			Director::redirect($checkoutPage->Link());
		}
		return;
	}

	function setamount($request) {
		if($amount = floatval($request->param("ID"))) {
			Session::set("AnyPriceProductPageAmount", $amount);
		}
		Director::redirect($this->Link());
		return array();
	}

	protected function parseFloat($floatString){
		//hack to clean up currency symbols, etc....
		$LocaleInfo = localeconv();
		$floatString = str_replace($LocaleInfo["mon_decimal_point"] , ".", $floatString);
		$titleDescriptor = new Currency("titleDescriptor");
		$titleDescriptor->setValue(1111111);
		$titleDescriptorString = $titleDescriptor->Nice();
		$titleDescriptorString = str_replace("1", "", $titleDescriptorString);
		//HACK!
		$titleDescriptorString = str_replace(".00", "", $titleDescriptorString);
		for($i = 0; $i < strlen($titleDescriptorString); $i++){
			$char =substr($titleDescriptorString, $i, 1);
			if($char != $LocaleInfo["mon_decimal_point"]) {
				$floatString = str_replace($char, "", $floatString);
			}
		}
		return round(floatval($floatString - 0), 2);
	}

}

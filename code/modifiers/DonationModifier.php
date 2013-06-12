<?php

class DonationModifier extends AnyPriceRoundUpDonationModifier {

	static $has_one = array(
		'Donation' => 'DonationOption'
	);

	function getModifierForm($optionalController = null, $optionalValidator = null) {
		$form = parent::getModifierForm($optionalController, $optionalValidator);
		$donations = $this->LiveDonations();
		$fields = $form->Fields();
		if($donations) {
			$field = $fields->fieldByName('AddDonation');
			$title = $field->Title();
			$source = $field->getSource();
			$fields->removeByName('AddDonation');
			unset($source[1]);
			$donations = $donations->map();
			$source += $donations;
			$fields->push(new DropdownField('DonationID', $title, $source, $this->DonationID));
		}
		$form = new DonationModifier_Form($form->Controller(), 'DonationModifier', $fields, $form->Actions(), $form->getValidator());
		$form->addExtraClass('anyPriceRoundUpDonationModifier');
		return $form;
	}

	protected function LiveDonations() {
		$country = EcommerceCountry::get_country();
		$donations = DataObject::get('DonationOption', "FIND_IN_SET('$country', `Countries`) > 0");
		if(! $donations) {
			$donations = DataObject::get('DonationOption', "`Countries` IS NULL");
		}
		return $donations;
	}

	public function updateAddDonation($donationID) {
		$this->AddDonation = $donationID ? true : false;
		$this->DonationID = $donationID;
		$this->write();
	}

	protected function LiveName() {
		if($this->hasDonation() && $this->DonationID) {
			return $this->Donation()->Title;
		}
		else {
			return parent::LiveName();
		}
	}
}

class DonationModifier_Form extends OrderModifierForm {

	public function submit($data, $form) {
		$order = ShoppingCart::current_order();
		if($order) {
			$modifier = $order->Modifiers('DonationModifier');
			if($modifier) {
				$modifier = $modifier->First();
				$modifier->updateAddDonation($data['DonationID']);
				$msg = $data['DonationID'] ? _t("AnyPriceRoundUpDonationModifier.UPDATED", "Round up donation added - THANK YOU.") : _t("AnyPriceRoundUpDonationModifier.UPDATED", "Round up donation removed.");
				if(isset($data['OtherValue'])) {
					$modifier->updateOtherValue(floatval($data['OtherValue']));
					if(floatval($data['OtherValue']) > 0) {
						$msg .= _t("AnyPriceRoundUpDonationModifier.UPDATED", "Added donation - THANK YOU.");
					}
				}
				else {
					$modifier->updateOtherValue(0);
				}
				$modifier->write();
				return ShoppingCart::singleton()->setMessageAndReturn($msg, "good");
			}
		}
		return ShoppingCart::singleton()->setMessageAndReturn(_t("AnyPriceRoundUpDonationModifier.NOTUPDATED", "Could not update the round up donation status.", "bad"));
	}
}

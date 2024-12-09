<?php

namespace Rule\RuleMailer\Helper;

class CustomerData
{
    public function getPhoneNumberFromQuote($customer, $quote)
    {
        return !empty($customer->getTelephone())?$customer->getTelephone():
            ($customer->getDefaultBillingAddress()?$customer->getDefaultBillingAddress()->getTelephone():
                ($customer->getDefaultShippingAddress()?$customer->getDefaultShippingAddress()->getTelephone():
                    ($quote->getBillingAddress()?$quote->getBillingAddress()->getTelephone():
                        ($quote->getShippingAddress()?$quote->getShippingAddress()->getTelephone(): null))));
    }

    public function getPhoneNumberFromShipment($customer, $shipment)
    {
        return !empty($customer->getTelephone())?$customer->getTelephone():
            ($customer->getDefaultBillingAddress()?$customer->getDefaultBillingAddress()->getTelephone():
                ($customer->getDefaultShippingAddress()?$customer->getDefaultShippingAddress()->getTelephone():
                    ($shipment->getBillingAddress()?$shipment->getBillingAddress()->getTelephone():
                        ($shipment->getShippingAddress()?$shipment->getShippingAddress()->getTelephone(): null))));
    }
}

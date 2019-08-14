<?php

/*
 * Observer.php.
 */

class Girosolution_Giroconnect_Model_Observer {
 
    public function paymentMethodIsActive(Varien_Event_Observer $observer) {
        $event           = $observer->getEvent();
        $method          = $event->getMethodInstance();
        $result          = $event->getResult();
        $currencyCode    = Mage::app()->getStore()->getCurrentCurrencyCode();
       
        if(empty($result->isDeniedInConfig)){ 
            if( $currencyCode != 'EUR'){
                if($method->getCode() == 'giroconnect_creditcard' ){
                    $result->isAvailable = true;
                }else if($method->getCode() == 'giroconnect_giropay' ){
                    $result->isAvailable = false;
                }else if($method->getCode() == 'giroconnect_eps' ){
                    $result->isAvailable = false;
                }else if($method->getCode() == 'giroconnect_ideal' ){
                    $result->isAvailable = false;
                }else if($method->getCode() == 'giroconnect_directdebit' ){
                    $result->isAvailable = false;
                }else if($method->getCode() == 'giroconnect_sofortuw' ){
                    $result->isAvailable = false;
                }else if($method->getCode() == 'giroconnect_paydirekt' ){
                    $result->isAvailable = false;
                }else if($method->getCode() == 'giroconnect_bluecode' ){
                    $result->isAvailable = false;
                }
            }
        }
    }
 
}
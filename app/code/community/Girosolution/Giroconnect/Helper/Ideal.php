<?php

/*
 * Ideal.php
 */

// include_once 'AbstractPaymentProxy.php';
// include_once 'IdealPaymentProxy.php';
 

class Girosolution_Giroconnect_Helper_Ideal extends Mage_Core_Helper_Abstract {
  
    public function getIdealPaymentProxy() {
        $result = Girosolution_Giroconnect_Helper_IdealPaymentProxy::getInstance();
        return $result;
    } // End getIdealPaymentProxy
  
} // *** End class Girosolution_Giroconnect_Helper_Ideal ***
 
?>

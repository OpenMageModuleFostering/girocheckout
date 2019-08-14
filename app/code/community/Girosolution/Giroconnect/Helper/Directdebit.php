<?php

/*
 * Directdebit.php
 */

// include_once 'AbstractPaymentProxy.php';
// include_once 'DirectdebitPaymentProxy.php';

class Girosolution_Giroconnect_Helper_Directdebit extends Mage_Core_Helper_Abstract {
  
    public function getDirectdebitPaymentProxy() {
        $result = Girosolution_Giroconnect_Helper_DirectdebitPaymentProxy::getInstance();
        return $result;
    } // End getDirectdebitPaymentProxy
  
} // *** End class Girosolution_Giroconnect_Helper_Directdebit ***
?>

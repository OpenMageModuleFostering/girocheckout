<?php

/*
 * Data.php.
 */
class Girosolution_Giroconnect_Helper_Data extends Mage_Core_Helper_Abstract {
  
    public function getLanguageCode() {
        $result = 'en';
        $languageCode = Mage::getStoreConfig('general/locale/code', Mage::app()->getStore()->getId());
        if(isset($languageCode)) {
            if(strlen($languageCode) > 2) {
                $languageCode = substr($languageCode, 0, 2);
                $result = strtolower($languageCode);
            }
        }
        return $result;
    } // End getLanguageCode
    
} // *** End class Girosolution_Giroconnect_Helper_Data ***
?>

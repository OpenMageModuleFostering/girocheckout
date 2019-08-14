<?php

/*
 * AbstractPaymentProxy.php.
 */

class Girosolution_Giroconnect_Helper_AbstractPaymentProxy {

  protected $paymentMethod = '';
  protected $baseUrlWeb = '';
  protected $baseUrl = '';
  protected $merchantId = '';
  protected $projectId = '';
  protected $projectPassword = '';
  protected $urlRedirect = '';
  protected $urlNotify = '';
  protected $transactionData = NULL;
  protected $sendEmail = TRUE;
  protected $createInvoice = FALSE;
  protected $languageCode = '';
  protected $sourceId = '194e7c84d1803d643e35f94accff7752';
  protected $purpose = '';

  public function __construct() {
    $this->includeGiroCheckoutLibrary();
  }

// End constructor

  public function setPaymentMethod($pm = '') {
    $pm = isset($pm) ? $pm : '';
    $this->paymentMethod = $pm;
  }

// End setPaymentMethod

  public function getPaymentMethod() {
    return $this->paymentMethod;
  }

// End getPaymentMethod

  public function getBaseUrlWeb() {
    return $this->baseUrlWeb;
  }

// End getBaseUrlWeb    

  public function getBaseUrl() {
    return $this->baseUrl;
  }

// End getBaseUrl    

  public function getMerchantId() {
    return $this->merchantId;
  }

// End getMerchantId

  public function getProjectId() {
    return $this->projectId;
  }

// End getProjectId

  public function getProjectPassword() {
    return $this->projectPassword;
  }

// End getProjectPassword

  public function getNotifyUrl() {
    return $this->urlNotify;
  }

// End getNotifyUrl

  public function getRedirectUrl() {
    return $this->urlRedirect;
  }

// End getRedirectUrl

  public function getLanguageCode() {
    return $this->languageCode;
  }

// End getLanguageCode

  public function canSendEmail() {
    return $this->sendEmail;
  }

// End canSendEmail

  public function canCreateInvoice() {
    return $this->createInvoice;
  }

  public function getPurpose($order) {
    
    $strPurpose = $this->purpose;
    if( empty($strPurpose) )
      $strPurpose = "Best. {ORDERID}, {SHOPNAME}";
    
    $strLastName = "";
    $strFirstName = "";  
    $strShopName = "";
    $aAddress = array();
    $orderid = $order->getRealOrderId();
    
    if( is_object($order->getShippingAddress()) ) {
      $aAddress = $order->getShippingAddress()->getData();
    }
    else {
      if( is_object($order->getBillingAddress()) ) {
        $aAddress = $order->getBillingAddress()->getData();
      }
    }
    
    $strLastName = $aAddress['lastname'];
    $strFirstName = $aAddress['firstname'];
    $strName = $strFirstName . " " . $strLastName;
    $iCustomerNr = $order->getCustomerId();
    $strShopName = Mage::app()->getStore()->getName();
    
    $strPurpose = str_replace( "{ORDERID}", $orderid, $strPurpose );
    $strPurpose = str_replace( "{CUSTOMERID}", $iCustomerNr, $strPurpose );
    $strPurpose = str_replace( "{SHOPNAME}", $strShopName, $strPurpose );
    $strPurpose = str_replace( "{CUSTOMERNAME}", $strName, $strPurpose );
    $strPurpose = str_replace( "{CUSTOMERFIRSTNAME}", $strFirstName, $strPurpose );
    $strPurpose = str_replace( "{CUSTOMERLASTNAME}", $strLastName, $strPurpose );
    
    return substr($strPurpose, 0, 27);    
  }

  public function getShippingOrderInfo($order) {
    $aShippingInfo = array();
    $aAddress = array();
    
    $aOrderData = $order->getData();
    
    if( is_object($order->getShippingAddress()) ) {
      $aAddress = $order->getShippingAddress()->getData();
    }
    else {
      if( is_object($order->getBillingAddress()) ) {
        $aAddress = $order->getBillingAddress()->getData();
      }
    }

    $aProducts = array();
    foreach( $order->getAllItems() as $item ) {
      $aProduct = $item->getData();
      $aProducts[] = array( $aProduct['name'], round($aProduct['qty_ordered'],2), round($aProduct['price'],2)*100, $aProduct['sku'] );
    }
    
    $aShippingInfo['LastName'] = $aAddress['lastname'];
    $aShippingInfo['FirstName'] = $aAddress['firstname'];
    $aShippingInfo['Mail'] = $aAddress['email'];
    $aShippingInfo['Amount'] = round($aOrderData['shipping_amount'], 2);
    $aShippingInfo['DiscountAmount'] = round($aOrderData['discount_amount'], 2);
    $aShippingInfo['CouponCode'] = $aOrderData['coupon_code'];
    $aShippingInfo['orderAmount'] = round($aOrderData['subtotal'], 2);
    $aShippingInfo['Company'] = $aAddress['company'];
    $aShippingInfo['AdditionalAddressInformation'] = '';
    $aShippingInfo['Street'] = $aAddress['street'];
    $aShippingInfo['ZipCode'] = $aAddress['postcode'];
    $aShippingInfo['City'] = $aAddress['city'];
    $aShippingInfo['CountryIso'] = $aAddress['country_id'];
    $aShippingInfo['Products'] = $aProducts;
    
    return $aShippingInfo;    
  }
  
// End getProjectPassword
// End canCreateInvoice

  public function getPaymentOrderComment($success = FALSE) {
    $success = isset($success) ? (is_bool($success) ? $success : FALSE) : FALSE;
    $result = '';
    if ($success == TRUE) {
      if ($this->paymentMethod == "gc_creditcard")
        $result = Mage::helper('giroconnect')->__('Payment with Creditcard was successful');
      else if ($this->paymentMethod == "gc_directdebit")
        $result = Mage::helper('giroconnect')->__('Payment with Direct Debit was successful');
      else if ($this->paymentMethod == "gc_giropay")
        $result = Mage::helper('giroconnect')->__('Payment with giropay was successful');
      else if ($this->paymentMethod == "gc_ideal")
        $result = Mage::helper('giroconnect')->__('Payment with iDEAL was successful');
      else if ($this->paymentMethod == "gc_eps")
        $result = Mage::helper('giroconnect')->__('Payment with eps was successful');
      else if ($this->paymentMethod == "gc_eps")
        $result = Mage::helper('giroconnect')->__('Payment with eps was successful');
      else if ($this->paymentMethod == "gc_sofortuw")
        $result = Mage::helper('giroconnect')->__('Payment with sofort was successful');
      else if ($this->paymentMethod == "gc_paydirekt")
        $result = Mage::helper('giroconnect')->__('Payment with paydirekt was successful');
      else
        $result = Mage::helper('giroconnect')->__('Payment was successful');
    }
    else {
      if ($this->paymentMethod == "gc_creditcard")
        $result = Mage::helper('giroconnect')->__('Payment with Creditcard failed');
      else if ($this->paymentMethod == "gc_directdebit")
        $result = Mage::helper('giroconnect')->__('Payment with Direct Debit failed');
      else if ($this->paymentMethod == "gc_giropay")
        $result = Mage::helper('giroconnect')->__('Payment with giropay failed');
      else if ($this->paymentMethod == "gc_ideal")
        $result = Mage::helper('giroconnect')->__('Payment with iDEAL failed');
      else if ($this->paymentMethod == "gc_eps")
        $result = Mage::helper('giroconnect')->__('Payment with eps failed');
      else if ($this->paymentMethod == "gc_sofortuw")
        $result = Mage::helper('giroconnect')->__('Payment with Sofort failed');
      else if ($this->paymentMethod == "gc_paydirekt")
        $result = Mage::helper('giroconnect')->__('Payment with Paydirekt failed');
      else
        $result = Mage::helper('giroconnect')->__('Payment failed');
    }

    return $result;
  }

// End getPaymentOrderComment

  public function getInvoiceComment() {
    if ($this->paymentMethod == "gc_creditcard")
      return Mage::helper('giroconnect')->__('Automatically generated by Credit Card payment confirmation');
    else if ($this->paymentMethod == "gc_directdebit")
      return Mage::helper('giroconnect')->__('Automatically generated by Direct Debit payment confirmation');
    else if ($this->paymentMethod == "gc_giropay")
      return Mage::helper('giroconnect')->__('Automatically generated by giropay payment confirmation');
    else if ($this->paymentMethod == "gc_ideal")
      return Mage::helper('giroconnect')->__('Automatically generated by iDEAL payment confirmation');
    else if ($this->paymentMethod == "gc_eps")
      return Mage::helper('giroconnect')->__('Automatically generated by eps payment confirmation');
    else if ($this->paymentMethod == "gc_sofortuw")
      return Mage::helper('giroconnect')->__('Automatically generated by Sofort payment confirmation');
    else if ($this->paymentMethod == "gc_paydirekt")
      return Mage::helper('giroconnect')->__('Automatically generated by Paydirekt payment confirmation');
    else
      return Mage::helper('giroconnect')->__('Automatically generated by payment confirmation');
  }

  public function getValidationErrorText() {
    if ($this->paymentMethod == "gc_creditcard")
      return Mage::helper('giroconnect')->__('Invalid Creditcard payment configuration. Please contact the administrator.');
    else if ($this->paymentMethod == "gc_directdebit")
      return Mage::helper('giroconnect')->__('Invalid Direct Debit payment configuration. Please contact the administrator.');
    else if ($this->paymentMethod == "gc_giropay")
      return Mage::helper('giroconnect')->__('Invalid giropay payment configuration. Please contact the administrator.');
    else if ($this->paymentMethod == "gc_ideal")
      return Mage::helper('giroconnect')->__('Invalid iDEAL payment configuration. Please contact the administrator.');
    else if ($this->paymentMethod == "gc_eps")
      return Mage::helper('giroconnect')->__('Invalid eps payment configuration. Please contact the administrator.');
    else if ($this->paymentMethod == "gc_sofortuw")
      return Mage::helper('giroconnect')->__('Invalid SOFORT payment configuration. Please contact the administrator.');
    else if ($this->paymentMethod == "gc_paydirekt")
      return Mage::helper('giroconnect')->__('Invalid Paydirekt payment configuration. Please contact the administrator.');
    else
      return Mage::helper('giroconnect')->__('Invalid payment configuration. Please contact the administrator.');
  }

// End getValidationErrorText

  public function getTransactionData() {
    return $this->transactionData;
  }

// End getTransactionData
  //get Source param.
  public function getGcSource() {

    $Mage = new Mage;

    return "Magento " . $Mage->getVersion() . ";Magento Plugin " . Mage::getConfig()->getNode()->modules->Girosolution_Giroconnect->version;
  }

// End getGcSource

  public function getAsBoolean($value = FALSE) {
    if ($value == 1 || $value == TRUE)
      return TRUE;
    else
      return FALSE;
  }

// End getAsBoolean

  public function getAsInt($value = '3') {
    $result = 1;
    $value = isset($value) ? trim($value) : '';
    if ($value != '') {
      if (ctype_digit($value)) {
        $result = intval($value);
        $result = abs($result);
      }
    }
    return $result;
  }

// End getAsInt

  public function stringStartsWith($str = '', $prefix = '') {
    $str = isset($str) ? $str : '';
    $prefix = isset($prefix) ? $prefix : '';

    if (strlen($str) >= strlen($prefix)) {
      if (strlen($str) == strlen($prefix)) {
        if ($str == $prefix)
          return TRUE;
        else
          return FALSE;
      }
      else {
        $part = substr($str, 0, strlen($prefix));
        if ($part == $prefix)
          return TRUE;
        else
          return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

// End stringStartsWith

  public function stringEndsWith($str = '', $postfix = '') {
    $str = isset($str) ? $str : '';
    $postfix = isset($postfix) ? $postfix : '';

    if (strlen($str) >= strlen($postfix)) {
      if (strlen($str) == strlen($postfix)) {
        if ($str == $postfix)
          return TRUE;
        else
          return FALSE;
      }
      else {
        $startPos = strlen($str) - strlen($postfix);
        $part = substr($str, $startPos, strlen($postfix));
        if ($part == $postfix)
          return TRUE;
        else
          return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

// End stringEndsWith
  //I include the Girocheckout SDK
  private function includeGiroCheckoutLibrary() {
    $libDIR = Mage::getBaseDir('lib');
    $classPath = $libDIR . '/GiroCheckout_SDK/GiroCheckout_SDK.php';
    if (is_file($classPath)) {
      include_once $classPath;
    }
    
    GiroCheckout_SDK_Config::getInstance()->setConfig('DEBUG_LOG_PATH',Mage::getBaseDir('log').'/GiroCheckout');
  }

// End includeGiroconnectLibrary
}

// *** End class AbstractPaymentProxy ***
?>

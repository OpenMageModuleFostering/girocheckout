<?php

/*
 * CreditcardPaymentProxy.php.
 */

class Girosolution_Giroconnect_Helper_CreditcardPaymentProxy extends Girosolution_Giroconnect_Helper_AbstractPaymentProxy {

  private static $refINSTANCE = NULL;
  protected $UseVisaMaster = false;
  protected $UseAMEX = false;
  protected $UseJCB = false;
  
  public function __construct() {
    parent::__construct();
    self::$refINSTANCE = $this;
    $this->setPaymentMethod("gc_creditcard");
    $this->pReadConfiguration();
//    $this->pCreateGiroConnectPaymentClass();
  }

// End constructor

  public static function getInstance() {
    if (isset(self::$refINSTANCE))
      return self::$refINSTANCE;
    else {
      $result = new Girosolution_Giroconnect_Helper_CreditcardPaymentProxy();
      return $result;
    }
  }

// End getInstance

  public function validate() {

    $result = array(
        'status' => 1001,
    );


    if ($this->merchantId == '' ||
            $this->projectId == '' ||
            $this->projectPassword == '') {
      $result['status'] = 1002;
      $result['msg'] = $this->getValidationErrorText();
    }

    return $result;
  }

// End validate

  public function modifyOrderAfterPayment($paymentSuccessful = FALSE, $orderid = '', $updateOrderState = FALSE, $gcRef = null, $gcTransInfo = null) {
    
    $paymentSuccessful = isset($paymentSuccessful) ? (is_bool($paymentSuccessful) ? $paymentSuccessful : FALSE) : FALSE;
    $orderid = isset($orderid) ? $orderid : '';
    $updateOrderState = isset($updateOrderState) ? (is_bool($updateOrderState) ? $updateOrderState : FALSE) : FALSE;
    if( $paymentSuccessful == TRUE ) { 
      $orderStateFinished = Mage_Sales_Model_Order::STATE_PROCESSING; 
    }

    $creditcardModel = Mage::getModel('giroconnect/payment_creditcard');
    if (isset($creditcardModel)) {
      $result = $creditcardModel->modifyOrderAfterPayment($paymentSuccessful, $orderid, $updateOrderState, $this->getPaymentOrderComment($paymentSuccessful), $this->canSendEmail(), $this->canCreateInvoice(), $this->getInvoiceComment(), $gcRef, $gcTransInfo, $orderStateFinished);
      return $result;
    } else {
      return FALSE;
    }
  }

// End getCardTypes

  public function startTransaction($amount = '', $currency = '', $orderId = '', $purpose = '', $clientId = '', $realorderId = '') {
    $this->transactionData = NULL;
    $amount = isset($amount) ? (is_string($amount) ? trim($amount) : '') : '';
    $currency = isset($currency) ? (is_string($currency) ? trim($currency) : 'EUR') : 'EUR';
    $orderId = isset($orderId) ? (is_string($orderId) ? trim($orderId) : '') : '';
    $sourceParam = $this->getGcSource();
    $result = array(
        'status' => 1002,
    );
    try {
      // Set language to EN in case it is distinct DE or EN
      if( strtoupper($this->languageCode) != 'DE' && strtoupper($this->languageCode) != 'EN' ) 
      {
        $strLanguage = 'en';
      }
      else {
        $strLanguage = $this->languageCode; 
      }

      //Sends request to Girocheckout.
      $request = new GiroCheckout_SDK_Request('creditCardTransaction');
      $request->setSecret($this->projectPassword);
      $request->addParam('merchantId', $this->merchantId)
              ->addParam('projectId', $this->projectId)
              ->addParam('merchantTxId', $orderId)
              ->addParam('amount', $amount * 100)
              ->addParam('currency', $currency)
              ->addParam('purpose', $purpose)
              ->addParam('locale', $strLanguage)
              ->addParam('urlRedirect', $this->urlRedirect)
              ->addParam('urlNotify', $this->urlNotify)
              ->addParam('sourceId', $sourceParam)
              ->addParam('orderId', $realorderId)
              ->addParam('customerId', $clientId)
              ->submit();
      
      if ($request->requestHasSucceeded()) {
        $strUrlRedirect = $request->getResponseParam('redirect');

        $result["status"] = 1001;
        $result["redirect"] = $strUrlRedirect;
        $result["reference"] = $request->getResponseParam('reference');
        $result["gcTransInfo"] = $request->getResponseParams();
      } else {
        $iReturnCode = $request->getResponseParam('rc');
        $strResponseMsg = $request->getResponseMessage($iReturnCode, $this->languageCode);

        if ($request->getResponseParam('reference')) {
          $order = Mage::getModel('sales/order');
          $order->loadByIncrementId($orderId);
          $payment = $order->getPayment();
          $payment->setTransactionId($request->getResponseParam('reference'));
          $transaction = $payment->addTransaction('order', null, false, '');
          $transaction->setParentTxnId($request->getResponseParam('reference'));
          $transaction->setIsClosed(1);
          $transaction->setAdditionalInformation("arrInfo", serialize($request->getResponseParams()));
          $transaction->save();
          $order->save();
        }

        $controller = Girosolution_Giroconnect_CreditcardController::getInstance();
        if (isset($controller)) {
          Mage::getSingleton('core/session')->addError($strResponseMsg);
          $controller->redirectToPath('checkout/onepage', array('_secure' => true));
        } else {
          $msg = $strResponseMsg;
          Mage::throwException($msg);
        }
      }
    } catch (Exception $e) {
      $controller = Girosolution_Giroconnect_CreditcardController::getInstance();
      if (isset($controller)) {
        Mage::getSingleton('core/session')->addError(GiroCheckout_SDK_ResponseCode_helper::getMessage(5100, $this->languageCode));
        $controller->redirectToPath('checkout/onepage', array('_secure' => true));
      } else {
        $msg = GiroCheckout_SDK_ResponseCode_helper::getMessage(5100, $this->languageCode);
        Mage::throwException($msg);
      }
    }
    return $result;
  }

// End startTransaction

  private function pReadConfiguration() {
    $baseurlweb = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
    $this->baseUrlWeb = $baseurlweb;

    $baseurl = Mage::getBaseUrl();
    $baseurl = rtrim($baseurl, "/\\");
    $this->baseUrl = $baseurl;

    $value = Mage::getStoreConfig('payment/giroconnect_creditcard/creditcard_mid');
    if (isset($value)) {
      $this->merchantId = $value;
    }
    $value = Mage::getStoreConfig('payment/giroconnect_creditcard/creditcard_pid');
    if (isset($value)) {
      $this->projectId = $value;
    }
    $value = Mage::getStoreConfig('payment/giroconnect_creditcard/creditcard_security');
    if (isset($value)) {
      $this->projectPassword = $value;
    }
    $value = Mage::getStoreConfig('payment/giroconnect_creditcard/creditcard_createinvoice');
    if (isset($value)) {
      $this->createInvoice = $this->getAsBoolean($value);
    }
    $value = Mage::getStoreConfig('payment/giroconnect_creditcard/creditcard_visamaster');
    if (isset($value)) {
      $this->UseVisaMaster = $this->getAsBoolean($value);
    }
    $value = Mage::getStoreConfig('payment/giroconnect_creditcard/creditcard_amex');
    if (isset($value)) {
      $this->UseAMEX = $this->getAsBoolean($value);
    }
    $value = Mage::getStoreConfig('payment/giroconnect_creditcard/creditcard_jcb');
    if (isset($value)) {
      $this->UseJCB = $this->getAsBoolean($value);
    }
    $value = Mage::getStoreConfig('payment/giroconnect_creditcard/creditcard_purpose');
    if (isset($value)) {
      $this->purpose = $value;
    }

    $value = Mage::helper('giroconnect')->getLanguageCode();
    if (isset($value)) {
      $this->languageCode = $value;
    }

    $this->urlRedirect = $this->baseUrl . '/giroconnect/creditcard/redirect';
    $this->urlNotify = $this->baseUrl . '/giroconnect/creditcard/notify';
  }
  
  /**
    * Get the extended logo for creditcards.
    *
    * @author GiroSolution AG
    * @package GiroCheckout
    * @copyright Copyright (c) 2014, GiroSolution AG
    * @return string
    */
   public function getExtendedLogo() {
     $visa_msc = $this->UseVisaMaster;
     $amex = $this->UseAMEX;
     $jcb = $this->UseJCB;

     if( !$visa_msc && !$amex && !$jcb )
       $visa_msc = true;

     return GiroCheckout_SDK_Tools::getCreditCardLogoName($visa_msc, $amex, $jcb);
   }
// End pReadConfiguration
}

// *** End class CreditcardPaymentProxy ***
?>

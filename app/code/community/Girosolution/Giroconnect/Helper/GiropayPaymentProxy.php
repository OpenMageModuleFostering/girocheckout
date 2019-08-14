<?php

/*
 * GiropayPaymentProxy.php
 */

class Girosolution_Giroconnect_Helper_GiropayPaymentProxy extends Girosolution_Giroconnect_Helper_AbstractPaymentProxy {

  private static $refINSTANCE = NULL;

  public function __construct() {
    parent::__construct();
    self::$refINSTANCE = $this;
    $this->setPaymentMethod("gc_giropay");
    $this->pReadConfiguration();
//        $this->pCreateGiroConnectPaymentClass();
  }

// End constructor

  public static function getInstance() {
    if (isset(self::$refINSTANCE))
      return self::$refINSTANCE;
    else {
      $result = new Girosolution_Giroconnect_Helper_GiropayPaymentProxy();
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
  public function modifyOrderAfterPayment($paymentSuccessful = FALSE, $orderid = '', $updateOrderState = FALSE, $strMsgPayment = '', $gcRef = null, $gcTransInfo = null) {
    $paymentSuccessful = isset($paymentSuccessful) ? (is_bool($paymentSuccessful) ? $paymentSuccessful : FALSE) : FALSE;
    $orderid = isset($orderid) ? $orderid : '';
    $updateOrderState = isset($updateOrderState) ? (is_bool($updateOrderState) ? $updateOrderState : FALSE) : FALSE;
    if( $paymentSuccessful == TRUE ) { 
      $orderStateFinished = Mage_Sales_Model_Order::STATE_PROCESSING; 
    }
    $strMsg = $this->getPaymentOrderComment($paymentSuccessful);
    if( !empty($strMsgPayment) )
      $strMsg = $strMsgPayment;
      
    $giropayModel = Mage::getModel('giroconnect/payment_giropay');
    if (isset($giropayModel)) {
      $result = $giropayModel->modifyOrderAfterPayment($paymentSuccessful, $orderid, $updateOrderState, $strMsg, $this->canSendEmail(), $this->canCreateInvoice(), $this->getInvoiceComment(), $gcRef, $gcTransInfo, $orderStateFinished);
      return $result;
    } else {
      return FALSE;
    }
  }

// End modifyOrderAfterPayment

  public function validateBic($BIC = '') {
    $BIC = isset($BIC) ? (is_string($BIC) ? trim($BIC) : '') : '';
    $result = array(
        'status' => 1002,
        'code' => 'sb999',
        'msg' => Mage::helper('giroconnect')->__('Giropay general error'),
    );
    $sourceParam = $this->getGcSource();

    try {
        //Check if the bank provides giropay from BIC.
        $request = new GiroCheckout_SDK_Request('giropayBankstatus');
        $request->setSecret($this->projectPassword);
        $request->addParam('merchantId', $this->merchantId)
                ->addParam('projectId', $this->projectId)
                ->addParam('sourceId', $sourceParam)
                ->addParam('bic', $BIC)
                ->submit();

        if ($request->requestHasSucceeded()) {
          $result["status"] = 1001;
          $result["msg"] = "";
        } else {
          $iReturnCode = $request->getResponseParam('rc');
          $result["msg"] = $request->getResponseMessage($iReturnCode, $this->languageCode);
          $result["status"] = 1002;
        }
      
    } catch (Exception $e) {
      $result["msg"] = $e->getMessage();
      $result["status"] = 1002;
    }
    return $result;
  }

// End validateBankcode

  public function startTransaction($amount = '', $currency = '', $transactionId = '', $purpose = '', $BIC = '', $clientId = '', $realorderId = '') {
    $this->transactionData = NULL;
    $amount = isset($amount) ? (is_string($amount) ? trim($amount) : '') : '';
    $currency = isset($currency) ? (is_string($currency) ? trim($currency) : 'EUR') : 'EUR';
    $transactionId = isset($transactionId) ? (is_string($transactionId) ? trim($transactionId) : '') : '';
    $BIC = isset($BIC) ? (is_string($BIC) ? trim($BIC) : '') : '';
    $sourceParam = $this->getGcSource();

    $result = array(
        'status' => 1002,
    );

    try {
      //Sends request to Girocheckout.
      $request = new GiroCheckout_SDK_Request('giropayTransaction');
      $request->setSecret($this->projectPassword);
      $request->addParam('merchantId', $this->merchantId)
              ->addParam('projectId', $this->projectId)
              ->addParam('merchantTxId', $transactionId)
              ->addParam('amount', $amount * 100)
              ->addParam('currency', $currency)
              ->addParam('purpose', $purpose)
              ->addParam('bic', $BIC)
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
          $order->loadByIncrementId($transactionId);

          $payment = $order->getPayment();
          $payment->setTransactionId($request->getResponseParam('reference'));
          $transaction = $payment->addTransaction('order', null, false, '');
          $transaction->setParentTxnId($request->getResponseParam('reference'));
          $transaction->setIsClosed(1);
          $transaction->setAdditionalInformation("arrInfo", serialize($request->getResponseParams()));
          $transaction->save();
          $order->save();
        }

        $controller = Girosolution_Giroconnect_GiropayController::getInstance();
        if (isset($controller)) {
          Mage::getSingleton('core/session')->addError($strResponseMsg);
          $controller->redirectToPath('checkout/onepage', array('_secure' => true));
        } else {
          $msg = $strResponseMsg;
          Mage::throwException($msg);
        }
      }
    } catch (Exception $e) {
      $controller = Girosolution_Giroconnect_GiropayController::getInstance();
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

    $value = Mage::getStoreConfig('payment/giroconnect_giropay/giropay_mid');
    if (isset($value)) {
      $this->merchantId = $value;
    }
    $value = Mage::getStoreConfig('payment/giroconnect_giropay/giropay_pid');
    if (isset($value)) {
      $this->projectId = $value;
    }
    $value = Mage::getStoreConfig('payment/giroconnect_giropay/giropay_security');
    if (isset($value)) {
      $this->projectPassword = $value;
    }

    $value = Mage::getStoreConfig('payment/giroconnect_giropay/giropay_createinvoice');
    if (isset($value)) {
      $this->createInvoice = $this->getAsBoolean($value);
    }

    $value = Mage::getStoreConfig('payment/giroconnect_giropay/giropay_purpose');
    if (isset($value)) {
      $this->purpose = $value;
    }
    
    $value = Mage::helper('giroconnect')->getLanguageCode();
    if (isset($value)) {
      $this->languageCode = $value;
    }


    $this->urlRedirect = $this->baseUrl . '/giroconnect/giropay/redirect';
    $this->urlNotify = $this->baseUrl . '/giroconnect/giropay/notify';
  }

// End pReadConfiguration
}

// *** End class GiropayPaymentProxy ***
?>

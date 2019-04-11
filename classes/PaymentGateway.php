<?php namespace Shohabbos\Paymeshopaholic\Classes;

use Lovata\OrdersShopaholic\Classes\Helper\AbstractPaymentGateway;

class PaymentGateway extends AbstractPaymentGateway
{
    /** @var array - response from payment gateway */
    protected $arResponse = [];
    protected $arRequestData = [];
    protected $sRedirectURL = '';
    protected $sMessage = '';
    
    protected $obResponse;
    
    /**
    * Get response array
    * @return array
    */
    public function getResponse() : array
    {
        return $this->arResponse;
    }
    
    /** 
    * Get redirect URL
    * @return string
    */
    public function getRedirectURL() : string
    {
        return $this->sRedirectURL;
    }
    
    /**
    * Get error message from payment gateway
    * @return string
    */
    public function getMessage() : string
    {
        return $this->sMessage;
    }
    
    /**
    * Prepare data for request in payment gateway 
    */
    protected function preparePurchaseData()
    {
        $this->arRequestData = [
            'm' => $this->getGatewayProperty('merchant_id'),
            'ac.id' => $this->obOrder->id,
            'a' => $this->obOrder->total_price_value * 100,
            'l' => 'ru',
            'c' => url()->current()
        ];
    }
    
    /**
    * Validate request data
    * @return bool 
    */
    protected function validatePurchaseData()
    {
        return true;
    }
    
    /**
    * Send request to payment gateway
    */
    protected function sendPurchaseData()
    {
        $arPaymentData = (array) $this->obOrder->payment_data;
        $arPaymentData['request'] = $this->arRequestData;

        $this->obOrder->payment_data = $arPaymentData;
        $this->obOrder->save();
    }
    
    /**
    * Process response from payment gateway 
    */
    protected function processPurchaseResponse()
    {
        $this->sRedirectURL = 'https://checkout.paycom.uz/'.base64_encode(http_build_query($this->arRequestData, '', ';'));
        $this->bIsRedirect = true;
    }


    /**
     * Process success request
     * @param string $orderId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processSuccessRequest($orderId, $paymentResponse = [])
    {
        $this->initOrderObject($orderId);
        if (empty($this->obOrder) || empty($this->obPaymentMethod)) {
            return Redirect::to('/');
        }

        if (!empty($paymentResponse)) {
            $arPaymentResponse = (array) $this->obOrder->payment_response;
            $arPaymentResponse['response'] = (array) $paymentResponse;

            $this->obOrder->payment_response = $arPaymentResponse;
            $this->obOrder->transaction_id = $paymentResponse['id'];
            $this->obOrder->save();
        }

        //Set success status in order
        $this->setSuccessStatus();
    }

    /**
     * Process cancel request
     * @param string $orderId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processCancelRequest($orderId, $paymentResponse = [])
    {
        //Init order object
        $this->initOrderObject($orderId);
        if (empty($this->obOrder) || empty($this->obPaymentMethod)) {
            return Redirect::to('/');
        }

        if (!empty($paymentResponse)) {
            $arPaymentResponse = (array) $this->obOrder->payment_response;
            $arPaymentResponse['cancel_response'] = (array) $paymentResponse;

            $this->obOrder->payment_response = $arPaymentResponse;
            $this->obOrder->transaction_id = $paymentResponse['id'];
            $this->obOrder->save();
        }

        //Set cancel status in order
        $this->setCancelStatus();
    }


}

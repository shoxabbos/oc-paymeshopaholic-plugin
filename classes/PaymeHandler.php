<?php namespace Shohabbos\Paymeshopaholic\Classes;

use Input;
use Event;
use Validator;
use Lovata\OrdersShopaholic\Models\Order;
use Shohabbos\Paymeshopaholic\Models\Transaction;
use Lovata\OrdersShopaholic\Models\PaymentMethod;

class PaymeHandler
{
    const JSON_RPC_VERSION = '2.0';

    protected $paymentModel;

    public function __construct() {
        $this->paymentModel = PaymentMethod::where('gateway_id', 'payme')->first();
    }

    public function generatePaymentUrl() {
        $params = Input::only('amount', 'order_id');

        $validator = Validator::make($params, [
            'amount' => 'required',
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()->first()], 422);
        }        


        $arRequestData = [
            'm' => $this->getGatewayProperty('merchant_id'),
            'ac.id' => $params['order_id'],
            'a' => $params['amount'] * 100,
            'l' => 'ru',
            'c' => url()->current()
        ];

        return [
            'data' => 'https://checkout.paycom.uz/'.base64_encode(http_build_query($arRequestData, '', ';'))
        ];
    }

    public function listen(\Illuminate\Http\Request $mainRequest) {
        // get vars 
        $mode = $this->getGatewayProperty('mode', 'test');
        $key = $this->getGatewayProperty($mode);
        $login = $this->getGatewayProperty("login");
        $header = "Basic ".base64_encode($login.":".$key);

        $json = file_get_contents('php://input');
        $response = [];
        $response['jsonrpc'] = self::JSON_RPC_VERSION;
        $request =  json_decode($json, true, 32);

        try {
            if ($header != $mainRequest->header('Authorization')) {
                throw new \Exception( 'Access denied', -32504);
            }

            if ( 
                !isset($request['jsonrpc'] ) || 
                !isset($request['method']) || 
                !isset($request['params']) || 
                !isset($request['id']) 
            ) {
                throw new \Exception( 'Invalid Request', -32600);
            }

            if (!method_exists($this, $request['method'])) {
                throw new \Exception( 'Method not found', -32601);
            }

            if (is_null($request['params'])) {
                $request['params'] = [];
            }

            $response['result'] = $this->{$request['method']}($request['params']);
            $response['id'] = $request['id'];
        } catch ( \Exception $ex ) {
            $response['error'] = new \stdClass();
            $response['error']->code = $ex->getCode();
            $response['error']->message = $ex->getMessage();
            $response['id'] = null;
        }

        return response()->json($response);
    }




    private function CheckTransaction($params) {
        // validate params
        $validator = Validator::make($params, [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            throw new \Exception( 'Invalid Request', -32600);
        }


        // find transaction by payme id
        $transaction = Transaction::where('transaction', $params['id'])->first();
        if (!$transaction) {
            throw new \Exception( 'Transaction not found', -31003);
        }

        return [
            'create_time' => (int) $transaction->create_time,
            'perform_time' => (int) $transaction->perform_time,
            'cancel_time' => (int) $transaction->cancel_time,
            'transaction' => (string) $transaction->id,
            'state' => (int) $transaction->state,
            'reason' => empty($transaction->reason) ? null : (int) $transaction->reason
        ];
    }

    private function CancelTransaction($params) {
        // validate params
        $validator = Validator::make($params, [
            'id' => 'required',
            'reason' => 'required',
        ]);

        if ($validator->fails()) {
            throw new \Exception( 'Invalid Request', -32600);
        }


        // fetch vars
        $id       = $params['id'];
        $reason   = $params['reason'];


        // find transaction by payme id
        $transaction = Transaction::where('transaction', $id)->first();
        if (!$transaction) {
            throw new \Exception( 'Transaction not found', -31003);
        }

        // check state
        if ($transaction->state == 1) {
            $transaction->state = -1;
            $transaction->reason = $reason;
            $transaction->cancel_time = time() * 1000;
            $transaction->save();

            return [
                'cancel_time' => (int) $transaction->cancel_time,
                'state' => (int) $transaction->state,
                'transaction' => (string) $transaction->id
            ];
        } elseif ($transaction->state != 2) {
            return [
                'cancel_time' => (int) $transaction->cancel_time,
                'state' => (int) $transaction->state,
                'transaction' => (string) $transaction->id
            ];
        }


        // take away balance or update status order
        $obPaymentGateway = new PaymentGateway();
        $obPaymentGateway->processCancelRequest($transaction->owner_id, $params);
        

        $transaction->state = -2;
        $transaction->reason = $reason;
        $transaction->cancel_time = time() * 1000;
        $transaction->save();

        return [
            'cancel_time' => (int) $transaction->cancel_time,
            'state' => (int) $transaction->state,
            'transaction' => (string) $transaction->id
        ];
    }

    private function PerformTransaction($params) {
        // validate params
        $validator = Validator::make($params, [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            throw new \Exception( 'Invalid Request', -32600);
        }
         
        // fetch vars
        $id = $params['id'];

        // find transaction by payme id
        $transaction = Transaction::where('transaction', $id)->first();
        if (!$transaction) {
            throw new \Exception( 'Transaction not found', -31003);
        }


        // check state
        if ($transaction->state != 1) {
            if ($transaction->state != 2) {
                throw new \Exception( 'Invalid transaction state', -31008);
            }

            return [
                'state' => (int) $transaction->state,
                'perform_time' => (int) $transaction->perform_time,
                'transaction' => (string) $transaction->id
            ];
        }


        // check timeout
        $currentTime = time() * 1000;
        $timeoutTime = (3600 * 1000) + $transaction->create_time;
        //if ($timeoutTime < $currentTime) {
            //$transaction->state = -1;
            //$transaction->reason = 4;
            //$transaction->save();

            //throw new \Exception('Timeout', -31008);
        //}

        // fill balance or update status order
        $obPaymentGateway = new PaymentGateway();
        $obPaymentGateway->processSuccessRequest($transaction->owner_id, $params);

        
        $transaction->perform_time = time() * 1000;
        $transaction->state = 2;
        $transaction->save();

        return [
            'state' => (int) $transaction->state,
            'perform_time' => (int) $transaction->perform_time,
            'transaction' => (string) $transaction->id
        ];
    }

    private function CreateTransaction($params) {
        // validate params
        $validator = Validator::make($params, [
            'id' => 'required',
            'time' => 'required',
            'amount' => 'required',
            'account' => 'required',
        ]);

        if ($validator->fails()) {
            throw new \Exception( 'Invalid Request', -32600);
        }


        // fetch vars
        $id       = $params['id'];
        $time     = $params['time'];
        $amount   = $params['amount'];
        $orderId  = $params['account']['id'];

        // find transaction by payme id
        $transaction = Transaction::where('transaction', $id)->first();
        if ($transaction) {

            // check state
            if ($transaction->state == 1) {
                
                // check timeout
                $currentTime = time() * 1000;
                $timeoutTime = ($this->getGatewayProperty('timeout') * 1000) + $transaction->create_time;
                if ($timeoutTime < $currentTime) {
                    //$transaction->state = -1;
                    //$transaction->reason = 4;
                    //$transaction->save();

                    //throw new \Exception( 'Timeout', -31008);
                }

                return [
                    'state' => (int) $transaction->state,
                    'create_time' => (int) $transaction->create_time,
                    'transaction' => (string) $transaction->id
                ];   
            } else {
                throw new \Exception( 'Invalid state', -31008);
            }
        }


        // check for Repeated payment
        if ($this->getGatewayProperty('type', 'single') == 'single') {
            $transaction = Transaction::where('owner_id', $orderId)->first();
            if ($transaction) {
                throw new \Exception('Waiting for payment', -31050);   
            }
        }


        // transaction not found
        // check perform transaction 
        $this->CheckPerformTransaction($params);


        // create new transaction
        $transaction = new Transaction();
        $transaction->state = 1;
        $transaction->transaction = $id;
        $transaction->payme_time = $time;
        $transaction->amount = $amount;
        $transaction->create_time = time() * 1000;
        $transaction->owner_id = $orderId;
        $transaction->save();

        return [
            'state' => (int) $transaction->state,
            'create_time' => (int) $transaction->create_time,
            'transaction' => (string) $transaction->id
        ];
    }

    private function CheckPerformTransaction($params) {
        // validate params
        $validator = Validator::make($params, [
            'amount' => 'required',
            'account' => 'required',
        ]);

        if ($validator->fails()) {
            throw new \Exception( 'Invalid Request', -32600);
        }

        // fetch vars
        $amount = $params['amount'];
        $orderId = $params['account']['id'];


        // Is exists order
        $order = Order::find($orderId);
        if (!$order) {
            throw new \Exception('Order not found', -31050);
        }


        // validate amount
        $amount /= 100;
        $result = $amount >= $this->getGatewayProperty('min_amount', 1) && $amount <= $this->getGatewayProperty('max_amount', 100);
        
        if (!$result || $amount != $order->total_price_value) {
            throw new \Exception('Invalid amount', -31001);
        }
        
        return ['allow' => true];
    }




    public function getGatewayProperty($field, $default = null) {
        return  $this->paymentModel 
            ? $this->paymentModel->getProperty($field) 
            : $default;
    }

}

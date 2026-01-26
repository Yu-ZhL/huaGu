<?php

namespace App\Http\Services;

use App\Http\Services\Alipay\AopClient;
use App\Http\Services\Alipay\request\AlipayTradePrecreateRequest;
use App\Http\Services\Alipay\request\AlipayTradeQueryRequest;

class AlipayService
{
    protected $aopClient;

    public function __construct()
    {
        $this->aopClient = new AopClient();
        $this->aopClient->gatewayUrl = config('alipay.gateway_url');
        $this->aopClient->appId = config('alipay.app_id');
        $this->aopClient->rsaPrivateKey = config('alipay.private_key');
        $this->aopClient->alipayrsaPublicKey = config('alipay.public_key');
        $this->aopClient->signType = 'RSA2';
        $this->aopClient->format = 'JSON';
        $this->aopClient->charset = 'utf-8';
    }

    public function createFaceToFaceOrder($orderData)
    {
        $bizContent = [
            'out_trade_no' => $orderData['order_no'],
            'total_amount' => strval($orderData['final_price']),
            'subject' => $orderData['plan_name'],
            'timeout_express' => '30m',
        ];

        $request = new AlipayTradePrecreateRequest();
        $request->setNotifyUrl(config('alipay.notify_url'));
        $request->setBizContent(json_encode($bizContent, JSON_UNESCAPED_UNICODE));

        $result = $this->aopClient->execute($request);

        if (isset($result->alipay_trade_precreate_response) && $result->alipay_trade_precreate_response->code == 10000) {
            return $result->alipay_trade_precreate_response;
        }

        throw new \Exception('支付宝预下单失败: ' . json_encode($result));
    }

    public function verifyNotify()
    {
        $params = request()->post();
        $isValid = $this->aopClient->rsaCheckV1($params, null, 'RSA2');

        if (!$isValid) {
            throw new \Exception('支付宝通知验签失败');
        }

        return (object) $params;
    }

    public function queryOrder($orderNo)
    {
        $bizContent = [
            'out_trade_no' => $orderNo
        ];

        $request = new AlipayTradeQueryRequest();
        $request->setBizContent(json_encode($bizContent, JSON_UNESCAPED_UNICODE));

        $result = $this->aopClient->execute($request);

        if (isset($result->alipay_trade_query_response) && $result->alipay_trade_query_response->code == 10000) {
            return $result->alipay_trade_query_response;
        }

        throw new \Exception('查询支付宝订单失败: ' . json_encode($result));
    }
}

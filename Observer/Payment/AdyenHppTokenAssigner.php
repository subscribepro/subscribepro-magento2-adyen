<?php

declare(strict_types=1);

namespace Swarming\SubscribeProAdyen\Observer\Payment;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Adyen\Payment\Model\Ui\AdyenHppConfigProvider;

class AdyenHppTokenAssigner extends TokenAssigner
{
    /**
     * @param string $paymentMethodToken
     * @param int $customerId
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface|null
     */
    protected function getPaymentToken(
        string $paymentMethodToken,
        int $customerId
    ): ?PaymentTokenInterface {
        return $this->paymentTokenManagement->getByGatewayToken(
            $paymentMethodToken,
            AdyenHppConfigProvider::CODE,
            $customerId
        );
    }
}

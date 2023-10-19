<?php

namespace Swarming\SubscribeProAdyen\Plugin;

use Adyen\Payment\Gateway\Request\RecurringVaultDataBuilder;
use Adyen\Payment\Model\Ui\AdyenCcConfigProvider;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;

class AdyenRecurringVaultDataBuilder
{
    public function afterBuild(RecurringVaultDataBuilder $subject, $result, array $buildSubject)
    {
        /** @var PaymentDataObject $paymentDataObject */
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $paymentMethod = $payment->getMethodInstance();
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();
        $details = json_decode($paymentToken->getTokenDetails() ?: '{}', true);

        if ($paymentMethod->getCode() === AdyenCcConfigProvider::CC_VAULT_CODE) {
            $result['body'] = array_merge($result['body'], [
                'paymentMethod' => [
                    'type' => $details['type'],
                    'storedPaymentMethodId' => $paymentToken->getGatewayToken()
                ]
            ]);
        }
        return $result;
    }
}

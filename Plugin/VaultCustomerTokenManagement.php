<?php

namespace Swarming\SubscribeProAdyen\Plugin;

use Adyen\Payment\Helper\Recurring;
use Adyen\Payment\Helper\Vault;
use Magento\Vault\Model\CustomerTokenManagement;

class VaultCustomerTokenManagement
{
    public function afterGetCustomerSessionTokens(
        CustomerTokenManagement $customerTokenManagement,
        array $customerSessionTokens
    ): array {
        foreach ($customerSessionTokens as $key => $token) {
            if (strpos($token->getPaymentMethodCode(), 'adyen_') === 0) {
                $tokenDetails = json_decode($token->getTokenDetails());
                if (property_exists($tokenDetails, Vault::TOKEN_TYPE) &&
                    in_array($tokenDetails->tokenType, [
                            Recurring::UNSCHEDULED_CARD_ON_FILE]
                    )
                ) {
                    unset($customerSessionTokens[$key]);
                }
            }
        }

        return $customerSessionTokens;
    }
}

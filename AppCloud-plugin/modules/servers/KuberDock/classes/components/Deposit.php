<?php

namespace components;

class Deposit extends \base\CL_Component
{
    /**
     * Adds Deposit value to setup fee if needed
     *
     * @param $params
     * @return array|null
     * @throws \Exception
     */
    public function pricingOverride($params)
    {
        $pid = $params['pid'];
        $product = \KuberDock_Product::model()->loadById($pid);

        if (!$product->isKuberProduct()) {
            return null;
        }

        $pricing = $product->getPricing();
        $setupFee = ($pricing['setup'] > 0) ? $pricing['setup'] : 0;

        $recurring = 0;
        $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadBySessionId();
        // Price for yaml PA
        if ($predefinedApp && !$predefinedApp->getPod() && $product->isFixedPrice()) {
            $recurring = $predefinedApp->getTotalPrice(true);
        }

        $recurring = ($pricing['recurring'] > 0) ? $pricing['recurring'] + $recurring : $recurring;

        return array(
            'setup' => $product->getFirstDeposit() + $setupFee,
            'recurring' => $recurring,
        );
    }
}
<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class KuberDock_Addon_Product extends CL_Model {
    /**
     *
     */
    public function init()
    {
        $this->_pk = 'product_id';
    }

    public function setTableName()
    {
        $this->tableName = 'KuberDock_products';
    }

    /**
     * @param int $productId
     */
    public function updateKubePricing($productId)
    {
        $product = KuberDock_Product::model()->loadById($productId);
        $currency = CL_Currency::model()->getDefaultCurrency();
        $api = $product->getApi();
        $product->createCustomField($product->pid, 'Token', $product::FIELD_TYPE_TEXT);

        if($pricing = $this->loadById($productId)) {
            $this->setKubePricing($product);
        } else {
            $response = $api->createPackage(array(
                'first_deposit' => $product->getConfigOption('firstDeposit'),
                'currency' => $currency->code,
                'prefix' => $currency->prefix,
                'suffix' => $currency->suffix,
                'name' => $product->name,
                'period' => $product->getReadablePaymentType(),
                'price_ip' => $product->getConfigOption('priceIP'),
                'price_pstorage' => $product->getConfigOption('pricePersistentStorage'),
                'price_over_traffic' => $product->getConfigOption('priceOverTraffic'),
            ));
            $data = $response->getData();
            $this->insert(array(
                'product_id' => $productId,
                'kuber_product_id' => $data['id'],
            ));
        }
    }

    /**
     * @param KuberDock_Product $product
     */
    private function setKubePricing(KuberDock_Product $product)
    {
        $currency = CL_Currency::model()->getDefaultCurrency();
        $pricing = $this->loadById($product->id);
        $api = $product->getApi();
        $api->updatePackage($pricing->kuber_product_id, array(
            'first_deposit' => $product->getConfigOption('firstDeposit'),
            'currency' => $currency->code,
            'prefix' => $currency->prefix,
            'suffix' => $currency->suffix,
            'name' => $product->getName(),
            'period' => $product->getReadablePaymentType(),
            'price_ip' => $product->getConfigOption('priceIP'),
            'price_pstorage' => $product->getConfigOption('pricePersistentStorage'),
            'price_over_traffic' => $product->getConfigOption('priceOverTraffic'),
        ));
    }

    /**
     * @param int $productId
     */
    public function deleteKubePricing($productId)
    {
        $addonProduct = $this->loadById($productId);
        $product = KuberDock_Product::model()->loadById($productId);
        // Remove KuberDock packages\kubes
        $kubes = $product->getKubes();
        foreach($kubes as $row) {
            KuberDock_Addon_Kube::model()->loadByParams($row)->deleteKubeFromPackage();
        }
        // Delete package
        $product->getApi()->deletePackage($addonProduct->kuber_product_id);
        // Delete local records
        $this->delete($productId);
    }

    /**
     *
     */
    public function deletePackage()
    {
        $product = KuberDock_Product::model()->loadById($this->product_id);
        $product->getApi()->deletePackage($this->kuber_product_id);
    }

    /**
     * @return array
     */
    public function getBrokenPackages()
    {
        $sql = 'SELECT * FROM `'.KuberDock_Product::model()->tableName.'`
            WHERE servertype = ? AND id NOT IN (SELECT product_id FROM `'.$this->tableName.'`)
            ORDER BY name';
        $values = array(KUBERDOCK_MODULE_NAME);

        return $this->_db->query($sql, $values)->getRows();
    }

    /**
     * @return array
     */
    public function getActiveServerProducts()
    {
        $products = KuberDock_Product::model()->getActive();
        $addonProducts = $this->loadByAttributes();
        $serverPackages = $this->getServerPackages();

        return array_filter($products, function($e) use ($addonProducts, $serverPackages) {
            foreach($addonProducts as $row) {
                if($e['id'] == $row['product_id'] && in_array($row['kuber_product_id'], array_keys($serverPackages))) {
                    return $e;
                }
            }
        });
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getServerPackages()
    {
        $api = $this->getApi();

        $packages = $api->getPackages()->getData();

        return CL_Tools::getKeyAsField($packages, 'id');
    }

    /**
     * @return KuberDock_Api
     */
    private function getApi()
    {
        if($this->product_id) {
            return KuberDock_Product::model()->loadById($this->product_id)->getApi();
        } else {
            return KuberDock_Server::model()->getActive()->getApi();
        }
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
     */
    public static function model($className = __CLASS__)
    {
        if(isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            self::$_models[$className] = new $className;
            return self::$_models[$className];
        }
    }
} 
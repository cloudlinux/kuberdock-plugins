<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Model;
use base\CL_Tools;
use base\models\CL_Currency;
use exceptions\CException;
use exceptions\ExistException;

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
        $product->createCustomField($productId, 'Token', $product::FIELD_TYPE_TEXT);

        if($pricing = $this->loadById($productId)) {
            $this->setKubePricing($product);
        } else {
            try {
                $response = $api->createPackage(array(
                    'first_deposit' => (float) $product->getConfigOption('firstDeposit'),
                    'currency' => $currency->code,
                    'prefix' => $currency->prefix,
                    'suffix' => $currency->suffix,
                    'name' => $product->name,
                    'period' => $product->getReadablePaymentType(),
                    'price_ip' => $product->getConfigOption('priceIP'),
                    'price_pstorage' => $product->getConfigOption('pricePersistentStorage'),
                    'price_over_traffic' => 0, // $product->getConfigOption('priceOverTraffic'), // AC-3783
                    'count_type' => ($product->getConfigOption('billingType')=='PAYG') ? 'payg' : 'fixed',
                ));
                $data = $response->getData();
                $this->insert(array(
                    'product_id' => $productId,
                    'kuber_product_id' => $data['id'],
                ));
            } catch(ExistException $e) {
                if($package = $api->getPackageByName($product->name)) {
                    $this->insert(array(
                        'product_id' => $productId,
                        'kuber_product_id' => $package['id'],
                    ));
                }
            }
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
            'first_deposit' => (float) $product->getConfigOption('firstDeposit'),
            'currency' => $currency->code,
            'prefix' => $currency->prefix,
            'suffix' => $currency->suffix,
            'name' => $product->getName(),
            'period' => $product->getReadablePaymentType(),
            'price_ip' => $product->getConfigOption('priceIP'),
            'price_pstorage' => $product->getConfigOption('pricePersistentStorage'),
            'price_over_traffic' => 0, // $product->getConfigOption('priceOverTraffic'), // AC-3783
            'count_type' => ($product->getConfigOption('billingType')=='PAYG') ? 'payg' : 'fixed',
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
            KuberDock_Addon_Kube_Link::model()->loadByParams($row)->deleteKubeFromPackage();
        }
        // Delete package
        $product->getApi()->deletePackage($addonProduct->kuber_product_id);
        // Delete local records
        $this->delete($productId);
    }

    /**
     * @throws Exception
     */
    public function deletePackage()
    {
        $product = KuberDock_Product::model()->loadById($this->product_id);
        if(!$product) {
            throw new Exception('Product not found');
        }
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
     * @param int $id
     * @param string $serverUrl
     * @return $this
     * @throws CException
     */
    public function getByKuberId($id, $serverUrl = '')
    {
        if($serverUrl) {
            $url = parse_url($serverUrl);
            $host = $url['host'];
            $host .= $url['port'] ? ':'.$url['port'] : '';

            $rows = $this->_db->query('SELECT kp.* FROM KuberDock_products kp
              LEFT JOIN tblproducts p ON kp.product_id=p.id
              LEFT JOIN tblservergroups sg ON p.servergroup=sg.id
              LEFT JOIN tblservergroupsrel sgr ON sg.id=sgr.groupid
              LEFT JOIN tblservers s ON sgr.serverid=s.id
                WHERE p.hidden != 1 AND (s.ipaddress = ? OR s.hostname = ?)
                AND kp.kuber_product_id = ?', array($host, $host, $id))->getRows();

        } else {
            $rows = $this->loadByAttributes(array(
                'kuber_product_id' => $id,
            ));
        }

        if(!$rows) {
            throw new CException(sprintf('Product for KuberDock server: %s not found ', $serverUrl));
        }

        return $this->loadByParams(current($rows));
    }

    /**
     * @param string $serverUrl
     * @return $this
     * @throws CException
     */
    public function getByServerUrl($serverUrl)
    {
        $url = parse_url($serverUrl);
        $host = $url['host'];
        $host .= $url['port'] ? ':'.$url['port'] : '';

        $rows = $this->_db->query('SELECT p.*, kp.kuber_product_id FROM KuberDock_products kp
            LEFT JOIN tblproducts p ON kp.product_id=p.id
            LEFT JOIN tblservergroups sg ON p.servergroup=sg.id
            LEFT JOIN tblservergroupsrel sgr ON sg.id=sgr.groupid
            LEFT JOIN tblservers s ON sgr.serverid=s.id
                WHERE p.hidden != 1 AND (s.ipaddress = ? OR s.hostname = ?) AND s.type = ?', array(
            $host, $host, KUBERDOCK_MODULE_NAME,
        ))->getRows();

        if(!$rows) {
            throw new CException("No available products for KuberDock server: " . $host);
        }

        return $rows;
    }

    /**
     * @return api\KuberDock_Api
     */
    private function getApi()
    {
        if($this->product_id) {
            return KuberDock_Product::model()->loadById($this->product_id)->getApi();
        } else {
            return KuberDock_Server::model()->getActive()->getApi();
        }
    }
} 
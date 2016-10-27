<?php

use components\KuberDock_InvoiceItem;

class KuberDock_Pod
{
    /**
     *
     */
    const UPDATE_KUBES_DESCRIPTION = 'Update resources';

    /**
     * @var \api\KuberDock_Api
     */
    protected $api;

    /**
     * @var array
     */
    protected $kubes;

    /**
     * @var array
     */
    protected $kube;

    /**
     * @var KuberDock_Product
     */
    protected $product;

    /**
     * @var array
     */
    protected $_values = array();

    private $editInvoiceItems = array();

    private $proRate;

    /**
     * @param \KuberDock_Hosting $service
     */
    public function __construct(\KuberDock_Hosting $service)
    {
        $this->api = $service->getApi();
        $this->kubes = \KuberDock_Addon_Kube_Link::loadByProductId($service->packageid);
        $this->product = KuberDock_Product::model()->loadById($service->packageid);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->_values[$name];
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->_values[$name] = $value;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->_values[$name]);
    }

    /**
     * @return array
     */
    public function getKube()
    {
        return $this->kube;
    }

    /**
     * @return \KuberDock_Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return array
     */
    public function getPod()
    {
        return $this->_values;
    }

    /**
     * @return number
     */
    public function totalPrice()
    {
        return $this->kubesPrice() + $this->pdPrice() + $this->ipPrice();
    }

    /**
     * @return number
     */
    public function kubesPrice()
    {
        $price = $this->kube['kube_price'];

        return array_sum(array_map(function ($c) use ($price) {
            return $c['kubes'] * $price;
        }, $this->_values['containers']));

    }

    /**
     * @return number
     */
    public function pdPrice()
    {
        $price = $this->product->getConfigOption('pricePersistentStorage');

        return array_sum(array_map(function ($v) use ($price) {
            if (isset($v['persistentDisk'])) {
                return $v['persistentDisk']['pdSize'] * $price;
            }
        }, $this->_values['volumes']));
    }

    /**
     * @return number
     * @throws Exception
     */
    public function ipPrice()
    {
        $price = $this->product->getConfigOption('priceIP');

        return isset($this->public_ip) ? (float)$price : 0;
    }

    /**
     * @param $name
     * @return array
     */
    public function getContainerByName($name)
    {
        foreach ($this->containers as $c) {
            if ($c['name'] == $name) return $c;
        }

        return array();
    }

    /**
     * @param array $newPod
     * @param KuberDock_User $user
     * @return \base\models\CL_Invoice
     * @throws Exception
     */
    public function updateKubes($newPod, KuberDock_User $user)
    {
        $newKubes = 0;
        $params = array();
        $attributes['id'] = $this->id;

        foreach ($newPod['containers'] as $newContainer) {
            $c = $this->getContainerByName($newContainer['name']);
            if ($c) {
                $params[] = array(
                    'name' => $newContainer['name'],
                    'kubes' => $newContainer['kubes'],
                );
                $newKubes += $newContainer['kubes'] - $c['kubes'];
            }
        }

        if ($newKubes == 0 || !$this->product->isFixedPrice()) {
            $invoice = new \base\models\CL_Invoice();
            return $invoice->setAttributes(array(
                'invoice_id' => 0,
                'status' => $invoice::STATUS_PAID,
            ));
        }
        $attributes['containers'] = $params;

        $data = KuberDock_Addon_Items::model()->loadByAttributes(array('pod_id' => $this->id));
        if ($data) {
            $item = KuberDock_Addon_Items::model()->loadByParams(current($data));
            $billableItem = \base\models\CL_BillableItems::model()->loadById($item->billable_item_id);

            if ($newKubes > 0) {
                $items = array();
                $price = $this->kube['kube_price'] * $billableItem->getProRate();
                foreach ($newPod['containers'] as $newContainer) {
                    $c = $this->getContainerByName($newContainer['name']);
                    $delta = $newContainer['kubes'] - $c['kubes'];
                    if ($c && $delta != 0) {
                        $description = self::UPDATE_KUBES_DESCRIPTION
                            . ', '
                            . abs($delta)
                            . (abs($delta) == 1 ? ' kube' : ' kubes')
                            . ($delta > 0 ? ' added' : ' removed')
                            . ' ('
                            . '"' . $this->name . '", '
                            . '"' . $newContainer['name'] . '"'
                            . ')';

                        $items[] = $this->product->createInvoice($description, $price, 'kube', $delta);
                    }
                }

                $invoice = \base\models\CL_Invoice::model()->createInvoice($user->id, $items, $user->getGateway(), false);
                $invoice = \base\models\CL_Invoice::model()->loadById($invoice);
                $invoiceItem = \base\models\CL_InvoiceItems::model()->loadByParams($invoice->invoiceitems);
                $invoiceItem->setAttributes(array(
                    'type' => $billableItem::TYPE,
                    'relid' => $billableItem->id,
                    'notes' => json_encode($attributes),
                ))->save();

                try {
                    $invoice->applyCredit($invoice->id, $invoice->getSum());
                } catch (Exception $e) {
                    if ($e->getMessage() == 'Amount exceeds customer credit balance') {
                        return $invoice;
                    } else {
                        throw $e;
                    }
                }

                return \base\models\CL_Invoice::model()->loadById($invoice->id);
            } else {
                $service = KuberDock_Hosting::model()->loadById($item->service_id);
                $service->getAdminApi()->redeployPod($this->id, $attributes);
                $billableItem->amount -= abs($newKubes * $this->kube['kube_price']);
                $billableItem->save();
                // Update app
                $pod = $service->getApi()->getPod($this->id);
                $app = KuberDock_Addon_PredefinedApp::model()->loadById($item->app_id);
                $app->data = json_encode($pod);
                $app->save();

                return \base\models\CL_Invoice::model()->loadById($item->invoice_id);
            }
        }
    }

    /**
     * @param array $newPod
     * @param KuberDock_User $user
     * @return \base\models\CL_Invoice
     * @throws Exception
     */
    public function edit($newPod, KuberDock_User $user)
    {
        $attributes['id'] = $this->id;

        if (isset($newPod['template_plan_name'])) {
            $attributes['plan'] = $newPod['template_plan_name'];
        }

        $data = KuberDock_Addon_Items::model()->loadByAttributes(array('pod_id' => $this->id));
        if (!$data) {
            throw new \exceptions\CException('Billable item not found.');
        }
        $item = KuberDock_Addon_Items::model()->loadByParams(current($data));
        $billableItem = \base\models\CL_BillableItems::model()->loadById($item->billable_item_id);
        $this->proRate = $billableItem->getProRate();

        $this->collectInvoiceItems($this->_values, $newPod);

        $price = array_reduce($this->editInvoiceItems, function ($carry, $item) {
            $carry += $item->getTotal();
            return $carry;
        });

        if (!$this->product->isFixedPrice()) {
            $invoice = new \base\models\CL_Invoice();
            return $invoice->setAttributes(array(
                'invoice_id' => 0,
                'status' => $invoice::STATUS_PAID,
            ));
        }

        if ($price > 0) {
            $invoice = \base\models\CL_Invoice::model()->createInvoice($user->id, $this->editInvoiceItems, $user->getGateway(), false);
            $invoice = \base\models\CL_Invoice::model()->loadById($invoice);
            $invoiceItem = \base\models\CL_InvoiceItems::model()->loadByParams($invoice->invoiceitems);
            $invoiceItem->setAttributes(array(
                'type' => $billableItem::TYPE,
                'relid' => $billableItem->id,
                'notes' => json_encode($attributes),
            ))->save();

            try {
                $invoice->applyCredit($invoice->id, $invoice->getSum());
            } catch (Exception $e) {
                if ($e->getMessage() == 'Amount exceeds customer credit balance') {
                    return $invoice;
                } else {
                    throw $e;
                }
            }

            return \base\models\CL_Invoice::model()->loadById($invoice->id);
        } else {
            $service = KuberDock_Hosting::model()->loadById($item->service_id);
            if (isset($attributes['plan'])) {
                // Switch plan
                $service->getAdminApi()->switchPodPlan($this->id, $attributes['plan']);
            } elseif (isset($attributes['containers'])) {
                // Add kubes pod
                // TODO: remove
                $service->getAdminApi()->redeployPod($this->id, $attributes);
            } else {
                // Edit pod
                $service->getAdminApi()->applyEdit($this->id);
            }

            $billableItem->amount += $price;
            $billableItem->save();
            // Update app
            $pod = $service->getApi()->getPod($this->id);
            $app = KuberDock_Addon_PredefinedApp::model()->loadById($item->app_id);
            $app->data = json_encode($pod);
            $app->save();

            return \base\models\CL_Invoice::model()->loadById($item->invoice_id);
        }
    }

    private function collectInvoiceItems($old, $new)
    {
        $kubeTypeChanged = false;
        $oldKubeType = $old['kube_type'];
        $newKubeType = $new['kube_type'];

        if ($oldKubeType != $newKubeType) {
            $oldKube = $this->kube;
            $kubes = \base\CL_Tools::getKeyAsField($this->kubes, 'kuber_kube_id');
            $this->kube = $kubes[$newKubeType];
            $kubeTypeChanged = true;
        }

        $oldKontainers = \base\CL_Tools::getKeyAsField($old['containers'], 'name');
        $newKontainers = \base\CL_Tools::getKeyAsField($new['containers'], 'name');

        $list = array_keys(array_merge($oldKontainers, $newKontainers));

        $filterPorts = function($array) {
            return array_filter($array['ports'], function($item) {
                return $item['isPublic'];
            });
        };

        $newPublicIpUsed = false;
        $oldPublicIpUsed = false;
        foreach ($list as $name) {
            // ports
            $oldPublicIpUsed = $oldPublicIpUsed || (bool) count($filterPorts($oldKontainers[$name]));
            $newPublicIpUsed = $newPublicIpUsed || (bool) count($filterPorts($newKontainers[$name]));

            // kubes
            $newKubesIsset = isset($newKontainers[$name]['kubes']);
            $oldKubesIsset = isset($oldKontainers[$name]['kubes']);
            if ($newKubesIsset && $oldKubesIsset) {
                $delta = $newKontainers[$name]['kubes'] - $oldKontainers[$name]['kubes'];
                if ($kubeTypeChanged) {
                    $oldKubePrice = $oldKube['kube_price'] * $this->proRate;
                    $newKubePrice = $this->kube['kube_price'] * $this->proRate;

                    if ($oldKubeType != $newKubeType) {
                        $description = sprintf('Change kube type from %s to %s (%s)',
                            $oldKube['kube_name'], $this->kube['kube_name'], $newKontainers[$name]['image']);
                        $price = $oldKontainers[$name]['kubes'] * ($newKubePrice - $oldKubePrice);
                        $this->editInvoiceItems[] = $this->product->createInvoice($description, $price, 'kube', 1);
                    }
                }
                if ($delta == 0) {
                    continue;
                }
                $action = $delta > 0 ? 'added' : 'removed';
            } elseif (!$newKubesIsset && $oldKubesIsset) {
                $delta = -$oldKontainers[$name]['kubes'];
                $action = 'removed';
            } elseif ($newKubesIsset && !$oldKubesIsset) {
                $delta = $newKontainers[$name]['kubes'];
                $action = 'added';
            } else {
                continue;
            }
            $price = $this->kube['kube_price'] * $this->proRate;
            $description = self::UPDATE_KUBES_DESCRIPTION
                . ', '
                . abs($delta)
                . (abs($delta) == 1 ? ' kube ' : ' kubes ')
                . $action
                . ' ('
                . '"' . $this->name . '", '
                . '"' . $name . '"'
                . ')';

            $this->editInvoiceItems[] = $this->product->createInvoice($description, $price, 'kube', $delta);
        }

        if ($oldPublicIpUsed != $newPublicIpUsed) {
            if ($newPublicIpUsed && !$oldPublicIpUsed) {
                $count = 1;
                $action = 'added';
            } else {
                $count = -1;
                $action = 'removed';
            }
            $description = self::UPDATE_KUBES_DESCRIPTION . ', public IP ' . $action;
            $ipPrice = (float) $this->product->getConfigOption('priceIP') * $this->proRate;
            $this->editInvoiceItems[] = $this->product->createInvoice($description, $ipPrice, 'IP', $count);
        }

        // volumes
        $this->compareVolumes($old['volumes'], $new['volumes']);
    }

    private function compareVolumes($old, $new)
    {
        $oldVolumes = self::sortVolumes($old);
        $newVolumes = self::sortVolumes($new);
        $psPrice = (float)$this->product->getConfigOption('pricePersistentStorage') * $this->proRate;
        $listVolumes = array_keys(array_merge($oldVolumes, $newVolumes));
        $unit = \components\Units::getPSUnits();
        foreach ($listVolumes as $volumeName) {
            $issetNewPorts = isset($newVolumes[$volumeName]);
            $issetOldPorts = isset($oldVolumes[$volumeName]);
            if ($issetNewPorts && $issetOldPorts) {
                $count = $newVolumes[$volumeName]['persistentDisk']['pdSize'] - $oldVolumes[$volumeName]['persistentDisk']['pdSize'];
                if ($count == 0) {
                    continue;
                }
                $action = $count > 0 ? 'added' : 'removed';
                $name = $newVolumes[$volumeName]['persistentDisk']['pdName'];
            } elseif (!$issetNewPorts && $issetOldPorts) {
                $count = -$oldVolumes[$volumeName]['persistentDisk']['pdSize'];
                $action = 'removed';
                $name = $oldVolumes[$volumeName]['persistentDisk']['pdName'];
            } elseif ($issetNewPorts && !$issetOldPorts) {
                $count = $newVolumes[$volumeName]['persistentDisk']['pdSize'];
                $action = 'added';
                $name = $newVolumes[$volumeName]['persistentDisk']['pdName'];
            } else {
                continue;
            }
            $description = self::UPDATE_KUBES_DESCRIPTION
                . ', storage '
                . $action
                . ' ('
                . $name
                . ')';
            $this->editInvoiceItems[] = $this->product->createInvoice($description, $psPrice, $unit, $count);
        }
    }

    private static function sortVolumes($array)
    {
        $volumes = isset($array)
            ? \base\CL_Tools::getKeyAsField($array, 'name')
            : array();

        return array_filter($volumes, function($item) {
            return $item['persistentDisk'];
        });
    }

    /**
     * @param string $id
     * @return $this
     */
    public function loadById($id)
    {
        $this->_values = $this->api->getPod($id);
        $kubes = \base\CL_Tools::getKeyAsField($this->kubes, 'kuber_kube_id');
        $this->kube = $kubes[$this->kube_type];

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function loadByParams($data)
    {
        $this->_values = $data;

        $kubes = \base\CL_Tools::getKeyAsField($this->kubes, 'kuber_kube_id');
        $this->kube = $kubes[$this->kube_type];

        return $this;
    }
}
<?php

namespace models\addon\billing;


use components\BillingApi;
use components\Component;
use components\InvoiceItemCollection;
use components\Tools;
use components\Units;
use exceptions\CException;
use exceptions\NotEnoughFundsException;
use exceptions\NotFoundException;
use models\addon\App;
use models\addon\Item;
use models\addon\ItemInvoice;
use models\addon\Resources;
use models\addon\resource\Pod;
use models\addon\resource\ResourceFactory;
use models\billing\BillableItem;
use models\billing\Client;
use models\billing\Config;
use models\billing\Invoice;
use models\billing\Service;

class Fixed extends Component implements BillingInterface
{
    /**
     * @var float
     */
    protected $proRate = 1.0;

    /**
     * @param ResourceFactory $resource
     * @param Service $service
     * @return Invoice
     */
    public function order(ResourceFactory $resource, Service $service)
    {
        if (!($resource instanceof Pod)) {
            $resource = $resource->setService($service)->create();
        }

        $invoiceItems = $resource->getInvoiceItems();
        $invoiceItems->filterPaidResources($service);
        $item = $this->createBillableItem($invoiceItems, $service, Resources::TYPE_POD, $resource);

        // On 1st service order
        $paidItemInvoice = $item->invoices()->paid()->first();
        if ($service->moduleCreate && $paidItemInvoice) {
            $this->afterOrderPayment($item);

            return $paidItemInvoice->invoice;
        }

        $invoice = $item->invoices()->unpaid()->first()->invoice;

        return $invoice;
    }

    /**
     * @param Item $item
     * @return Pod
     */
    public function afterOrderPayment(Item $item)
    {
        $pod = new Pod($item->service->package);

        $pod->setService($item->service);
        $pod->loadById($item->pod_id);

        try {
            $pod->start(true);
        } catch (\Exception $e) {
            CException::log($e);
        }

        Resources::add($pod, $item);

        return $pod;
    }

    /**
     * @param Item $item
     * @return Pod
     */
    public function afterEditPayment(Item $item)
    {
        $pod = new Pod($item->service->package);
        $pod->setService($item->service);

        try {
            $response = $item->service->getAdminApi()->applyEdit($item->pod_id)->getData();
            $pod->setAttributes($response['edited_config']);
        } catch (\Exception $e) {
            CException::log($e);
        }

        $item->billableItem->amount = $pod->getPrice();
        $item->billableItem->save();

        return $pod;
    }

    /**
     * @param Item $item
     * @return Pod
     */
    public function afterSwitchPayment(Item $item)
    {
        $pod = new Pod($item->service->package);
        $pod->setService($item->service);

        try {
            $params = $item->invoices()->orderBy('id', 'desc')->first()->params;
            $item->service->getAdminApi()->switchPodPlan($item->pod_id, $params->plan);
            $pod->loadById($item->pod_id);
        } catch (\Exception $e) {
            CException::log($e);
        }

        $item->billableItem->amount = $pod->getPrice();
        $item->billableItem->save();

        return $pod;
    }

    /**
     * @param Service $service
     */
    public function afterModuleCreate(Service $service)
    {
        // Not used
    }

    /**
     * @param Pod $pod
     * @param Service $service
     * @param string $type
     * @return array
     * @throws CException
     */
    public function processApiOrder(Pod $pod, Service $service, $type)
    {
        $item = Item::where('pod_id', $pod->id)
            ->where('status', '!=', Resources::STATUS_DELETED)
            ->where('type', Resources::TYPE_POD)
            ->where('user_id', $service->userid)
            ->first();

        if ($item) {
            $itemInvoice = $item->invoices()->unpaid()->first();

            if ($itemInvoice) {
                $invoice = $itemInvoice->invoice;
            } else {
                $actionMethod = 'processType' . strtolower(ucfirst($type));

                if (!method_exists($this, $actionMethod)) {
                    throw new CException('Unknown api action method');
                }

                $invoice = call_user_func([$this, $actionMethod], $pod, $item, $service);
            }
        } else {
            $invoice = $this->order($pod, $service);
        }

        try {
            $invoice = BillingApi::model()->applyCredit($invoice);
        } catch (NotEnoughFundsException $e) {
            //
        }

        $result = [
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
        ];

        if ($invoice->isUnpaid()) {
            $result['redirect'] = BillingApi::generateAutoAuthLink($invoice->getUrl(), $service->client);
        }

        return $result;
    }

    /**
     * Cron job
     */
    public function processCron()
    {
        $config = Config::get();

        // Process deleted pods
        $this->processDeletedPods();

        // Process unpaid items
        $unpaidItems = Item::select('KuberDock_items.*')
            ->join('tblhosting', 'tblhosting.id', '=', 'KuberDock_items.service_id')
            ->join('KuberDock_item_invoices', 'KuberDock_item_invoices.item_id', '=', 'KuberDock_items.id')
            ->join('tblinvoices', 'tblinvoices.id', '=', 'KuberDock_item_invoices.invoice_id')
            ->where('tblhosting.domainstatus', 'Active')
            ->where('KuberDock_items.billable_item_id', '>', 0)
            ->where('KuberDock_items.status', '!=', Resources::STATUS_DELETED)
            ->where('KuberDock_item_invoices.status', Invoice::STATUS_UNPAID)
            ->whereRaw('DATE(DATE_ADD(tblinvoices.duedate, INTERVAL ? DAY)) <= CURRENT_DATE()', [
                $config->AutoSuspensionDays,
            ])
            ->groupBy('KuberDock_items.id')
            ->get();

        foreach ($unpaidItems as $item) {
            // Free resources
            switch ($item->type) {
                case Resources::TYPE_POD:
                    // TODO: remove PD\IP
                    try {
                        $item->service->getApi()->stopPod($item->pod_id);
                    } catch (NotFoundException $e) {
                        continue;
                    } catch (\Exception $e) {
                        CException::log($e);
                    }

                    $item->service->getAdminApi()->updatePod($item->pod_id, [
                        'status' => 'unpaid',
                    ]);
                    break;
                case Resources::TYPE_PD:
                case Resources::TYPE_IP:
                    $resource = $item->resourcePods()->whereNull('pod_id')->first()->resources;

                    if ($item->type == Resources::TYPE_PD) {
                        $resource->freePD($item);
                    } else {
                        $resource->freeIP($item);
                    }

                    break;
            }

            $item->stopInvoicing();
        }
    }

    /**
     * @param InvoiceItemCollection $invoiceItems
     * @param Service $service
     * @param string $type
     * @param ResourceFactory|null $resource
     * @return Item
     */
    protected function createBillableItem(InvoiceItemCollection $invoiceItems, Service $service,
                                          $type = Resources::TYPE_POD, ResourceFactory $resource = null)
    {
        $price = $invoiceItems->sum();

        $billableItem = new BillableItem();
        $billableItem->setRawAttributes([
            'userid' => $service->userid,
            'description' => $invoiceItems->getDescription(),
            'recurfor' => 0,
            'invoiceaction' => BillableItem::CREATE_RECUR_ID,
            'amount' => $price,
            'invoicecount' => 1,
        ]);
        $billableItem->setRecur($service->package);
        $billableItem->setNextDueDate();
        $billableItem->save();

        $item = new Item();
        $item->setRawAttributes([
            'user_id' => $service->userid,
            'service_id' => $service->id,
            'billable_item_id' => $billableItem->id,
            'type' => $type,
        ]);

        if ($resource) {
            $item->pod_id = $resource->id;
        }

        $item->save();

        // On 1st service order invoice already generated (invoice_item type = Hosting, relid = service_id)
        if ($service->moduleCreate) {
            $invoice = $service->invoiceItem()->first()->invoice;
        } else {
            $client = Client::find($service->userid);
            $invoice = BillingApi::model()->createInvoice($client, $invoiceItems, false);
        }

        $invoice->items()->first()->assignBillableItem($billableItem);

        $itemInvoice = new ItemInvoice([
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
            'type' => ItemInvoice::TYPE_ORDER,
        ]);
        $item->invoices()->save($itemInvoice);

        return $item;
    }

    /**
     * @param Pod $pod
     * @param Item $item
     * @param Service $service
     * @return Invoice
     */
    protected function createEditInvoice(Pod $pod, Item $item, Service $service)
    {
        $attributes = [];
        $type = ItemInvoice::TYPE_EDIT;

        $pod->setService($service);
        $newPod = clone $pod;
        $newPod->setAttributes($pod->edited_config);
        $this->proRate = $item->billableItem->getProRate();

        $invoiceItems = $this->collectEditInvoiceItems($pod, $newPod);
        $invoiceItems->filterPaidResources($service);

        if (isset($newPod->template_plan_name)) {
            $attributes['plan'] = $newPod->template_plan_name;
            $type = ItemInvoice::TYPE_SWITCH;
        }

        if ($invoiceItems->sum() <= 0) {
            if ($type == ItemInvoice::TYPE_SWITCH) {
                try {
                    $item->service->getAdminApi()->switchPodPlan($item->pod_id, $attributes['plan']);
                } catch (\Exception $e) {
                    CException::log($e);
                }

                $item->billableItem->amount = $newPod->getPrice();
                $item->billableItem->save();
            } else {
                $this->afterEditPayment($item);
            }

            $invoice = new Invoice();
            $invoice->status = Invoice::STATUS_PAID;

            return $invoice;
        }

        $invoice = BillingApi::model()->createInvoice($service->client, $invoiceItems, false);
        $invoice->items()->first()->assignBillableItem($item->billableItem);

        $itemInvoice = new ItemInvoice([
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
            'type' => $type,
            'params' => $attributes,
        ]);
        $item->invoices()->save($itemInvoice);

        return $invoice;
    }

    /**
     * @param Pod $old
     * @param Pod $new
     * @return InvoiceItemCollection
     */
    protected function collectEditInvoiceItems(Pod $old, Pod $new)
    {
        $invoiceItems = new InvoiceItemCollection();
        $package = $new->getPackage();

        $packageKubes = $old->getPackage()->getKubes();

        $oldKubeType = $packageKubes[$old->getKubeType()];
        $newKubeType = $packageKubes[$new->getKubeType()];

        $oldKubePrice = $oldKubeType['kube_price'] * $this->proRate;
        $newKubePrice = $newKubeType['kube_price'] * $this->proRate;

        $oldContainers = Tools::model()->keyBy($old->containers, 'name');
        $newContainers = Tools::model()->keyBy($new->containers, 'name');

        $list = array_keys(array_merge($oldContainers, $newContainers));

        $filterPorts = function ($array) {
            return array_filter($array['ports'], function($item) {
                return $item['isPublic'];
            });
        };

        $newPublicIpUsed = false;
        $oldPublicIpUsed = false;

        foreach ($list as $name) {
            // ports
            $oldPublicIpUsed = $oldPublicIpUsed || (bool) count($filterPorts($oldContainers[$name]));
            $newPublicIpUsed = $newPublicIpUsed || (bool) count($filterPorts($newContainers[$name]));

            // kubes
            $newKubesIsset = isset($oldContainers[$name]['kubes']);
            $oldKubesIsset = isset($newContainers[$name]['kubes']);

            if ($newKubesIsset && $oldKubesIsset) {
                $delta = $newContainers[$name]['kubes'] - $oldContainers[$name]['kubes'];

                if ($oldKubeType['id'] != $newKubeType['id']) {
                    $description = sprintf('Change kube type from %s to %s (%s)',
                        $oldKubeType['template']['kube_name'], $newKubeType['template']['kube_name'],
                        $newContainers[$name]['image']
                    );
                    $price = $newContainers[$name]['kubes'] * $newKubePrice
                        - $newContainers[$name]['kubes'] * $oldKubePrice;
                    $invoiceItems->add($package->createInvoiceItem($description, $price, 'kube', 1));
                }

                if ($delta == 0) {
                    continue;
                }

                $action = $delta > 0 ? 'added' : 'removed';
            } elseif (!$newKubesIsset && $oldKubesIsset) {
                $delta = -$oldContainers[$name]['kubes'];
                $action = 'removed';
            } elseif ($newKubesIsset && !$oldKubesIsset) {
                $delta = $newContainers[$name]['kubes'];
                $action = 'added';
            } else {
                continue;
            }

            $description = 'Update resources, '
                . abs($delta)
                . (abs($delta) == 1 ? ' kube ' : ' kubes ')
                . $action
                . ' ('
                . '"' . $newContainers[$name]['image'] . '", '
                . '"' . $name . '"'
                . ')';

            $invoiceItems->add(
                $package->createInvoiceItem($description, $newKubePrice, 'kube', $delta, Resources::TYPE_POD)
            );
        }

        if ($oldPublicIpUsed != $newPublicIpUsed) {
            if ($newPublicIpUsed && !$oldPublicIpUsed) {
                $count = 1;
                $action = 'added';
            } else {
                $count = -1;
                $action = 'removed';
            }
            $description = 'Update resources, public IP ' . $action;
            $ipPrice = $package->getPriceIP() * $this->proRate;
            $invoiceItems->add($package->createInvoiceItem($description, $ipPrice, 'IP', $count, Resources::TYPE_IP));
        }

        // volumes
        $this->addVolumeInvoiceItems($old, $new, $invoiceItems);

        return $invoiceItems;
    }

    /**
     * @param Pod $old
     * @param Pod $new
     * @param InvoiceItemCollection $invoiceItems
     * @return void
     */
    protected function addVolumeInvoiceItems(Pod $old, Pod $new, InvoiceItemCollection &$invoiceItems)
    {
        $oldVolumes = self::sortVolumes($old->volumes);
        $newVolumes = self::sortVolumes($new->volumes);

        $psPrice = $new->getPackage()->getPricePS() * $this->proRate;
        $listVolumes = array_keys(array_merge($oldVolumes, $newVolumes));
        $unit = Units::getPSUnits();

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
            $description = 'Update resources, storage '
                . $action
                . ' ('
                . $name
                . ')';

            $invoiceItems->add(
                $new->getPackage()->createInvoiceItem($description, $psPrice, $unit, $count, Resources::TYPE_PD)
                    ->setName($name)
            );
        }
    }

    /**
     * @param $array
     * @return array
     */
    protected static function sortVolumes($array)
    {
        $volumes = isset($array)
            ? Tools::model()->keyBy($array, 'name')
            : array();

        return array_filter($volumes, function ($item) {
            return $item['persistentDisk'];
        });
    }

    /**
     * @param Pod $pod
     * @param Item $item
     * @param Service $service
     * @return Invoice|void
     */
    private function processTypeOrder(Pod $pod, Item $item, Service $service)
    {
        return $this->order($pod, $service);
    }

    /**
     * @param Pod $pod
     * @param Item $item
     * @param Service $service
     * @return Invoice
     */
    private function processTypeEdit(Pod $pod, Item $item, Service $service)
    {
        return $this->createEditInvoice($pod, $item, $service);
    }

    /**
     * @param Pod $pod
     * @param Item $item
     * @param Service $service
     * @return Invoice
     */
    private function processTypeSwitch(Pod $pod, Item $item, Service $service)
    {
        return $this->createEditInvoice($pod, $item, $service);
    }

    /**
     * Check whether deleted pod or not
     */
    private function processDeletedPods()
    {
        $items = Item::where('status', '!=', Resources::STATUS_DELETED)
            ->where('billable_item_id', '>', 0)
            ->where('type', Resources::TYPE_POD)
            ->get();

        foreach ($items as $item) {
            try {
                $item->service->getApi()->getPod($item->pod_id);
            } catch (NotFoundException $e) {
                $item->stopInvoicing();
            } catch (\Exception $e) {
                CException::log($e);
            }
        }
    }

    /**
     * @param Service $service
     * @return InvoiceItemCollection
     */
    public function firstInvoiceCorrection(Service $service)
    {
        $session = App::getFromSession();

        return (!is_null($session))
            ? $session->getResource()->getInvoiceItems()
            : new InvoiceItemCollection();
    }
}
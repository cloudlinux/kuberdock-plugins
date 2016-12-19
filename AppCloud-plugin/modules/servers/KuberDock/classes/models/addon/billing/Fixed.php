<?php

namespace models\addon\billing;


use Carbon\Carbon;
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
     * @var App
     */
    public $app;
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
        $invoiceItems->setResource($resource);
        $invoiceItems->filterPaidResources($service);
        $item = $this->createBillableItem($invoiceItems, $service, Resources::TYPE_POD, $resource);

        // On 1st service order
        $paidItemInvoice = $item->invoices()->paid()->first();
        if ($service->moduleCreate && $paidItemInvoice) {
            $this->afterOrderPayment($paidItemInvoice);
            return $paidItemInvoice->invoice;
        }

        return $item->invoices()->unpaid()->first()->invoice;
    }

    /**
     * @param ItemInvoice $itemInvoice
     */
    public function beforePayment(ItemInvoice $itemInvoice)
    {
        // Not used
    }

    /**
     * @param ItemInvoice $itemInvoice
     * @return Pod
     */
    public function afterOrderPayment(ItemInvoice $itemInvoice)
    {
        $item = $itemInvoice->item;
        $pod = new Pod($item->service->package);

        $pod->setService($item->service);
        $pod->loadById($item->pod_id);

        try {
            $pod->start(true);
        } catch (\Exception $e) {
            CException::log($e);
        }

        $app = App::notCreated()->where('service_id', $item->service->id)->first();
        if ($app) {
            $app->pod_id = $pod->id;
            $app->save();
        }

        $item->due_date = $item->billableItem->duedate;
        $item->save();

        Resources::add($pod, $item);

        return $pod;
    }

    /**
     * @param ItemInvoice $itemInvoice
     * @return Pod
     */
    public function afterEditPayment(ItemInvoice $itemInvoice)
    {
        $item = $itemInvoice->item;
        $pod = new Pod($item->service->package);
        $pod->setService($item->service);

        try {
            $response = $item->service->getAdminApi()->applyEdit($item->pod_id)->getData();
            $pod->setAttributes($response['edited_config']);
            $item->billableItem->amount = $pod->getPrice();
            $item->billableItem->save();
        } catch (\Exception $e) {
            CException::log($e);
        }

        $pod->loadById($item->pod_id);

        Resources::add($pod, $item);

        return $pod;
    }

    /**
     * @param ItemInvoice $itemInvoice
     * @return Pod
     */
    public function afterSwitchPayment(ItemInvoice $itemInvoice)
    {
        $item = $itemInvoice->item;
        $pod = new Pod($item->service->package);
        $pod->setService($item->service);

        try {
            $params = $itemInvoice->params;
            $item->service->getAdminApi()->switchPodPlan($item->pod_id, $params->plan);
            $pod->loadById($item->pod_id);
        } catch (\Exception $e) {
            CException::log($e);
        }

        $item->billableItem->amount = $pod->getPrice();
        $item->billableItem->save();

        $itemInvoice->deleteUnpaidSiblings();

        return $pod;
    }

    /**
     * @param Service $service
     * @throws \Exception
     */
    public function afterModuleCreate(Service $service)
    {
        $billing = $service->package->getBilling();
        $app = App::notCreated()->where('service_id', $service->id)->first();

        if ($app) {
            $this->app = $app;
            $service->moduleCreate = true;
            $invoice = $billing->order($app->getResource(), $service);

            $item = ItemInvoice::where('invoice_id', $invoice->id)->first()->item;
            $app->pod_id = $item->pod_id;
            $app->save();
            $pod = new Pod($item->service->package);
            $pod->setService($item->service);
            $pod->loadById($item->pod_id);

            if ($invoice->isPaid()) {
                $pod->redirect(true);
            }
        }
    }

    /**
     * @param Pod $pod
     * @param Service $service
     * @param string $type
     * @return array
     * @throws CException
     * @throws \Exception
     */
    public function processApiOrder(Pod $pod, Service $service, $type)
    {
        $pod->setService($service);
        $pod->saveApp();
        $invoice = $this->getInvoice($pod, $service, $type);

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
        $settings = new AutomationSettings();

        // Process deleted pods
        $this->processDeletedPods();
        $this->processDeletedResources();

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
            /* @var Item $item */
            $itemInvoice = $item->invoices()->unpaid()->orderBy('id', 'asc')->first();

            // Free resources
            switch ($item->type) {
                case Resources::TYPE_POD:
                    try {
                        $item->service->getAdminApi()->updatePod($item->pod_id, [
                            'unpaid' => true,
                        ]);

                        if (!$settings->isTerminated($itemInvoice->invoice->duedate)) {
                            break;
                        }

                        foreach ($item->resourcePods as $resourcePod) {
                            if ($resourcePod->resources->isDeleted()) {
                                if ($resourcePod->resources->type === Resources::TYPE_PD) {
                                    $resourcePod->resources->freePD($item);
                                } else {
                                    $resourcePod->resources->freeIP($item);
                                }
                            }
                        }
                    } catch (NotFoundException $e) {
                        break;
                    } catch (\Exception $e) {
                        CException::log($e);
                    }

                    break;
                case Resources::TYPE_PD:
                case Resources::TYPE_IP:
                    $resource = $item->resourcePods()->whereNull('pod_id')->first()->resources;

                    if (!$settings->isTerminated($itemInvoice->invoice->duedate)) {
                        break;
                    }

                    if ($item->type == Resources::TYPE_PD) {
                        $resource->freePD($item);
                    } else {
                        $resource->freeIP($item);
                    }

                    break;
            }
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
        $invoiceItems->setItem($item);
        $invoiceItems->setResource($pod);
        $invoiceItems->filterPaidResources($service);

        if (isset($newPod->template_plan_name)) {
            $attributes['plan'] = $newPod->template_plan_name;
            $type = ItemInvoice::TYPE_SWITCH;
        }

        if ($invoiceItems->sum() <= 0) {
            if ($type === ItemInvoice::TYPE_SWITCH) {
                try {
                    $item->service->getAdminApi()->switchPodPlan($item->pod_id, $attributes['plan']);
                } catch (\Exception $e) {
                    CException::log($e);
                }

                $item->billableItem->amount = $newPod->getPrice();
                $item->billableItem->save();
            } else {
                $itemInvoice = $item->invoices()->paid()->first();
                $this->afterEditPayment($itemInvoice);
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
                    $price = $oldContainers[$name]['kubes'] * ($newKubePrice - $oldKubePrice);
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
    public function processTypeEdit(Pod $pod, Item $item, Service $service)
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
     * Stop invoicing for deleted pods
     *
     * For case when api deletekuberdockpod was not received from KD
     */
    private function processDeletedPods()
    {
        $items = Item::where('status', '!=', Resources::STATUS_DELETED)
            ->where('billable_item_id', '>', 0)
            ->where('type', Resources::TYPE_POD)
            ->get();

        foreach ($items as $item) {
            try {
                /** @var Service $service */
                $service = $item->service;
                if ($service) {
                    $service->getApi()->getPod($item->pod_id);
                }
            } catch (NotFoundException $e) {
                $item->stopInvoicing();
                $item->changeStatus();
            } catch (\Exception $e) {
                CException::log($e);
            }
        }
    }

    /**
     * Clean resources
     */
    private function processDeletedResources()
    {
        $query = Resources::select('r.*')
            ->from('KuberDock_items as i')
            ->join('KuberDock_resource_items as ri', 'ri.item_id', '=', 'i.id')
            ->join('KuberDock_resource_pods as rp', 'rp.id', '=', 'ri.resource_pod_id')
            ->join('KuberDock_resources as r', 'r.id', '=', 'rp.resource_id')
            ->where('i.due_date', '<=', new Carbon())
            ->groupBy('r.id');
        $resources = $query->get();

        foreach ($resources as $resource) {
            if (!$resource->hasPaidItems()) {
                $resource->status = Resources::STATUS_DELETED;
                $resource->save();
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

    /**
     * @param Pod $pod
     * @param Service $service
     * @param $type
     * @return Invoice
     * @throws CException
     */
    private function getInvoice(Pod $pod, Service $service, $type)
    {
        $item = Item::withPod($pod->id)->orderBy('id', 'desc')->first();

        if (!$item) {
            return $this->order($pod, $service);
        }

        $itemInvoices = $item->invoices()->unpaid()->type($type);

        if ($type == ItemInvoice::TYPE_SWITCH) {
            $newPlan = $pod->edited_config['template_plan_name'];
            foreach ($itemInvoices->get() as $i) {
                if (isset($i->params->plan) && $i->params->plan == $newPlan) {
                    $itemInvoice = $i;
                }
            }
        } else {
            $itemInvoice = $itemInvoices->first();
        }

        if (isset($itemInvoice) && $itemInvoice) {
            return $itemInvoice->invoice;
        }

        $actionMethod = 'processType' . strtolower(ucfirst($type));

        if (!method_exists($this, $actionMethod)) {
            throw new CException('Unknown api action method');
        }

        return call_user_func([$this, $actionMethod], $pod, $item, $service);
    }
}
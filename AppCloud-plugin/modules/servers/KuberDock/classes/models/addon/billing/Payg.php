<?php

namespace models\addon\billing;


use Carbon\Carbon;
use components\BillingApi;
use components\Component;
use components\InvoiceItemCollection;
use components\Units;
use exceptions\CException;
use exceptions\NotEnoughFundsException;
use models\addon\App;
use models\addon\Item;
use models\addon\ItemInvoice;
use models\addon\Resources;
use models\addon\resource\Pod;
use models\addon\resource\ResourceFactory;
use models\addon\State;
use models\billing\EmailTemplate;
use models\billing\Invoice;
use models\billing\InvoiceItem;
use models\billing\Package;
use models\billing\Service;
use models\Model;

class Payg extends Component implements BillingInterface
{
    /**
     * @var App
     */
    protected $app;

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

        try {
            $resource->start(true);
        } catch (\Exception $e) {
            CException::log($e);
        }

        if ($this->app) {
            $this->app->pod_id = $resource->id;
            $this->app->save();
        }

        $resource->redirect(true);
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
        try {
            BillingApi::unSuspendModule($item->service);
        } catch (\Exception $e) {
            CException::log($e);
        }
    }

    /**
     * @param ItemInvoice $itemInvoice
     * @return Pod
     */
    public function afterEditPayment(ItemInvoice $itemInvoice)
    {
        // Not used
    }

    /**
     * @param Item $item
     * @return Pod
     */
    public function afterSwitchPayment(ItemInvoice $itemInvoice)
    {
        // Not used
    }

    /**
     * @param Service $service
     */
    public function afterModuleCreate(Service $service)
    {
        Item::firstOrCreate([
            'pod_id' => '',
            'user_id' => $service->userid,
            'service_id' => $service->id,
            'status' => Resources::STATUS_ACTIVE,
            'type' => Resources::TYPE_POD,
        ]);

        $billing = $service->package->getBilling();
        $app = App::notCreated()->where('service_id', $service->id)->first();

        if ($app) {
            $this->app = $app;
            $billing->order($app->getResource(), $service);
        }
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
        // Not used
    }

    /**
     * @throws \Exception
     */
    public function processCron()
    {
        Model::getConnectionResolver()->enableQueryLog();
        $date = new \DateTime();
        $date->setTime(0, 0, 0);

        $services = Service::typeKuberDock()->with('item')
            ->where('domainstatus', 'Active')
            ->whereHas('package', function ($query) {
                $query->where('configoption9', 'PAYG');
            })
            ->whereHas('item', function ($query) {
                $query->where('status', Resources::STATUS_ACTIVE);
            })
            ->get();

        foreach ($services as $service) {
            try {
                $package = $service->package;
                /* @var Package $package */

                if ($package->getEnableTrial()) {
                    $this->processTrial($service);
                    continue;
                }

                // override auto suspend
                if ($service->overideautosuspend && $date < $service->overidesuspenduntil) {
                    continue;
                }

                if ((strtotime($service->nextduedate) > 0) && $date > $service->nextduedate) {
                    continue;
                }

                if ($package->getPaymentType() == 'hourly') {
                    $invoiceItems = $this->getHourlyUsage($service);
                } else {
                    $invoiceItems = $this->getPeriodicUsage($service);
                }

                if ($invoiceItems->sum() <= 0) {
                    continue;
                }

                $invoice = BillingApi::model()->createInvoice($service->client, $invoiceItems, false);

                $itemInvoice = new ItemInvoice([
                    'invoice_id' => $invoice->id,
                    'status' => $invoice->status,
                    'type' => ItemInvoice::TYPE_ORDER,
                ]);
                $service->item->invoices()->save($itemInvoice);

                BillingApi::model()->applyCredit($invoice);
            } catch (NotEnoughFundsException $e) {
                $this->suspendItem($itemInvoice);
            } catch (\Exception $e) {
                CException::log($e);
            }
        }

        $this->suspendAll();
    }

    /**
     * @param Service $service
     * @throws \Exception
     */
    protected function processTrial(Service $service)
    {
        /* @var Package $package */
        $settings = new AutomationSettings();
        $package = $service->package;

        $now = new \DateTime();
        $now->setTime(0, 0, 0);
        $expireDate = clone $service->regdate;
        $expireDate->modify('+' . $package->getTrialTime() . ' day');

        if ($now >= $expireDate) {
            if ($expireDate == $now) {
                if ($package->getSendTrialExpire()) {
                    BillingApi::model()->sendPreDefinedEmail($this->id, EmailTemplate::TRIAL_EXPIRED_NAME, [
                        'trial_end_date' => $expireDate->format('Y-m-d'),
                    ]);
                }

                // If suspend module then user can't change product via client area
                $service->getAdminApi()->updateUser(['suspended' => true], $service->username);
            } else if ($settings->isSuspended($expireDate)) {
                $resources = new Resources();
                $resources->freeAll($service);
            };
        } else {
            $trialNoticeEvery = $package->getTrialNoticeEvery() != ''
                ? $package->getTrialNoticeEvery() : 7;

            if ($trialNoticeEvery != 0 && ($service->regdate->diff($now)->days % $trialNoticeEvery == 0)) {
                BillingApi::model()->sendPreDefinedEmail($this->id, EmailTemplate::TRIAL_NOTICE_NAME, [
                    'trial_end_date' => $expireDate->format('Y-m-d'),
                ]);
            }
        }
    }

    /**
     * @param ItemInvoice $itemInvoice
     * @throws \Exception
     */
    protected function suspendItem(ItemInvoice $itemInvoice)
    {
        $settings = new AutomationSettings();

        if (!$settings->isSuspendEnabled()) {
            return;
        }

        $item = $itemInvoice->item;

        if ($item->service->package->getPaymentType() == 'hourly') {
            $item->suspend();
        } else {
            $hasPaidInvoices = $item->invoices()->paid()->count();

            if (!$hasPaidInvoices || $settings->isSuspended($itemInvoice->invoice->duedate)) {
                $item->suspend();
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function suspendAll()
    {
        $settings = new AutomationSettings();
        $now = new \DateTime();
        $now->setTime(0, 0, 0);

        $items = Item::select('KuberDock_items.*')
            ->join('KuberDock_item_invoices', 'KuberDock_item_invoices.item_id', '=', 'KuberDock_items.id')
            ->join('tblinvoices', 'tblinvoices.id', '=', 'KuberDock_item_invoices.invoice_id')
            ->join('tblhosting', 'tblhosting.id', '=', 'KuberDock_items.service_id')
            ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
            ->whereNull('KuberDock_items.billable_item_id')
            ->whereIn('KuberDock_items.status', [Resources::STATUS_ACTIVE, Resources::STATUS_SUSPENDED])
            ->where('KuberDock_item_invoices.status', Invoice::STATUS_UNPAID)
            ->where('tblinvoices.duedate', '<', $now)
            ->where('tblproducts.configoption3', '!=', 'hourly')
            ->get();

        foreach ($items as $item) {
            $hasPaidInvoices = $item->invoices()->paid()->count();
            $invoice = $item->invoices->last()->invoice;

            if ((!$hasPaidInvoices && $settings->isSuspendEnabled()) || $settings->isSuspended($invoice->duedate)) {
                $item->suspend();
            }

            if (!$settings->isTerminateEnabled()) {
                continue;
            }

            if ($settings->isTerminateNotice($invoice->duedate)) {
                BillingApi::model()->sendPreDefinedEmail($item->service->id,
                    EmailTemplate::RESOURCES_NOTICE_NAME, [
                        'expire_date' => $now->format('Y-m-d'),
                    ]);
            }

            if ($settings->isTerminated($invoice->duedate)) {
                $resources = new Resources();
                $resources->freeAll($item->service);

                BillingApi::model()->sendPreDefinedEmail($item->service->id,
                    EmailTemplate::RESOURCES_NOTICE_NAME, [
                        'expire_date' => $now->format('Y-m-d'),
                    ]);
                $item->update([
                    'status' => Resources::STATUS_DELETED,
                ]);
            }
        }
    }

    /**
     * @param Service $service
     * @return InvoiceItemCollection
     */
    protected function getHourlyUsage(Service $service)
    {
        /* @var Package $package */
        $package = $service->package;
        $kubes = $package->getKubes();

        $now = new \DateTime();
        $invoiceItems = new InvoiceItemCollection();

        $dateEnd = clone $now;
        $dateStart = $now->modify('-1 day');
        $dateStart->setTime(0, 0, 0);
        $dateEnd->setTime(0, 0, 0);
        $timeStart = $dateStart->getTimestamp();
        $timeEnd = $dateEnd->getTimestamp();

        $usage = $service->getAdminApi()->getUsage($service->username, $dateStart, $dateEnd)->getData();

        foreach ($usage['pods_usage'] as $pod) {
            if (empty($pod['time'])) {
                continue;
            }

            $usageHours = [];

            foreach ($pod['time'] as $container) {
                foreach ($container as $period) {
                    $start = $period['start'];
                    $end = $period['end'];

                    if (!$this->getUsageHoursFromPeriod($start, $end, $timeStart, $timeEnd, $usageHours)) {
                        continue;
                    }
                }
            }

            $price = $kubes[$pod['kube_id']]['kube_price'] * $pod['kubes'];
            $invoiceItems->add(
                $package->createInvoiceItem('', $price, 'hour', count($usageHours))->setName($pod['name'])
            );
        }

        foreach ($usage['ip_usage'] as $data) {
            $usageHours = [];

            if (!$this->getUsageHoursFromPeriod($data['start'], $data['end'], $timeStart, $timeEnd, $usageHours)) {
                continue;
            }

            $price = $package->getPriceIP();
            $invoiceItems->add(
                $package->createInvoiceItem('', $price, 'hour',  count($usageHours),
                    Resources::TYPE_IP)->setName($data['ip_address'])
            );
        }

        foreach ($usage['pd_usage'] as $data) {
            $usageHours = [];

            if (!$this->getUsageHoursFromPeriod($data['start'], $data['end'], $timeStart, $timeEnd, $usageHours)) {
                continue;
            }

            $price = $package->getPricePS() * $data['size'];
            $invoiceItems->add(
                $package->createInvoiceItem('', $price, 'hour', count($usageHours), Resources::TYPE_PD)
                    ->setName($data['pd_name'])
            );
        }

        $service->nextduedate = (new \DateTime())->modify($package->getNextShift());
        $service->save();

        return $invoiceItems;
    }


    /**
     * @param Service $service
     * @return InvoiceItemCollection
     * @throws \Exception
     */
    protected function getPeriodicUsage(Service $service)
    {
        /* @var Package $package
         * @var Item $item
         */
        $settings = new AutomationSettings();
        $package = $service->package;
        $kubes = $package->getKubes();

        $now = new \DateTime();
        $now->setTime(0, 0, 0);

        if ((strtotime($service->nextduedate) <= 0)) {
            $dateStart = clone $service->regdate;
            $dateEnd = clone $service->regdate;
            $dateEnd->modify($package->getNextShift());
            $service->nextduedate = $service->regdate->modify($package->getNextShift());
            $service->save();
        } else {
            if ($now == $service->nextduedate) {
                $service->nextduedate = $service->nextduedate->modify($package->getNextShift());
                $service->save();
            }

            $dateStart = clone $service->nextduedate;
            $dateStart->modify($package->getNextShift(false));
            $dateEnd = clone $service->nextduedate;
        }

        $response = $service->getAdminApi()->getUsage($service->username, $dateStart, $dateEnd);
        $usage = $response->getData();

        $items = new InvoiceItemCollection();

        $podsUsage = [];
        $totalKubeCount = 0;

        foreach ($usage['pods_usage'] as $pod) {
            $podsUsage[$pod['kube_id']] += $pod['kubes'];
        }

        if ($podsUsage) {
            foreach ($podsUsage as $kubeId => $kubeCount) {
                $price = $kubes[$kubeId]['kube_price'];
                $totalKubeCount += $kubeCount;
                $items->add(
                    $package->createInvoiceItem('Kubes "' . $kubes[$kubeId]['template']['kube_name'] . '"',
                        $price, 'Pod', $kubeCount, Resources::TYPE_POD)
                );
            }
        }

        $totalIPs = [];

        foreach ($usage['ip_usage'] as $data) {
            if (!in_array($data['ip_address'], $totalIPs)) {
                $totalIPs[] = $data['ip_address'];
            }
        }

        if ($totalIPs) {
            $price = $package->getPriceIP();
            $items->add($package->createInvoiceItem('', $price, 'IP', count($totalIPs), Resources::TYPE_IP));
        }

        $totalPd = [];

        foreach ($usage['pd_usage'] as $data) {
            if (!isset($totalPd[$data['pd_name']]) || $totalPd[$data['pd_name']] < $data['size']) {
                $totalPd[$data['pd_name']] = $data['size'];
            }
        }

        $totalPdSize = array_sum($totalPd);

        if ($totalPdSize) {
            $price = $package->getPricePS();
            $unit = Units::getPSUnits();
            $items->add($package->createInvoiceItem('', $price, $unit, $totalPdSize, Resources::TYPE_PD));
        }

        $lastState = State::where('hosting_id', $service->id)
            ->whereBetween('checkin_date', [$dateStart, $dateEnd])
            ->orderBy('id', 'desc')
            ->first();

        if ($settings->isSuspendNotice($service->nextduedate)) {
            BillingApi::model()->sendPreDefinedEmail($service->id, EmailTemplate::INVOICE_REMINDER_NAME, [
                'invoice_date' => $service->nextduedate->format('Y-m-d'),
                'amount' => $service->client->currencyModel->getFullPrice($items->sum()),
            ]);
        }
        
        $originItems = clone $items;

        if ($lastState && $now < $service->nextduedate) {
            $items->filterPaidState($lastState);

            if ($items->sum() && $lastState->checkin_date != $now) {
                $period = $dateEnd->diffInDays($dateStart);
                $periodRemained = $dateEnd->diffInDays(new Carbon());

                foreach ($items as $item) {
                    $item->proratePrice($periodRemained / $period);
                }
            }
        }

        if ($items->sum()) {
            $states = new State();
            $states->setRawAttributes([
                'hosting_id' => $service->id,
                'product_id' => $package->id,
                'checkin_date' => $now,
                'kube_count' => $totalKubeCount,
                'ps_size' => $totalPdSize,
                'ip_count' => count($totalIPs),
                'total_sum' => $originItems->sum(),
            ]);
            $states->details = $originItems;
            $states->save();
        }

        return $items;
    }

    /**
     * @param int $timeStart
     * @param int $timeEnd
     * @param int $periodStart
     * @param int $periodEnd
     * @param array $usagePeriod
     * @return array
     */
    private function getUsageHoursFromPeriod($timeStart, $timeEnd, $periodStart, $periodEnd, &$usagePeriod = [])
    {
        if ($timeStart <= $periodStart) {
            $timeStart = $periodStart;
        }

        if ($timeEnd >= $periodEnd) {
            $timeEnd = $periodEnd;
        }

        if ($timeStart > $periodStart && $periodEnd < $timeEnd) {
            return [];
        }

        for ($i = $timeStart; $i <= $timeEnd; $i += 3600) {
            $hour = date('H', $i);
            if (!in_array($hour, $usagePeriod)) {
                $usagePeriod[] = date('H', $i);
            }
        }

        return $usagePeriod;
    }

    /**
     * @param Service $service
     * @return InvoiceItemCollection
     */
    public function firstInvoiceCorrection(Service $service)
    {
        /* @var Package $package */
        $package = $service->package;

        $invoiceItems = new InvoiceItemCollection();

        if (($firstDeposit = $package->getFirstDeposit())) {
            $invoiceItems->add($package->createInvoiceItem('First deposit', $firstDeposit)->setTaxed(false));
        }

        return $invoiceItems;
    }
}
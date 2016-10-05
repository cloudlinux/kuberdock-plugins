<?php

namespace models\addon\billingTypes;


use components\BillingApi;
use components\Component;
use components\Tools;
use components\Units;
use models\addon\App;
use models\addon\billingTypes\BillingInterface;
use models\addon\Item;
use models\addon\ItemInvoice;
use models\addon\KubePrice;
use models\addon\Resources;
use models\addon\State;
use models\billing\Client;
use models\billing\Invoice;
use models\billing\Package;
use models\billing\Service;

class Payg extends Component implements BillingInterface
{

    /**
     * @param App $app
     * @param Service $service
     * @param Client $client
     */
    public function orderPA(App $app, Service $service, Client $client)
    {
        $item = Item::typePayg($service->id)->first();
        if (!$item) {
            Item::create([
                'user_id' => $client->id,
                'service_id' => $service->id,
                'billing_type' => $app->package->getBillingType(),
            ]);
        }

        try {
            $pod = $app->getResource()->create($service->getApi());
            $pod->start();
            $pod->redirect();
        } catch (\Exception $e) {
            $app->redirectWithError($e->getMessage());
        }
    }

    /**
     * @param \stdClass $pod
     * @param Service $service
     * @param Client $client
     * @param $referer
     * @return array
     */
    public function orderPod($pod, Service $service, Client $client, $referer)
    {
        return [
            'invoice_id' => 0,
            'status' => Invoice::STATUS_PAID,
        ];
    }

    /**
     *
     */
    public function processCron()
    {
        $services = Service::typeKuberDockPayg()->get();

        foreach ($services as $service) {
            $package = $service->package();

            $item = $service->item()->where('billing_type', 'payg')->first();
            if (!$item) {
                $item = new Item();
                $item->setRawAttributes([
                    'user_id' => $service->client_id,
                    'service_id' => $service->id,
                    'type' => Resources::TYPE_POD,
                    'billing_type' => 'payg',
                    'status' => Item::STATUS_ACTIVE,
                ]);
                $item->save();
            }

            if ($package->getPaymentType() == 'hour') {
                $invoiceItems = $this->getHourlyUsage($service, $package);

                $nextDueDate = (new \DateTime())->modify('+1 day');
                $item->due_date = $nextDueDate;
                $item->save();

                if ($invoiceItems) {
                    $invoice = BillingApi::model()
                        ->createInvoice($invoiceItems, $service->id, $nextDueDate, $service->client);
                    $service->invoices()->save(new ItemInvoice([
                        'invoice_id' => $invoice->id,
                        'status' => $invoice->getStatus(),
                    ]));
                }
            } else {
                $this->getPeriodicUsage($item, $service, $package);

                if ($invoiceItems) {
                    $invoice = BillingApi::model()
                        ->createInvoice($invoiceItems, $service->id, new \DateTime($item->due_date), $service->client);
                    $service->invoices()->save(new ItemInvoice([
                        'invoice_id' => $invoice->id,
                        'status' => $invoice->getStatus(),
                    ]));
                }
            }
        }
    }

    /**
     * @param Service $service
     * @param Package $package
     * @return array
     */
    private function getHourlyUsage(Service $service, Package $package)
    {
        $kubes = KubePrice::with('template')->where('product_id', $package->id)
            ->get()->keyBy('template.kuber_kube_id')->toArray();

        $now = new \DateTime();
        $api = $service->getAdminApi();

        $dateEnd = clone $now;
        $dateStart = $now->modify('-1 day');
        $dateStart->setTime(0, 0, 0);
        $dateEnd->setTime(0, 0, 0);
        $timeStart = $dateStart->getTimestamp();
        $timeEnd = $dateEnd->getTimestamp();

        $response = $api->getUsage($service->getApi()->getUsername(), $dateStart, $dateEnd);
        $usage = $response->getData();

        $items = [];

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
            $items[] = $package->createInvoiceItem('Pod: ' . $pod['name'], $price, 'hour', count($usageHours));
        }

        foreach ($usage['ip_usage'] as $data) {
            $usageHours = [];

            if (!$this->getUsageHoursFromPeriod($data['start'], $data['end'], $timeStart, $timeEnd, $usageHours)) {
                continue;
            }

            $price = $package->getPriceIP();
            $items[] = $package->createInvoiceItem('IP: ' . $data['ip_address'], $price, 'hour', count($usageHours),
                Resources::TYPE_IP);
        }

        foreach ($usage['pd_usage'] as $data) {
            $usageHours = [];

            if (!$this->getUsageHoursFromPeriod($data['start'], $data['end'], $timeStart, $timeEnd, $usageHours)) {
                continue;
            }

            $price = $package->getPricePS() * $data['size'];
            $items[] = $package->createInvoiceItem('Storage: ' . $data['pd_name'], $price, 'hour', count($usageHours),
                Resources::TYPE_PD);
        }

        return $items;
    }

    /**
     * @param Item $item
     * @param Service $service
     * @param Package $package
     * @return array
     */
    public function getPeriodicUsage(Item &$item, Service $service, Package $package)
    {
        $kubes = KubePrice::with('template')->where('product_id', $package->id)
            ->get()->keyBy('template.kuber_kube_id')->toArray();
        $api = $service->getAdminApi();
        $now = new \DateTime();

        switch ($package->getPaymentType()) {
            case 'monthly':
                $offset = '1 month';
                break;
            case 'quarterly':
                $offset = '3 month';
                break;
            case 'annually':
                $offset = '1 year';
                break;
        }

        if (is_null($item->due_date)) {
            $dateStart = (new \DateTime())->modify('-1 day');
            $dateEnd = new \DateTime();
        } else {
            $dateStart = new \DateTime();
            $dateEnd = new \DateTime($item->due_date);
        }

        $response = $api->getUsage($service->getApi()->getUsername(), $dateStart, $dateEnd);
        $usage = $response->getData();

        $items = [];
        $allPods = [];
        $totalKubeCount = 0;

        foreach ($usage['pods_usage'] as $pod) {
            $title = preg_replace('/__[a-z0-9]+/i', '', $pod['name']);
            if (!in_array($title, $allPods)) {
                $allPods[] = $title;
                $totalKubeCount += $pod['kubes'];

                $price = $kubes[$pod['kube_id']]['kube_price'];
                $items[] = $package->createInvoiceItem('Pod: ' . $title, $price, 'pod', $pod['kubes'],
                    Resources::TYPE_POD);
            }
        }

        $totalIPs = [];
        foreach ($usage['ip_usage'] as $data) {
            if (!in_array($data['ip_address'], $totalIPs)) {
                $totalIPs[] = $data['ip_address'];
                $price = $package->getPriceIP();
                $items[] = $package->createInvoiceItem('IP: ' . $data['ip_address'], 'IP', $price, Resources::TYPE_IP);
            }
        }

        $totalPdSize = 0;
        $invoicedPd = [];
        foreach ($usage['pd_usage'] as $data) {
            if (in_array($data['pd_name'], $invoicedPd)) {
                continue;
            }
            $invoicedPd[] = $data['pd_name'];
            $totalPdSize += $data['size'];
            $price = $package->getPricePS();
            $unit = Units::getPSUnits();
            $items[] = $package->createInvoiceItem('Storage: ' . $data['pd_name'], $price, $unit, $data['size'],
                Resources::TYPE_PD);
        }

        // Предыдущая оплата в этом периоде
        $from = clone $dateStart;
        $to = clone $dateEnd;
        $lastState = State::where('hosting_id', $service->id)
            ->whereRaw('checkin_date BETWEEN CAST(\'?\' AS DATE) AND CAST(\'?\' AS DATE)', [
                $from->modify('Y-m-d'), $to->modify('Y-m-d')]
            )
            ->first();

        // еще не оплачивалось или уже оплачивалось, но после этого добавились кубы
        if (!$lastState || $totalKubeCount > $lastState->kube_count) {
            // Если оплачиваем дополнительные кубы, убираем из списка уже оплаченные
            if ($lastState) {
                $paidItems = json_decode($lastState->details, true);
                foreach ($paidItems as $paidItem) {
                    $items = array_filter($items, function($item) use ($paidItem) {
                        return !($item['title'] == $paidItem['title'] && $item['type'] == $paidItem['type']);
                    });
                }
            }

            // Если оплачиваем дополнительные кубы и дата оплаты еще не наступила
            $dueDate = new \DateTime($service->due_date);

            if ($lastState && !is_null($service->due_date) && $now < $dueDate) {
                // $checkInDate сегодня, оплачивать ничего не надо

                $checkInDate = new \DateTime($lastState->checkin_date);

                if ($dueDate->format('Y-m-d') == $now->format('Y-m-d')) return [];

                // оплачиваем только остаток периода
                $period = Tools::model()->getIntervalDiff($dateStart, $dateEnd);
                $periodRemained = Tools::model()->getIntervalDiff($checkInDate, $dueDate);

                $items = array_map(function ($item) use ($period, $periodRemained) {
                    $item['total'] = round($item['total'] / $period * $periodRemained, 2);
                    return $item;
                }, $items);
            } elseif (is_null($service->due_date) || $now->format('Y-m-d') == $dueDate->format('Y-m-d')) {
                // устанавливаем новый день оплаты
                $item->due_date = (new \DateTime())->modify('+' . $offset);
                $item->save();
            }

            $totalPrice = array_reduce($items, function ($carry, $item) {
                $carry += $item->getTotal();
                return $carry;
            });

            $states = new State();
            $states->setRawAttributes([
                'hosting_id' => $service->id,
                'product_id' => $package->id,
                'checkin_date' => $now,
                'kube_count' => $totalKubeCount,
                'ps_size' => $totalPdSize,
                'ip_count' => count($totalIPs),
                'total_sum' => $totalPrice,
                'details' => json_encode($items),
            ]);
            $states->save();
        }

        return $items;
    }

    /**
     * @param timestamp $timeStart
     * @param timestamp $timeEnd
     * @param timestamp $periodStart
     * @param timestamp $periodEnd
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

}
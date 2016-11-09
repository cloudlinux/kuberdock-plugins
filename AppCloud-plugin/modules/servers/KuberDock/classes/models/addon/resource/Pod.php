<?php


namespace models\addon\resource;


use api\Api;
use api\ApiResponse;
use components\Component;
use components\InvoiceItemCollection;
use components\Units;
use models\addon\KubeTemplate;
use models\addon\Resources;
use models\billing\Package;
use models\billing\Server;
use models\billing\Service;

class Pod extends ResourceFactory
{
    /**
     * @param $data
     * @return $this
     */
    public function load($data)
    {
        $this->setAttributes(json_decode($data, true));

        return $this;
    }

    /**
     * Load pod by id
     * @param string $id
     * @return $this
     */
    public function loadById($id)
    {
        $this->setAttributes($this->service->getApi()->getPod($id));

        return $this;
    }

    /**
     * @return InvoiceItemCollection
     */
    public function getInvoiceItems()
    {
        $items = new InvoiceItemCollection();
        $hasPublicIP = false;

        $kubeType = $this->getKubeType();
        $packageId = $this->package->id;
        $kube = KubeTemplate::where('kuber_kube_id', $kubeType)->with([
            'kubePrice' => function ($query) use ($packageId) {
                $query->where('product_id', $packageId);
            }
        ])->first();
        $kubePrice = $kube->kubePrice()->where('product_id', $packageId)->first()->kube_price;

        foreach ($this->containers as $row) {
            if (isset($row['kubes'])) {
                $description = 'Pod: ' . $this->name . ' (' . $row['image'] . ')';
                $items->add(
                    $this->package->createInvoiceItem($description, $kubePrice, 'pod', $row['kubes'])
                );
            }

            if (isset($row['ports']) && !isset($this->domain)) {
                foreach ($row['ports'] as $port) {
                    if (isset($port['isPublic']) && $port['isPublic'] && !$hasPublicIP) {
                        $hasPublicIP = true;
                        $ipPrice = $this->package->getPriceIP();
                        $items->add(
                            $this->package->createInvoiceItem('', $ipPrice, Units::getIPUnits(), 1, Resources::TYPE_IP)
                        );
                    }
                }
            }
        }

        if (isset($this->volumes)) {
            foreach ($this->volumes as $row) {
                if (isset($row['persistentDisk']['pdSize'])) {
                    $psPrice = $this->package->getPricePS();
                    $items->add(
                        $this->package->createInvoiceItem('', $psPrice, Units::getPSUnits(),
                            $row['persistentDisk']['pdSize'], Resources::TYPE_PD)
                                ->setName($row['persistentDisk']['pdName'])
                    );
                }
            }
        }

        $items->setDescription($this->package->name . ' - Pod ' . $this->getName());

        return $items;
    }

    /**
     * @param bool $unpaid
     * @return ApiResponse
     */
    public function start($unpaid = false)
    {
        if ($unpaid) {
            $this->service->getAdminApi()->updatePod($this->id, [
                'unpaid' => false,
            ]);
        }

        $response = $this->service->getApi()->startPod($this->id);
        $this->setAttributes($response->getData());
    }

    /**
     * @return array
     */
    public function getPersistentDisk()
    {
        if (isset($this->values['volumes'])) {
            return array_map(function($e) {
                if (isset($e['persistentDisk'])) {
                    return $e['persistentDisk'];
                }
            }, $this->values['volumes']);
        }

        return array();
    }

    /**
     * @return string
     */
    public function getPublicIP()
    {
        return isset($this->public_ip) ? $this->public_ip : '';
    }

    /**
     * @return mixed
     */
    public function getKubeType()
    {
        return $this->kube_type;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPackageName()
    {
        return $this->getName();
    }

    public function create()
    {
        // TODO: Implement create() method.
    }

    /**
     * @param bool $js
     */
    public function redirect($js = true)
    {
        global $whmcs;
        if ($whmcs && !$whmcs->isClientAreaRequest()) {
            return;
        }

        $token = $this->service->getApi()->getJWTToken();
        $url = sprintf('%s/?token2=%s#pods/%s', $this->service->serverModel->getUrl(), $token, $this->id);

        if ($js) {
            echo sprintf('<script>window.location.href = "%s";</script>', $url);
        } else {
            header('Location: ' . $url);
        }
        exit(0);
    }
}
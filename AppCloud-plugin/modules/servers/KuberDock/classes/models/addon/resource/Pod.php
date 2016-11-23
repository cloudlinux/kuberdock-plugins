<?php


namespace models\addon\resource;


use api\Api;
use api\ApiResponse;
use components\Component;
use components\InvoiceItemCollection;
use components\Units;
use models\addon\KubeTemplate;
use models\addon\Resources;
use models\addon\App;

class Pod extends ResourceFactory
{
    /**
     * @var string
     */
    protected $referer = '';

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
     * @throws \Exception
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
     * @param string $value
     */
    public function setReferer($value)
    {
        $this->referer = $value;
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
     * @return integer
     */
    public function getKubeType()
    {
        return $this->kube_type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPackageName()
    {
        return $this->getName();
    }

    /**
     *
     */
    public function create()
    {
        // TODO: Implement create() method.
    }

    /**
     * @return App
     */
    public function saveApp()
    {
        $packageRelation = $this->package->relatedKuberDock;
        $app = App::where('pod_id', $this->id)->where('service_id', $this->service->id)->first();

        if (!$app) {
            $app = new App();
        }

        $app->setRawAttributes([
            'user_id' => $this->service->userid,
            'kuber_product_id' => $packageRelation->kuber_product_id,
            'product_id' => $packageRelation->product_id,
            'pod_id' => $this->id,
            'data' => json_encode($this->getAttributes()),
            'referer' => $this->referer,
            'type' => ResourceFactory::TYPE_POD,
            'service_id' => $this->service->id,
        ]);
        $app->save();

        return $app;
    }

    /**
     * @param bool $js
     * @throws \Exception
     */
    public function redirect($js = true)
    {
        global $whmcs;

        if ($whmcs && !$whmcs->isClientAreaRequest()) {
            return;
        }

        $app = App::where('pod_id', $this->id)->where('service_id', $this->service->id)->first();

        if ($app && $app->referer) {
            $url = $app->referer;
        } else {
            $token = $this->service->getApi()->getJWTToken();
            $url = sprintf('%s/?token2=%s#pods/%s', $this->service->serverModel->getUrl(), $token, $this->id);
        }

        if ($js) {
            echo sprintf('<script>window.location.href = "%s";</script>', $url);
            exit(0);
        } else {
            header('Location: ' . $url);
        }
    }
}
<?php


namespace models\addon\resourceTypes;


use api\KuberDock_Api;
use components\InvoiceItemCollection;
use components\Tools;
use Exception;
use components\Units;
use exceptions\CException;
use models\addon\KubeTemplate;
use models\addon\Resources;
use models\billing\Service;

class PredefinedApp extends ResourceFactory
{
    /**
     * @var
     */
    protected $originData;

    /**
     * Load predefined app with parsed yaml
     * @param array $data
     * @return $this
     */
    public function load($data)
    {
        $this->originData = $data;
        $this->setAttributes(Tools::parseYaml($data));

        return $this;
    }

    /**
     * @return InvoiceItemCollection
     * @throws Exception
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

        if (isset($this->spec['template']['spec'])) {
            $spec = $this->spec['template']['spec'];
        } else {
            $spec = $this->spec;
        }

        $containers = $spec['containers'] ? $spec['containers'] : $spec;

        foreach ($containers as $row) {
            if (isset($row['kubes'])) {
                $items->add($this->package->createInvoiceItem($kubePrice, $row['name'], 'pod', $row['kubes']));
            }

            if (isset($row['ports']) && !isset($data['kuberdock']['appPackage']['domain'])) {
                foreach ($row['ports'] as $port) {
                    if (isset($port['isPublic']) && $port['isPublic'] && !$hasPublicIP) {
                        $hasPublicIP = true;
                        $ipPrice = $this->package->getPriceIP();
                        $items->add($this->package->createInvoiceItem($ipPrice, '', 'IP', 1, Resources::TYPE_IP));
                    }
                }
            }
        }

        if (isset($spec['volumes'])) {
            foreach ($spec['volumes'] as $row) {
                if (isset($row['persistentDisk']['pdSize'])) {
                    $unit = Units::getPSUnits();
                    $psPrice = $this->package->getPricePS();
                    $qty = $row['persistentDisk']['pdSize'];
                    $items->add(
                        $this->package->createInvoiceItem($psPrice, '', $unit, $qty, Resources::TYPE_PD)
                            ->setName($row['persistentDisk']['pdName'])
                    );
                }
            }
        }

        $items->setDescription($this->package->name . ' - Pod ' . $this->getName());

        return $items;
    }

    /**
     * @return mixed
     * @throws CException
     */
    public function getKubeType()
    {
        if (isset($this->kuberdock['kube_type'])) {
            return $this->kuberdock['kube_type'];
        } else if (isset($this->kuberdock['appPackage']['kubeType'])) {
            return $this->kuberdock['appPackage']['kubeType'];
        }

        throw new CException('Can\'t get kube type from PA');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return isset($this->metadata['name']) ? $this->metadata['name'] : 'Undefined';
    }

    /**
     * @return string
     */
    public function getPackageName()
    {
        return isset($this->kuberdock['appPackage']['name']) ? $this->kuberdock['appPackage']['name']: 'Undefined';
    }

    /**
     * @return Pod
     */
    public function create()
    {
        $data = $this->getApi()->createPodFromYaml($this->originData)->getData();
        $pod = new Pod($this->package);
        $pod->setAttributes($data);
        $pod->setService($this->service);

        return $pod;
    }
}
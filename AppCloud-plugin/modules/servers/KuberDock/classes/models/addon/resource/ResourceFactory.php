<?php


namespace models\addon\resource;


use api\KuberDock_Api;
use components\Component;
use components\InvoiceItemCollection;
use models\billing\Package;
use models\billing\Service;

abstract class ResourceFactory extends Component
{
    /**
     *
     */
    const TYPE_POD = 'pod';
    /**
     *
     */
    const TYPE_YAML = 'yaml';

    /**
     * @var Package
     */
    protected $package;
    /**
     * @var Service
     */
    protected $service;

    /**
     * @param string $data
     * @return $this
     */
    abstract public function load($data);

    /**
     * @return int
     */
    abstract public function getKubeType();

    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @return string
     */
    abstract public function getPackageName();

    /**
     * @return InvoiceItemCollection
     */
    abstract public function getInvoiceItems();

    /**
     * @return Pod
     */
    abstract public function create();

    /**
     * PredefinedApp constructor.
     * @param Package $package
     */
    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    /**
     * @return Package
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param Service $service
     * @return $this
     */
    public function setService(Service $service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return KuberDock_Api
     */
    public function getApi()
    {
        return $this->service->getApi();
    }

    /**
     * @return KuberDock_Api
     */
    public function getAdminApi()
    {
        return $this->service->getAdminApi();
    }

    /**
     * @param bool $packageIndependent
     * @return float
     */
    public function getPrice($packageIndependent = false)
    {
        if (!$packageIndependent && $this->package->isBillingPayg()) {
            return 0;
        }

        return $this->getInvoiceItems()->sum();
    }
}
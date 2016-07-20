<?php


namespace components;


use models\billing\Service;

class Pod extends Component
{
    /**
     *
     */
    const UPDATE_KUBES_DESCRIPTION = 'Update resources';

    /**
     * @var Service
     */
    protected $service;

    /**
     * Pod constructor.
     * @param Service $service
     */
    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * Load pod by id
     * @param string $id
     * @return $this
     */
    public function load($id)
    {
        $this->values = $this->service->getApi()->getPod($id);

        return $this;
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
     * @param string $podId
     */
    public function setUnpaid($podId)
    {
        // TODO: implement stop and unpaid when KD api will be done
        try {
            $this->service->getApi()->stopPod($podId);
            $this->service->getAdminApi()->updatePod($podId, array(
                'status' => 'unpaid',
            ));
        } catch (\Exception $e) {
            // Pod stopped
            $this->service->getAdminApi()->updatePod($podId, array(
                'status' => 'unpaid',
            ));
        }
    }
}
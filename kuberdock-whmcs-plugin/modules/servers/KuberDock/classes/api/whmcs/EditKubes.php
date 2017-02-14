<?php

namespace api\whmcs;


use models\addon\ItemInvoice;
use models\addon\resource\Pod;
use models\billing\Service;

class EditKubes extends Api
{
    protected function getRequiredParams()
    {
        return ['client_id', 'pod'];
    }

    public function answer()
    {
        $service = Service::typeKuberDock()->where('userid', $this->getParam('client_id'))->first();
        if (!$service) {
            throw new \Exception('User has no KuberDock service');
        }

        $pod = new Pod($service->package);
        $pod->safeLoad($this->getParam('pod'));
        $pod->setReferer($this->getParam('referer'));

        return $service->package->getBilling()->processApiOrder($pod, $service, ItemInvoice::TYPE_EDIT);
    }
}
<?php

namespace api\whmcs;


use exceptions\NotFoundException;
use models\addon\Item;

class DeletePod extends Api
{
    const PARAM_POD_ID = 'pod_id';

    protected function getRequiredParams()
    {
        return [DeletePod::PARAM_POD_ID];
    }

    public function answer()
    {
        $pod_id = $this->getParam(DeletePod::PARAM_POD_ID);

        /** @var Item $item */
        $item = Item::withPod($pod_id)->first();
        if (!$item) {
            throw new NotFoundException('Pod not found');
        }

        $item->stopInvoicing();
        $item->changeStatus();

        return 'Pod deleted';
    }
}
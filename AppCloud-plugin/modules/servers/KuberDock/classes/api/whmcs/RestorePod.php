<?php

namespace api\whmcs;


use exceptions\NotFoundException;
use models\addon\Item;

class RestorePod extends Api
{
    const PARAM_POD_ID = 'pod_id';

    protected function getRequiredParams()
    {
        return [self::PARAM_POD_ID];
    }

    public function answer()
    {
        $podId = $this->getParam(self::PARAM_POD_ID);

        /** @var Item $item */
        $item = Item::withPod($podId)->first();

        if (!$item) {
            throw new NotFoundException('Pod not found');
        }

        $item->restore();

        return 'Pod restored';
    }
}
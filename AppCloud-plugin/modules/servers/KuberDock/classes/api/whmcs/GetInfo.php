<?php

namespace api\whmcs;


use exceptions\NotFoundException;
use models\addon\PackageRelation;
use models\billing\Client;
use models\billing\Server;
use models\billing\Service;
use models\billing\Config;

class GetInfo extends Api
{
    protected function getRequiredParams()
    {
        return ['kdServer', 'user', 'userDomains'];
    }

    public function answer()
    {
        $kdServer = $this->getParam('kdServer');
        $user = $this->getParam('user');
        $domains = explode(',',  $this->getParam('userDomains'));

        $client = Client::byDomain($user, $domains)->first();
        if (!$client) {
            throw new NotFoundException('User not found. Probably you have no service with your current domain.');
        }

        /** @var Server $server */
        $server = Server::typeKuberDock()->byReferer($kdServer)->first();

        $packageRelation = PackageRelation::byReferer($kdServer)->count();
        if (!$packageRelation) {
            throw new NotFoundException(sprintf('KuberDock product for server %s not found', $kdServer));
        }

        $adminApi = $server->getApi();

        /** @var Service $service */
        $service = Service::typeKuberDock()
            ->where('userid', $client->id)
            ->where('server', $server->id)
            ->where('domainstatus', 'Active')
            ->orderBy('id', 'desc')
            ->first();

        $data = [];

        if ($service) {
            $kdPackageId = $service->package->relatedKuberDock->kuber_product_id;
            $data['service'] = [
                'id' => $service->id,
                'product_id' => $service->packageid,
                'token' => $service->getToken(),
                'domainstatus' => $service->domainstatus,
                'orderid' => $service->orderid,
                'kuber_product_id' => $kdPackageId,
            ];
            $data['package'] = $service->getAdminApi()->getPackageById($kdPackageId, true)->getData();
        } else {
            $data['packages'] = $adminApi->getPackages(true)->getData();
        }

        $data['billingUser'] = [
            'id' => $client->id,
            'defaultgateway' => $client->defaultgateway,
        ];

        $data['billing'] = 'WHMCS';
        $data['billingLink'] = Config::get()->SystemURL;

        $data['default']['kubeType'] = $adminApi->getDefaultKubeType()->getData();
        $data['default']['packageId'] = $adminApi->getDefaultPackageId()->getData();

        return $data;
    }
}
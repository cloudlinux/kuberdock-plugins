<?php

namespace Kuberdock\classes\api;

use Kuberdock\classes\components\Proxy;
use Kuberdock\classes\models\Pod;
use Kuberdock\classes\Base;
use Kuberdock\classes\exceptions\ApiException;
use Kuberdock\classes\Tools;
use Kuberdock\classes\models\Template;
use Kuberdock\classes\models\PredefinedApp;
use Kuberdock\classes\Validator;

class KuberDock extends API
{
    protected function get_pods($name = null)
    {
        return $name
            ? $this->getPod()->loadByName($name)->asArray()
            : $this->getPod()->getPods(true);
    }

    protected function post_pods()
    {
        $image = Tools::getPost('image');
        $pod = $this->getPod()->loadByImage($image);

        $pod->name = Tools::getPost('containerName', str_replace('/', '-', $image)).'-'.rand(1, 100);
        $pod->restartPolicy = 'Always';
        $pod->replicationController = true;
        $pod->packageId = Tools::getPost('package_id');
        $pod->kube_type = Tools::getPost('kube_id');

        if (Tools::getPost('public_access_type') == 'domain') {
            $pod->domain = Tools::getPost('public_access_domain');
        }

        $kubeCount = Tools::getPost('kube_count');
        $pod->checkMaxKubes($kubeCount);

        $pod->containers = array(
            'image' => $image,
            'kubes' => $kubeCount,
            'ports' => $pod->parsePorts(Tools::getPost('ports')),
            'env' => $pod->parseEnv(Tools::getPost('env')),
            'volumeMounts' => $pod->parseVolumeMounts(Tools::getPost('volume')),
        );

        $pod->createProduct();
        $pod->save();

        $pod = $pod->loadByName($pod->name);
        $package = Base::model()->getPanel()->billing->getPackage();

        if(Base::model()->getPanel()->billing->isFixedPrice($package['id'])) {
            $pod->order($pod->getLink());
        } else {
            $pod->start();
        }

        return $pod->asArray();
    }

    protected function put_pods($name)
    {
        $data = $this->getJSONData();
        $pod = $this->getPod()->loadByName($name);

        if ($data->command == 'edit') {
            $this->redirect = $pod->processCommand($data->command, $data);
            return;
        }

        return $pod->processCommand($data->command, $data);
    }

    protected function delete_pods($name)
    {
        $pod = $this->getPod()->loadByName($name);
        $pod->delete();

        return 'Application deleted';
    }

    protected function get_predefined($template_id)
    {
        return PredefinedApp::byId($template_id)->getPods();
    }

    protected function put_predefined($template_id)
    {
        $this->checkNumeric($template_id);
        $data = (array) $this->getJSONData();

        $app = PredefinedApp::byId($template_id);
        $app->getVariables();

        $validator = new Validator(array(
            'APP_NAME' => array(
                'name' => 'application name',
                'rules' => array(
                    'required' => true,
                    'min' => 2,
                    'max' => 64,
                    'alphanum' => true,
                ),
            ),
        ));

        if (!$validator->run($data)) {
            throw new ApiException($validator->getErrorsAsString());
        };

        $app->createApp($data);

        $pod = $app->getPod()->loadByName($app->template->getPodName());

        if (Base::model()->getPanel()->billing->isFixedPrice($app->getPackageId())) {
            $link = sprintf('#pod/%s/1', $app->template->getPodName());
            $pod->order(Base::model()->getPanel()->getURL() . $link);
        } else {
            $pod->start();
        }

        return $pod->asArray();
    }

    protected function get_pods_search($search)
    {
        $page = Tools::getParam('page', 1);

        $pod = $this->getPod();

        $images = $pod->searchImages($search, $page);
        $registryUrl = Base::model()->getPanel()->getAdminApi()->getRegistryUrl();

        $values = array(
            'page' => $page,
            'search' => $search,
            'registryUrl' => $registryUrl,
            'images' => $images,
        );

        return $values;
    }

    protected function get_pods_image($name, $sub_name = null)
    {
        if ($sub_name) {
            $name .= '/' . $sub_name;
        }

        return $this->getPod()->getImageInfo($name);
    }

    protected function get_templates($id = null)
    {
        $this->checkNumeric($id);

        $panel = $this->getPod()->getPanel();
        $template = new Template($panel);

        return $id
            ? $template->getById($id)
            : $template->getClientTemplates();
    }

    protected function get_templates_setup($id)
    {
        $this->checkNumeric($id);

        return PredefinedApp::byId($id)->getVariables();
    }

    protected function get_persistent_drives()
    {
        return $this->getPod()->getPersistentDrives();
    }

    protected function get_is_volume_resizable()
    {
        $api = Base::model()->getPanel()->getApi();
        $resizable = $api->isVolumeResizable();

        return array('resizable' => $resizable);
    }

    protected function get_user_package()
    {
        return Base::model()->getPanel()->billing->getPackage();
    }

    protected function get_token2()
    {
        $api = Base::model()->getPanel()->getApi();
        try {
            return array('token2' => $api->requestToken2());
        } catch (\Exception $e) {
            return array('token2' => '');
        }
    }

    protected function get_stream($token2 = null)
    {
        set_time_limit(0);

        Base::model()->getPanel()->renderStreamHeaders();
        echo str_repeat("\n", 1024);    // for cPanel

        $api = Base::model()->getPanel()->getApi();

        if (!$api->getToken()) {
            echo "retry: 50000\r\n";
            exit;
        }

        if (!$token2) {
            $token2 = $api->requestToken2();
        }

        $url = sprintf('%s/api/stream?token2=%s', $api->getServerUrl(), $token2);

        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        $handle = fopen($url, 'r', false, stream_context_create($arrContextOptions));

        if (ob_get_level() == 0) ob_start();

        while (!connection_aborted()) {
            $response = fgets($handle);

            // Add rule to .htaccess, because immediately after pod start can't get pod IP
            if (preg_match('/data:(.*)\n/', $response, $match)) {
                $data = json_decode($match[1]);
                if ($data->id) {
                    $pod = new Pod();
                    $pod = $pod->loadById($data->id);

                    if ($pod->template_id && in_array($pod->status, array('running'))) {
                        $proxy = new Proxy();
                        $proxy->addRuleToPod($pod);
                    }
                }
            }

            echo sprintf("%s", $response);
            ob_flush();
            flush();
            sleep(1);
        }

        echo "retry: 50000\r\n";
        fclose($handle);
    }

    /**
     * @return Pod
     */
    private function getPod()
    {
        return new Pod;
    }
}
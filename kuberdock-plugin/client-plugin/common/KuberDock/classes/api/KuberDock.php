<?php

namespace Kuberdock\classes\api;

use Kuberdock\classes\KcliCommand;
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
            : array_map(function($pod) {
                /** @var $pod Pod */
                return $pod->asArray();
            }, $this->getPod()->getPods());
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
            Base::model()->getPanel()->getApi()->updatePod($pod->id, array(
                'status' => 'unpaid',
            ));
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

        if($data->command == 'edit') {
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
        $app = new PredefinedApp($template_id);

        return $app->getPods();
    }

    protected function post_predefined($template_id)
    {
        $this->checkNumeric($template_id);
        $data = (array) $this->getJSONData();

        $app = new PredefinedApp($template_id);
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

        $app->setPackageId($data['package_id']);
        $app->createApp($data);

        $pod = $app->getPod()->loadByName($app->template->getPodName());
        $link = sprintf('#pod/%s/1', $app->template->getPodName());

        if(Base::model()->getPanel()->billing->isFixedPrice($app->getPackageId())) {
            Base::model()->getPanel()->getApi()->updatePod($pod->id, array(
                'status' => 'unpaid',
            ));
            $pod->order($link);
        } else {
            $pod->start();
        }

        $this->redirect = $link;

        return $pod->asArray();
    }

    protected function get_pods_search($search, $page = 1)
    {
        $this->checkNumeric($page);

        $pod = $this->getPod();

        $images = $pod->searchImages($search, $page);
        $registryUrl = $pod->command->getRegistryUrl();

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

        return $id
            ? $this->getTemplate()->getById($id)
            : $this->getTemplate()->getAll();
    }

    protected function get_templates_setup($id)
    {
        $this->checkNumeric($id);
        $app = new PredefinedApp($id);

        return $app->getVariables();
    }

    protected function get_persistent_drives()
    {
        return $this->getPod()->getPersistentDrives();
    }

    protected function get_stream()
    {
        set_time_limit(0);
        header('Content-Type: text/event-stream');

        $config = KcliCommand::getConfig();

        if(!isset($config['url']) || !isset($config['token'])) {
            echo "retry: 10000\n\n";
            exit;
        }

        $url = sprintf('%s/api/stream?token=%s', $config['url'], $config['token']);
        $handle = fopen($url, 'r');

        while($handle) {
            $response = fgets($handle);
            if($response) {
                echo sprintf("%s", $response);
            }
            flush();
            sleep(1);
        }

        echo "retry: 10000\n\n";
        fclose($handle);
    }

    /**
     * @return Pod
     */
    private function getPod()
    {
        return new Pod;
    }

    private function getTemplate()
    {
        $panel = $this->getPod()->getPanel();
        return new Template($panel);
    }
}
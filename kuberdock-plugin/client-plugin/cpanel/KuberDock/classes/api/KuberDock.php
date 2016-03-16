<?php

namespace Kuberdock\classes\api;

use Kuberdock\classes\models\Pod;
use Kuberdock\classes\Base;
use Kuberdock\classes\exceptions\CException;
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

    protected function post_pods($image)
    {
        $pod = $this->getPod()->loadByImage($image);

        $pod->name = Tools::getPost('containerName', str_replace('/', '-', $image)).'-'.rand(1, 100);
        $pod->restartPolicy = 'Always';
        $pod->replicationController = true;
        $pod->packageId = $packageId = Tools::getPost('product_id');
        $pod->kube_type = Tools::getPost('kuber_kube_id');

        $kubeCount = Tools::getPost('kube_count');
        $this->checkMaxKubes($kubeCount);

        $pod->containers = array(
            'image' => $image,
            'kubes' => $kubeCount,
            'ports' => Tools::getPost('Ports'),
            'env' => Tools::getPost('Env'),
            'volumeMounts' => Tools::getPost('Volume'),
        );

        $pod->create();
        $pod->save();

        $pod = $pod->loadByName($pod->name);

        if(Base::model()->getPanel()->billing->isFixedPrice($packageId)) {
            Base::model()->getPanel()->getApi()->updatePod($pod->id, array(
                'status' => 'unpaid',
            ));
            $pod->order($pod->getLink());
        } else {
            $pod->start();
        }

        return array(
            'status' => 'success',
            'message' => 'Application created',
            'redirect' => $pod->panel->getURL(),
        );
    }

    protected function post_predefined($template_id)
    {
        $this->checkNumeric($template_id);

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

        if (!$validator->run($_POST)) {
            throw new CException($validator->getErrorsAsString());
        };

        $app->setPackageId(Tools::getPost('product_id'));
        $app->createApp($_POST);

        $pod = $app->getPod()->loadByName($app->template->getPodName());
        $link = sprintf('%s?c=app&a=installPredefined&template=%s&podName=%s&postDescription=%s',
            $pod->panel->getURL(), $template_id, $app->template->getPodName(), 1);

        if(Base::model()->getPanel()->billing->isFixedPrice($app->getPackageId())) {
            Base::model()->getPanel()->getApi()->updatePod($pod->id, array(
                'status' => 'unpaid',
            ));
            $pod->order($link);
        } else {
            $pod->start();
        }

        return array(
            'status' => 'success',
            'message' => 'Application created',
            'redirect' => $link,
        );
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

    protected function post_pods_start()
    {
        $container = Tools::getPost('name');
        $pod = $this->getPod()->loadByName($container);

        if($pod->isUnPaid()) {
            $pod->order();
            $message = 'Application started';
        } elseif(in_array($pod->status, array('stopped', 'terminated', 'failed', 'succeeded'))) {
            $pod->start();
            $message = 'Application started';
        } else {
            $message = 'Application is already running';
        }

        return array(
            'status' => 'success',
            'message' => $message,
        );
    }

    protected function post_pods_stop()
    {
        $container = Tools::getPost('name');
        $pod = $this->getPod()->loadByName($container);

        if (in_array($pod->status, array('running', 'pending'))) {
            $pod->stop();
            $message = 'Application stopped';
        } else {
            $message = 'Application is already stopped';
        }

        return array(
            'status' => 'success',
            'message' => $message,
        );
    }

    protected function post_pods_delete()
    {
        $container = Tools::getPost('name');
        $pod = $this->getPod()->loadByName($container);

        $pod->delete();

        return array(
            'status' => 'success',
            'message' => 'Application deleted',
            'redirect' => $_SERVER['SCRIPT_URI'],
        );
    }

    protected function post_pods_restart()
    {
        $name = Tools::getPost('name');
        $wipeOut = Tools::getPost('wipeOut', 0);

        $pod = $this->getPod()->loadByName($name);

        Base::model()->getPanel()->getApi()->redeployPod($pod->id, $wipeOut);

        return array(
            'status' => 'success',
            'message' => 'Application restarted',
        );
    }

    protected function post_pods_upgrade()
    {
        $name = Tools::getPost('name');
        $pod = $this->getPod()->loadByName($name);

        $newKubes = Tools::getPost('new_container_kubes', array());
        $values = array();

        foreach($_POST['container_name'] as $k=>$name) {
            $kubes = (int) $newKubes[$k];
            $this->checkMaxKubes($kubes);
            $values[] = array(
                'name' => $name,
                'kubes' => $kubes,
            );
        }

        $params['id'] = $pod->id;
        $params['containers'] = $values;
        $params['kube_type'] = $pod->kube_type;
        $product = Base::model()->getPanel()->billing->getProduct();

        if(Base::model()->getPanel()->billing->isFixedPrice($product['id'])) {
            $pod->orderKubes($params, $pod->getLink());
        } else {
            Base::model()->getPanel()->getApi()->addKubes($pod->id, $params['containers']);
        }

        return array(
            'status' => 'success',
            'message' => 'Application upgraded',
            'redirect' => $pod->panel->getURL() . '?a=podDetails&podName=' . $pod->name,
        );
    }

    protected function get_pods_redirect()
    {
        $podName = Tools::getPost('name');
        $pod = $this->getPod()->loadByName($podName);

        return array(
            'redirect'=> $pod->getPodUrl(true),
        );
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

    protected function checkMaxKubes($kubeCount)
    {
        $sysapi = Base::model()->getPanel()->getApi()->getSysApi('name');
        $maxKubes = $sysapi['max_kubes_per_container']['value'];

        if ($kubeCount > $maxKubes) {
            throw new CException('Only ' . $maxKubes . ' kubes allowed');
        }
    }
}
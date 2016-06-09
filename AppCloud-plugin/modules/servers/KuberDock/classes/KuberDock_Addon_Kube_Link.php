<?php

use base\CL_Model;

class KuberDock_Addon_Kube_Link extends CL_Model
{
    public function setTableName()
    {
        $this->tableName = 'KuberDock_kubes_links';
    }

    public static function loadByProductId($product_id)
    {
        $sql = "
            SELECT
                l.*,
                t.kuber_kube_id,
                t.kube_name,
                t.kube_type,
                t.cpu_limit,
                t.memory_limit,
                t.hdd_limit,
                t.traffic_limit,
                t.server_id
            FROM " . self::model()->tableName . " l
            INNER JOIN " . KuberDock_Addon_Kube_Template::model()->tableName . " t ON l.template_id=t.id
            WHERE l.product_id=?;
        ";

        return \base\CL_Query::model()->query($sql, array($product_id))->getRows();
    }

    /**
     *
     */
    public function deleteKubeFromPackage()
    {
        $api = $this->getApi();
        $kubeIds = $api->getPackageKubesById($this->kuber_product_id)->getData();
        $template = \KuberDock_Addon_Kube_Template::model()->loadById($this->template_id);

        if(in_array($template->kuber_kube_id, array_values($kubeIds))) {
            $api->deletePackageKube($this->kuber_product_id, $template->kuber_kube_id);
        }
    }

    /**
     *
     */
    public function beforeSave()
    {
        if(!empty($this->kube_price) || $this->kube_price==='0') {
            $template = \KuberDock_Addon_Kube_Template::model()->loadById($this->template_id);

            $this->getApi()->addKubeToPackage($this->kuber_product_id, $template->kuber_kube_id, $this->kube_price);
        } elseif(trim($this->kube_price) === '') {
            // if price is empty string, we delete kube and unlink it from kd
            $this->delete();
            return false;
        }

        return true;
    }

    /**
     *
     */
    public function afterSave()
    {
        KuberDock_Product::model()->loadById($this->product_id)->hideOrShow();
    }

    public function beforeDelete()
    {
        if(empty($this->kube_price) && trim($this->kube_price) === '') {
            $this->deleteKubeFromPackage();
        }

        return true;
    }

    /**
     *
     */
    public function afterDelete()
    {
        KuberDock_Product::model()->loadById($this->product_id)->hideOrShow();
    }

    /**
     * @return \api\KuberDock_Api
     */
    private function getApi()
    {
        if (isset($this->product_id)) {
            return KuberDock_Product::model()->loadById($this->product_id)->getApi();
        }

        return KuberDock_Server::model()->getActive()->getApi();
    }
} 
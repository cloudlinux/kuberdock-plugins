<?php

namespace migrations\versions;

class Version1 implements \migrations\VersionInterface
{
    /**
     * AC-2940
     * @return array
     */
    public function up()
    {
        return array(
            'CREATE TABLE IF NOT EXISTS `KuberDock_kubes_templates` (
                id INT AUTO_INCREMENT,
                kuber_kube_id INT,
                kube_name VARCHAR(255),
                kube_type TINYINT(1) DEFAULT 0,
                cpu_limit DECIMAL(10,4),
                memory_limit INT,
                hdd_limit INT,
                traffic_limit DECIMAL(10,2),
                server_id INT,
                PRIMARY KEY (id),
                INDEX (kuber_kube_id)
            ) ENGINE=INNODB',

            'CREATE TABLE IF NOT EXISTS `KuberDock_kubes_links` (
                id INT AUTO_INCREMENT,
                template_id INT,
                product_id INT,
                kuber_product_id INT,
                kube_price DECIMAL(10,2),
                PRIMARY KEY (id),
                FOREIGN KEY (product_id)
                    REFERENCES KuberDock_products(product_id)
                    ON UPDATE CASCADE ON DELETE CASCADE,
                FOREIGN KEY (template_id)
                    REFERENCES KuberDock_kubes_templates(id)
                    ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=INNODB',

            'INSERT INTO `KuberDock_kubes_templates`
                SELECT
                    NULL,
                    kuber_kube_id,
                    kube_name,
                    kube_type,
                    cpu_limit,
                    memory_limit,
                    hdd_limit,
                    traffic_limit,
                    server_id
                FROM `KuberDock_kubes`
                WHERE product_id IS NULL
                ORDER BY kuber_kube_id;',

            'INSERT INTO `KuberDock_kubes_links`
                SELECT
                    NULL,
                    t.id,
                    k.product_id,
                    k.kuber_product_id,
                    k.kube_price
                FROM `KuberDock_kubes` k
                INNER JOIN `KuberDock_kubes_templates` t
                ON k.kuber_kube_id=t.kuber_kube_id
                WHERE product_id IS NOT NULL
                ORDER BY k.kuber_kube_id, k.product_id;
            ',
        );
    }

    public function down()
    {
        return array();
    }
}

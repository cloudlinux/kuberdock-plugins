<?php
/**
 * @var $kubes \KuberDock_Addon_Kube[]
 * @var array $brokenPackages
 */
?>

<div class="container-fluid">
    <div class="row offset-top">
        <p class="text-right">
            <a href="<?php echo \base\CL_Base::model()->baseUrl?>&a=add">
                <button type="button" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span> Add kube type</button>
            </a>
        </p>
    </div>

    <div class="row">
        <table id="kubes_table" class="tablesorter table table-bordered">
        <thead>
        <tr class="active">
            <th>Id</th>
            <th>Kube type</th>
            <th>CPU limit (<?php echo \components\KuberDock_Units::getCPUUnits()?>)</th>
            <th>Memory limit (<?php echo \components\KuberDock_Units::getMemoryUnits()?>)</th>
            <th>HDD limit (<?php echo \components\KuberDock_Units::getHDDUnits()?>)</th>
            <th>Traffic limit (<?php echo \components\KuberDock_Units::getTrafficUnits()?>)</th>
        </tr>
        </thead>

            <?php foreach($kubes as $kube): ?>
                <tr>
                    <td><?php echo $kube['kuber_kube_id'];?></td>
                    <td class="pricing" data-id="<?php echo $kube['kuber_kube_id'];?>">
                        <?php echo $kube['kube_name'];?>
                        <span class="pricing">Pricing settings</span>
                    </td>
                    <td><?php echo (float) $kube['cpu_limit']?></td>
                    <td><?php echo $kube['memory_limit']?></td>
                    <td><?php echo $kube['hdd_limit']?></td>
                    <td><?php echo $kube['traffic_limit']?></td>
                </tr>
                <tr id="package_<?php echo $kube['kuber_kube_id'];?>" style="display: none;">
                    <td colspan="6">

                        <table id="packages_of_kube">
                            <tr>
                                <th>Billing type</th>
                                <th>Package name (id)</th>
                                <th>Price</th>
                            </tr>
                            <?php foreach($kube['packages'] as $package):?>
                                <tr>
                                    <td><?php echo $package['payment_type']; ?></td>
                                    <td><?php echo $package['name']; ?> (<?php echo $package['id']; ?>)</td>
                                    <td>
                                        <form class="price_package_form" data-id="<?php echo $package['id']; ?>" method="post">
                                            <?php echo \base\CL_Csrf::render(); ?>
                                            <input data-prev="<?php echo $package['kube_price']; ?>" name="kube_price" type="text" value="<?php echo $package['kube_price']; ?>">
                                            <input type="hidden" name="id" value="<?php echo $package['id']; ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $package['product_id']; ?>">
                                            <input type="hidden" name="kuber_kube_id" value="<?php echo $kube['kuber_kube_id'];?>">
                                            <span id="form_buttons_<?php echo $package['id']; ?>">
                                                <button type="submit" class="btn btn-default">Save</button>
                                                <button type="cancel" class="btn cancel_button">Cancel</button>
                                            </span>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>

                    </td>
                </tr>

            <?php endforeach; ?>
        </table>

        <?php if (count($brokenPackages)): ?>
            <table  class="table table-bordered">
                <tr class="active">
                    <th>Package name</th>
                    <th>Status</th>
                    <th>Price type</th>
                </tr>
                <?php foreach($brokenPackages as $row):
                    $product = KuberDock_Product::model()->loadByParams($row);
                    ?>
                    <tr class="danger">
                        <td><a href="configproducts.php?action=edit&id=<?php echo $row['id']?>" target="_blank"><?php echo $row['name']?></a></td>
                        <td>
                            <span class="glyphicon  glyphicon-exclamation-sign" aria-hidden="true"></span>
                            Package not added to KuberDock. Please edit <a href="configproducts.php?action=edit&id=<?php echo $row['id']?>">product</a>
                        </td>
                        <td>per <?php echo $product->getReadablePaymentType()?></td>
                    </tr>
                <?php endforeach;?>
            </table>
        <?php endif;?>

    </div>
</div>
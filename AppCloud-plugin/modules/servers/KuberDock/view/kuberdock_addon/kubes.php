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
            <th class="col-md-3">Kube type (id)</th>
            <th class="col-md-2">CPU limit (<?php echo \components\KuberDock_Units::getCPUUnits()?>)</th>
            <th class="col-md-3">Memory limit (<?php echo \components\KuberDock_Units::getMemoryUnits()?>)</th>
            <th class="col-md-2">HDD limit (<?php echo \components\KuberDock_Units::getHDDUnits()?>)</th>
            <th class="col-md-1">Traffic limit (<?php echo \components\KuberDock_Units::getTrafficUnits()?>)</th>
        </tr>
        </thead>
            <?php foreach($kubes as $kube): ?>
                <tr>
                    <td class="col-md-3 pricing" data-id="<?php echo $kube['kuber_kube_id'];?>">
                        <strong><?php echo $kube['kube_name'];?> (<?php echo $kube['kuber_kube_id'];?>)</strong>
                        <span class="pricing pull-right">$ Pricing settings</span>
                    </td>
                    <td class="col-md-2"><?php echo (float) $kube['cpu_limit']?></td>
                    <td class="col-md-3"><?php echo $kube['memory_limit']?></td>
                    <td class="col-md-2"><?php echo $kube['hdd_limit']?></td>
                    <td class="col-md-2"><?php echo $kube['traffic_limit']?></td>
                </tr>
                <tr id="package_<?php echo $kube['kuber_kube_id'];?>" style="display: none;">
                    <td colspan="6" class="packages_of_kube_col">
                        <table class="table" id="packages_of_kube">
                            <thead>
                                <tr>
                                    <th class="left col-md-3">Billing type</th>
                                    <th class="middle col-md-2">Package name (id)</th>
                                    <th class="right col-md-3">Price</th>
                                    <th class="col-md-2"></th>
                                    <th class="col-md-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($kube['packages'] as $package):?>
                                <tr>
                                    <td class="left col-md-3"><strong><?php echo $package['payment_type']; ?></strong></td>
                                    <td class="middle col-md-2"><?php echo $package['name']; ?> (<?php echo $package['id']; ?>)</td>
                                    <td class="right col-md-3">
                                        <form class="price_package_form" data-id="<?php echo $package['id']; ?>" method="post">
                                            <?php echo \base\CL_Csrf::render(); ?>
                                            <input data-prev="<?php echo $package['kube_price']; ?>" name="kube_price" type="text" value="<?php echo $package['kube_price']; ?>">
                                            <input type="hidden" name="id" value="<?php echo $package['id']; ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $package['product_id']; ?>">
                                            <input type="hidden" name="kuber_kube_id" value="<?php echo $kube['kuber_kube_id'];?>">
                                            <span id="form_buttons_<?php echo $package['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-xs">Save</button>
                                                <button type="cancel" class="btn btn-danger btn-xs cancel_button">Cancel</button>
                                            </span>
                                        </form>
                                    </td>
                                    <td class="col-md-2"></td>
                                    <td class="col-md-2"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
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
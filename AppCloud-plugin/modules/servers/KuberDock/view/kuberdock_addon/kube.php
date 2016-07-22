<tr>
    <td class="col-md-3 pricing" data-id="<?php echo $kube['kuber_kube_id'];?>">
        <strong><?php echo $kube['kube_name'];?> (<?php echo $kube['kuber_kube_id'];?>)</strong>
        <span class="pricing pull-right">$ Pricing settings</span>
    </td>
    <td class="col-md-3"><?php echo (float) number_format($kube['cpu_limit'], 2)?></td>
    <td class="col-md-3"><?php echo $kube['memory_limit']?></td>
    <td class="col-md-3"><?php echo $kube['hdd_limit']?></td>
    <?php /* AC-3783
    <td class="col-md-2"><?php echo $kube['traffic_limit']?></td>
    */ ?>
    <td>
        <button type="button" class="btn btn-default btn-xs kube-delete<?php echo $kube['deletable']? '' : ' hidden'?>"
                data-kube-id="<?php echo $kube['id']?>">
            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
        </button>
    </td>
</tr>

<tr id="package_<?php echo $kube['kuber_kube_id'];?>" class="package_row" style="display: none;">
    <td colspan="6" class="packages_of_kube_col">
        <table class="table" id="packages_of_kube">
            <thead>
            <tr>
                <th class="left col-md-3">Billing type</th>
                <th class="middle col-md-2">Package name (id)</th>
                <th class="right col-md-1">Active</th>
                <th class="right col-md-5">Price</th>
                <th class="col-md-1"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($kube['packages'] as $package):?>
                <tr>
                    <td class="left col-md-3"><strong><?php echo $package['payment_type']; ?></strong></td>
                    <td class="middle col-md-2">
                        <?php echo $package['name']; ?> (<?php echo $package['kuber_product_id']; ?>)
                    </td>
                    <td class="middle col-md-1">
                        <input
                            type="checkbox"
                            class="active_kube_checkbox"
                            id="active_kube_checkbox_<?php echo $package['input_id']; ?>"
                            data-input="<?php echo $package['input_id']; ?>"
                            <?php if ($package['kubeIsActive']) :?> checked="checked"<?php endif;?>
                        >
                    </td>
                    <td class="right col-md-5">
                        <form class="price_package_form" method="post">
                            <?php echo \base\CL_Csrf::render(); ?>
                            <input
                                id="price_input_<?php echo $package['input_id']; ?>"
                                data-prev="<?php echo $package['prev']; ?>"
                                data-input="<?php echo $package['input_id']; ?>"
                                name="kube_price"
                                type="text"
                                value="<?php echo $package['kube_price']; ?>"
                                <?php if (!$package['kubeIsActive']) :?> disabled="disabled"<?php endif;?>
                            >
                            <input type="hidden" name="id" value="<?php echo $package['id']; ?>">
                            <input type="hidden" name="product_id" value="<?php echo $package['product_id']; ?>">
                            <input type="hidden" name="kuber_kube_id" value="<?php echo $kube['kuber_kube_id'];?>">
                            <input type="hidden" name="kuber_product_id" value="<?php echo $package['kuber_product_id'];?>">
                            <input type="hidden" name="template_id" value="<?php echo $kube['id'];?>">
                            <span class="hidden">
                                <button type="submit" class="btn btn-success btn-xs">Save</button>
                                <button type="cancel" class="btn btn-danger btn-xs cancel_button">Cancel</button>
                            </span>
                        </form>
                    </td>
                    <td class="col-md-2"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    </td>
</tr>
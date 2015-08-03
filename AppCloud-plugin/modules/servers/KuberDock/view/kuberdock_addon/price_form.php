<form class="form-horizontal" method="post" id="kube_price_form">
    <table class="table table-bordered">
        <tr class="active">
            <th>KuberDock package</th>
            <th>Kube type name</th>
            <th>Price (per hour)</th>
        </tr>

        <?php if($priceKubes):?>
        <tr>
            <td rowspan="<?php echo (count($priceKubes) + 1)?>">
                <select type="text" name="product_id" class="form-control" id="product_id">
                    <option value="">Select package</option>
                    <?php foreach($products as $product):?>
                        <option value="<?php echo $product['id']?>"<?php echo $product['id'] == $productId ? ' selected' : ''?>>
                            <?php echo $product['name']?>
                        </option>
                    <?php endforeach;?>
                </select>
            </td>
        </tr>
        <?php foreach($priceKubes as $kube):?>
        <tr>
            <td>
                <label class="control-label"><?php echo $kube['kube_name']?></label>
                <input type="hidden" name="id[]" value="<?php echo $kube['id']?>">
            </td>
            <td width="200px">
                <input type="text" name="kube_price[]" class="form-control" value="<?php echo $kube['kube_price']?>">
            </td>
        </tr>
        <?php endforeach;?>
        <?php else: ?>
        <tr>
            <td>
                <select type="text" name="product_id" class="form-control" id="product_id">
                    <option value="">Select package</option>
                    <?php foreach($products as $product):?>
                        <option value="<?php echo $product['id']?>"<?php echo $product['id'] == $productId ? ' selected' : ''?>>
                            <?php echo $product['name']?>
                        </option>
                    <?php endforeach;?>
                </select>
            </td>
            <td></td>
            <td width="200px"></td>
        </tr>
        <?php endif;?>
    </table>

    <?php if($priceKubes):?>
    <div class="form-group">
        <div class="col-sm-offset-6 col-sm-10">
            <button type="submit" class="btn btn-default">Update</button>
        </div>
    </div>
    <?php endif;?>
</form>
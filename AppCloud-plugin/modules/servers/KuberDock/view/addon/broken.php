<?php if (count($brokenPackages)): ?>
    <div class="row offset-top">
        <table  class="table table-bordered">
            <tr class="active">
                <th class="col-md-3">Package name</th>
                <th class="col-md-7">Status</th>
                <th class="col-md-2">Price type</th>
            </tr>
            <?php foreach($brokenPackages as $row): ?>
                <?php $product = KuberDock_Product::model()->loadByParams($row); ?>
                <tr class="danger">
                    <td class="col-md-3">
                        <a href="configproducts.php?action=edit&id=<?php echo $row['id']?>" target="_blank">
                            <?php echo $row['name']?>
                        </a>
                    </td>
                    <td class="col-md-7">
                        <span class="glyphicon  glyphicon-exclamation-sign" aria-hidden="true"></span>
                        Package not added to KuberDock. Please check
                        <a href="configproducts.php?action=edit&id=<?php echo $row['id']?>">product</a>
                        settings and click Save Changes
                    </td>
                    <td class="col-md-2">per <?php echo $product->getReadablePaymentType()?></td>
                </tr>
            <?php endforeach;?>
        </table>

    </div>
<?php endif;?>
<div class="center" style="margin-top: 10px"><h3>Pods</h3></div>

<?php if ($pods): ?>
    <table class="client product-info">
        <tr>
            <th>Name</th>
            <th>Images</th>
            <th>Kubes</th>
            <th>Kube type</th>
            <th>Price</th>
            <th>Next due date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php foreach($pods as $pod):
            $item = \models\addon\Item::withPod($pod['id'])->first();
            $billableItem = $item->billableItem;

            array_walk($pod['containers'], function($e) use (&$pod) {
                $pod['kubes'] += $e['kubes'];
                $pod['images'][] = $e['image'];
            });
            ?>
            <tr>
                <td>
                    <a href="<?php echo $service->getLoginLink() . '&next=#pods/' . $pod['id'];?>" target="_blank">
                        <?php echo $pod['name']?>
                    </a>
                </td>
                <td><?php echo implode(',', $pod['images'])?></td>
                <td><?php echo $pod['kubes']?></td>
                <td><?php echo $kubes[$pod['kube_type']]['name']?></td>
                <td>
                    <?php echo $billableItem ? $currency->getFullPrice($billableItem->amount) . ' / ' . $package->getReadablePaymentType() : ''?>
                </td>
                <td>
                    <?php echo $billableItem ? $billableItem->duedate->format(\components\Tools::getDateFormat()) : ''?>
                </td>
                <td><?php echo $pod['status']?></td>
                <td>
                    <a href="#login-modal" data-toggle="modal" data-target="#restartModal">
                        <button class="btn btn-success pod-restart">Restart</button>
                    </a>
                </td>
            </tr>
        <?php endforeach;?>
    </table>
<?php else:?>
    <div>No pods yet</div>
<?php endif; ?>

<div class="modal fade" id="restartModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    Confirm restarting of application <?php echo $pod['name']?>
                </h4>
            </div>
            <div class="modal-body">
                You can wipe out all the data and redeploy the application or you can just restart and save data in Persistent storages of your application.
            </div>
            <div class="modal-footer">
                <a href="<?php printf('kdorder.php?a=restart&podId=%s&serviceId=%s&wipeOut=1', $pod['id'], $service->id)?>">
                    <button type="button" class="btn btn-danger">Wipe out</button>
                </a>
                <a href="<?php printf('kdorder.php?a=restart&podId=%s&serviceId=%s', $pod['id'], $service->id)?>">
                    <button type="button" class="btn btn-primary">Just restart</button>
                </a>
            </div>
        </div>
    </div>
</div>

<script>

</script>
<div class="container pull-left">
    <div class="row">
        <div class="col-xs-8 col-md-8">
            <div class="top-offset">
                <a name="add" href="?a=app" class="btn btn-primary">Add app</a>
            </div>
            <?php if ($apps) : ?>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th class="text-center col-xs-1 col-md-1">#</th>
                    <th class="text-center col-xs-3 col-md-6">App name</th>
                    <th class="text-center col-xs-4 col-md-5">Actions</th>
                </tr>
                </thead>
                <?php foreach ($apps as $app):?>
                <tr>
                    <td class="text-center"><?php echo $app['index'];?></td>
                    <td><?php echo $app['name'];?></td>
                    <td class="text-center actions">
                        <?php echo $app['actions'];?>
                    </td>
                </tr>
                <?php endforeach;?>
            </table>
            <?php else : ?>
                <div role="alert" class="top-offset alert alert-info">
                    You haven't any apps now. Add apps.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
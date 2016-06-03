<div id="edit-kubecli-conf" class="container-fluid <?php if (isset($error)): ?>row <?php else:?>pull-left <?php endif;?>top-offset">

    <form method="post" class="form-inline">
        <input type="hidden" name="tab" value="kubecli">
        <div class="row col-md-12">
            <label for="app_name">KuberDock master url</label>
            <input type="text" name="url" value="<?php echo $kubeCli['url'];?>">
        </div>

        <div class="row col-md-12">
            <label for="app_name">User</label>
            <input type="text" name="user" value="<?php echo $kubeCli['user'];?>">
        </div>

        <div class="row col-md-12">
            <label for="app_name">Password</label>
            <input type="password" name="password" value="<?php echo $kubeCli['password'];?>" autocomplete="off">
        </div>

        <div class="row col-md-12">
            <label for="app_name">Registry</label>
            <input type="text" name="registry" value="<?php echo $kubeCli['registry'];?>">
        </div>

        <div class="row col-md-12">
            <button type="submit" class="btn btn-primary" name="save">Save</button>
        </div>
    </form>
</div>
<html>
    <body onload="redirect()">
        <form id="redirect" method="post" action="<?php echo $action?>">
            <input type="hidden" name="token" value="<?php echo $token?>">

            <?php if (isset($postDescription)) : ?>
                <input type="hidden" name="postDescription" value="<?php echo $postDescription?>">
            <?php endif; ?>

            <?php if (isset($error)) : ?>
                    <input type="hidden" name="error" value="<?php echo $error?>">
            <?php endif; ?>
        </form>

        <script>
            function redirect() {
                document.getElementById('redirect').submit();
            }
        </script>
    </body>
</html>
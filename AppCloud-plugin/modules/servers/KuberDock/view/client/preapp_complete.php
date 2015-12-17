<html>
    <body onload="redirect()">
        <form id="redirect" method="post" action="<?php echo $serverLink . '/#pods/' . $podId?>">
            <input type="hidden" name="token" value="<?php echo $token?>">
            <input type="hidden" name="postDescription" value="<?php echo $postDescription?>">
        </form>

        <script>
            function redirect() {
                document.getElementById('redirect').submit();
            };
        </script>
    </body>
</html>
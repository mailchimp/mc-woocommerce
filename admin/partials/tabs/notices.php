<?php if(isset($_GET['error_notice']) && file_exists(__DIR__.'/errors/'.$_GET['error_notice'].'.php')): ?>
    <?php include(__DIR__.'/errors/'.$_GET['error_notice'].'.php'); ?>
<?php endif; ?>

<?php if(isset($_GET['success_notice']) && file_exists(__DIR__.'/success/'.$_GET['success_notice'].'.php')): ?>
    <?php include(__DIR__.'/success/'.$_GET['success_notice'].'.php'); ?>
<?php endif; ?>


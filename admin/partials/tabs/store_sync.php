<?php

$handler = MailChimp_Woocommerce_Admin::connect();

// if we're not ready for a sync, we need to redirect out of this page like now.
//if (!$handler->isReadyForSync()) {
//    wp_redirect('options-general.php?page=mailchimp-woocommerce&tab=api_key&error_notice=not_ready_for_sync');
//}

if (($sync_started_at = $this->getData('sync.started_at', false))) {
    $date = mailchimp_date_local(date("c", $sync_started_at));
    $sync_started_at = $date->format('D, M j, Y g:i A');
} else {
    $sync_started_at = 'N/A';
}

if (($sync_complete_at = $this->getData('sync.completed_at', false))) {
    $date = mailchimp_date_local(date("c", $sync_complete_at));
    $sync_complete_at = $date->format('D, M j, Y g:i A');
} else {
    $sync_complete_at = $sync_started_at !== 'N/A' ? 'In Progress' : 'N/A';
}

?>

<h2 style="padding-top: 1em;">Sync Timeline</h2>

<p>Sync Started: <?php echo $sync_started_at; ?></p>
<p>Sync Completed: <?php echo $sync_complete_at; ?></p>


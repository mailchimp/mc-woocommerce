<?php

/**
 * Trigger $X additional loopback requests with unique URL params.
 */
function mailchimp_request_additional_runners() {
    // allow self-signed SSL certificates
    add_filter( 'https_local_ssl_verify', '__return_false', 100 );
    $processes = defined('MAILCHIMP_HIGH_PERFORMANCE_PROCESSES') ?
        (int) MAILCHIMP_HIGH_PERFORMANCE_PROCESSES : 5;
    if (empty($processes)) {
        return;
    }
    for ( $i = 0; $i < $processes; $i++ ) {
        wp_remote_post( admin_url( 'admin-ajax.php' ), array(
            'method'      => 'POST',
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => false,
            'headers'     => array(),
            'body'        => array(
                'action'     => 'mailchimp_create_additional_runners',
                'instance'   => $i,
                'mailchimp_actionscheduler_nonce' => wp_create_nonce( 'mailchimp_additional_runner_' . $i ),
            ),
            'cookies'     => array(),
        ) );
    }
    mailchimp_debug('actionscheduler', "increased processes by {$processes}");
}

add_action( 'action_scheduler_run_queue', 'mailchimp_request_additional_runners', 0 );

/**
 * Handle requests initiated by eg_request_additional_runners() and start a queue runner if the request is valid.
 */
function mailchimp_create_additional_runners() {
    if ( isset( $_POST['mailchimp_actionscheduler_nonce'] ) && isset( $_POST['instance'] ) && wp_verify_nonce( $_POST['mailchimp_actionscheduler_nonce'], 'mailchimp_additional_runner_' . $_POST['instance'] ) ) {
        ActionScheduler_QueueRunner::instance()->run();
    }
    wp_die();
}

add_action( 'wp_ajax_nopriv_mailchimp_create_additional_runners', 'mailchimp_create_additional_runners', 0 );
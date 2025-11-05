<?php
/**
 * Logs tab template
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

?>
<?php
$mailchimp_logger = new Mailchimp_Woocommerce_DB_Logger();
$page =  $_GET['mc_log_page'] ?? 1;
$per_page =  $_GET['mc_log_per_page'] ?? 10;

$filters = [
    'level'     => $_GET['mc_log_level'] ?? null,
    'action'    => null,
    'from_date' => $_GET['mc_log_date_from'] ?? null,
    'to_date'   => $_GET['mc_log_date_to'] ?? null,
    'order'     => 'DESC',
];

$logs = $mailchimp_logger->paginate($page, $per_page, $filters);
echo mailchimp_environment_variables()->logging;
?>

<input type="hidden" name="mailchimp_active_settings_tab" value="<?php echo MC_WC_LOGS_TAB; ?>"/>
<div class="mc-wc-tab-content-wrapper logs">
    <div class="mc-wc-tab-content-box has-underline">
        <div class="mc-wc-tab-content-title">
            <h3><?php esc_html_e('Activity logs', 'mailchimp-for-woocommerce' ); ?></h3>
        </div>
        <div class="mc-wc-tab-content-description-small mc-wc-fz-13">
        <?php esc_html_e('Advanced troubleshooting can be conducted with the logging capability turned on. By default, it’s set to “standard” and you may toggle to either “debug” or “none” as needed. With standard logging, you can see basic information about the data submission to Mailchimp including any errors. “Debug” gives a much deeper insight that is useful to share with support if problems arise.', 'mailchimp-for-woocommerce' ); ?>
        </div>
        <div class="mc-wc-logs-list-choose">
            <p>
            <?php esc_html_e('Logging preferences', 'mailchimp-for-woocommerce' ); ?>
            </p>
            <div class="mc-wc-select-wrapper">
                <select class="mc-wc-select mc-wc-select-not-bold" id="mailchimp-log-pref" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_logging]" required>
                    <?php $logging_preference = mailchimp_environment_variables()->logging; ?>
                    <?php
                    foreach ( array(
                        'none'     => esc_html__( 'None', 'mailchimp-for-woocommerce' ),
                        'debug'    => esc_html__( 'Debug', 'mailchimp-for-woocommerce' ),
                        'standard' => esc_html__( 'Standard', 'mailchimp-for-woocommerce' ),
                        'enhanced' => esc_html__( 'Enhanced', 'mailchimp-for-woocommerce' ),
                    ) as $log_value => $log_label ) {
                        echo '<option value="' . esc_attr( $log_value ) . '" ' . selected( $log_value === $logging_preference, true, false ) . '>' . esc_html( $log_label ) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <div class="mc-wc-tab-content-box">
        <div class="mc-wc-logs-recent-list">
            <p><?php esc_html_e('Recent logs', 'mailchimp-for-woocommerce' ); ?></p>
            <div class="mc-wc-logs-recent-list-wrapper">

                <div class="mc-wc-logs-navigation">
                    <div class="mc-wc-logs-filters">
                        <label>
                            <span><?php esc_html_e('Level', 'mailchimp-for-woocommerce'); ?></span>
                            <select class="mailchimp-not-option mc-wc-select-not-bold" id="mailchimp-log-level-filter">
                                <?php $logging_preference = mailchimp_environment_variables()->logging; ?>
                                <?php
                                foreach ( array(
                                              null       => esc_html__( 'All', 'mailchimp-for-woocommerce' ),
                                              'debug'    => esc_html__( 'Debug', 'mailchimp-for-woocommerce' ),
                                              'info'     => esc_html__( 'Info', 'mailchimp-for-woocommerce' ),
                                              'enhanced' => esc_html__( 'Enhanced', 'mailchimp-for-woocommerce' ),
                                          ) as $log_level => $log_label ) {
                                    echo '<option value="' . esc_attr( $log_level ) . '" ' . selected( $log_level === $filters['level'], true, false ) . '>' . esc_html( $log_label ) . '</option>';
                                }
                                ?>
                            </select>
                        </label>

                        <div class="mc-wc-date-filters">
                            <label>
                                <span><?php esc_html_e('From date', 'mailchimp-for-woocommerce'); ?></span>
                                <input type="date" id="mailchimp-date-from-filter" class="mailchimp-not-option" value="<?php echo esc_attr($filters['from_date']); ?>" />
                            </label>
                            <label>
                                <span><?php esc_html_e('To date', 'mailchimp-for-woocommerce'); ?></span>
                                <input type="date" id="mailchimp-date-to-filter" class="mailchimp-not-option" value="<?php echo esc_attr($filters['from_date']); ?>" />
                            </label>
                        </div>

                        <a href="#" id="mc-filter-logs" class="mc-wc-btn mc-wc-btn-primary no-linear-gradient">
                            <?php esc_html_e('Filter', 'mailchimp-for-woocommerce'); ?>
                        </a>
                    </div>

                    <a href="#" id="mc-logs-clear" class="mc-wc-btn mc-wc-btn-2-outline no-linear-gradient">
                        <?php esc_html_e('Clear all', 'mailchimp-for-woocommerce'); ?>
                        <span class="mc-wc-loading" style="display: none">
                            <svg class="animate-spin" width="14" height="14" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </a>
                </div>
                <div class="mc-wc-logs-recent-list-show">
                    <?php if ( isset( $logs ) ) : ?>
                        <div id="log-viewer">
                        <span class="spinner" style="display:none;"></span>
                            <table>
                                <thead>
                                    <tr>
                                        <th>
                                            <?php esc_html_e( 'Level', 'woocommerce' ); ?>
                                        </th>
                                        <th>
                                            <?php esc_html_e( 'Date', 'woocommerce' ); ?>
                                        </th>
                                        <th>
                                            <?php esc_html_e( 'Action', 'woocommerce' ); ?>
                                        </th>
                                        <th>
                                            <?php esc_html_e( 'Message', 'woocommerce' ); ?>
                                        </th>
                                        <th>
                                            <?php esc_html_e( 'Data', 'woocommerce' ); ?>
                                        </th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $logs['data'] as $log ) : ?>
                                        <tr>
                                            <td>
                                                <?php if ($log['level'] === 'debug') : ?>
                                                    <svg style="color: gray" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve">
                                                        <path fill="currentColor" d="M500.633,211.454l-58.729-14.443c-3.53-11.133-8.071-21.929-13.55-32.256c8.818-14.678,27.349-45.571,27.349-45.571
                                                            c3.545-5.903,2.607-13.462-2.256-18.325l-42.422-42.422c-4.863-4.878-12.407-5.815-18.325-2.256L347.055,83.53
                                                            c-10.269-5.435-21.006-9.932-32.065-13.433l-14.443-58.729C298.876,4.688,292.885,0,286,0h-60
                                                            c-6.885,0-12.891,4.688-14.546,11.367c0,0-10.005,40.99-14.429,58.715c-11.792,3.735-23.188,8.584-34.043,14.502l-47.329-28.403
                                                            c-5.918-3.516-13.447-2.607-18.325,2.256l-42.422,42.422c-4.863,4.863-5.801,12.422-2.256,18.325l29.268,48.882
                                                            c-4.717,9.302-8.672,18.984-11.821,28.901l-58.729,14.487C4.688,213.124,0,219.115,0,226v60c0,6.885,4.688,12.891,11.367,14.546
                                                            l58.744,14.443c3.56,11.294,8.188,22.266,13.799,32.798l-26.191,43.652c-3.545,5.903-2.607,13.462,2.256,18.325l42.422,42.422
                                                            c4.849,4.849,12.407,5.771,18.325,2.256c0,0,29.37-17.607,43.755-26.221c10.415,5.552,21.313,10.137,32.549,13.696l14.429,58.715
                                                            C213.109,507.313,219.115,512,226,512h60c6.885,0,12.876-4.688,14.546-11.367l14.429-58.715
                                                            c11.558-3.662,22.69-8.394,33.281-14.136c14.78,8.862,44.443,26.66,44.443,26.66c5.903,3.53,13.462,2.622,18.325-2.256
                                                            l42.422-42.422c4.863-4.863,5.801-12.422,2.256-18.325l-26.968-44.927c5.317-10.093,9.727-20.654,13.169-31.523l58.729-14.443
                                                            C507.313,298.876,512,292.885,512,286v-60C512,219.115,507.313,213.124,500.633,211.454z M256,361c-57.891,0-105-47.109-105-105
                                                            s47.109-105,105-105s105,47.109,105,105S313.891,361,256,361z"></path>
                                                </svg>
                                                <?php elseif ($log['level'] === 'enhanced') : ?>
                                                    <svg height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg" color="#674399">
                                                        <path fill="currentColor" d="m310.81 311.2c-23.66-3.72-40.29-25.61-37.65-48.81a41.62 41.62 0 0 1 2.13-9.22c2.73-7.88-.08-17-6.84-22.68a174.36 174.36 0 0 0 -48-29.42 18.63 18.63 0 0 0 -22.12 5.93 41.16 41.16 0 0 1 -58.55 7.31 40.4 40.4 0 0 1 -7.06-7.31 18.63 18.63 0 0 0 -22.09-5.93 174 174 0 0 0 -44.69 26.58c-7.1 5.77-10.07 15.22-7.18 23.36 8.06 21.39-2.82 46.46-24.94 55.6a45 45 0 0 1 -12.34 3.17 21.21 21.21 0 0 0 -18.15 16.22 136.26 136.26 0 0 0 -1.06 54.71 21 21 0 0 0 18.23 16.8c23.67 2.49 41.27 23.41 39.76 46.69a41.25 41.25 0 0 1 -3.26 13.6c-3.5 8.08-.79 17.89 6.44 23.86a175.2 175.2 0 0 0 47.58 29 20.87 20.87 0 0 0 6.72 1.38 18.49 18.49 0 0 0 15.78-8.3 41.06 41.06 0 0 1 67.57-1.15 18.55 18.55 0 0 0 22.91 6.17 178.24 178.24 0 0 0 43.72-27.54c6.89-5.82 9.67-15.13 6.75-23.11-8.32-21.27 2.28-46.44 24.3-55.8a45.18 45.18 0 0 1 11.66-3.21 21.06 21.06 0 0 0 17.57-16.48 138.79 138.79 0 0 0 3.07-26.89 137.08 137.08 0 0 0 -3-28.36 20.87 20.87 0 0 0 -17.26-16.17zm-90.11 44.53a55.18 55.18 0 1 1 -55.18-55.17 55.47 55.47 0 0 1 55.18 55.17z"></path>
                                                        <path fill="currentColor" d="m495.69 89.93c-19.07-3-32.48-20.64-30.34-39.34a32.63 32.63 0 0 1 1.71-7.43c2.2-6.35-.06-13.68-5.51-18.28a140.17 140.17 0 0 0 -38.72-23.71 15 15 0 0 0 -17.83 4.83 33.19 33.19 0 0 1 -47.19 5.89 33.05 33.05 0 0 1 -5.66-5.89 15 15 0 0 0 -17.8-4.78 139.93 139.93 0 0 0 -36 21.42c-5.72 4.65-8.12 12.27-5.79 18.83 6.5 17.23-2.27 37.44-20.1 44.81a36.64 36.64 0 0 1 -9.95 2.56 17.08 17.08 0 0 0 -14.62 13 109.7 109.7 0 0 0 -.86 44.1 17 17 0 0 0 14.7 13.54c19.07 2 33.26 18.88 32 37.65a33.36 33.36 0 0 1 -2.6 11c-2.82 6.52-.63 14.42 5.2 19.23a141.17 141.17 0 0 0 38.34 23.35 17 17 0 0 0 5.42 1.11 14.93 14.93 0 0 0 12.72-6.69 33.09 33.09 0 0 1 54.46-.93 15 15 0 0 0 18.41 5 143.87 143.87 0 0 0 35.26-22.2c5.55-4.69 7.79-12.2 5.43-18.63-6.7-17.13 1.84-37.42 19.59-45a36.66 36.66 0 0 1 9.4-2.59 17 17 0 0 0 14.17-13.28 111.67 111.67 0 0 0 2.47-21.68 110.94 110.94 0 0 0 -2.4-22.82 16.84 16.84 0 0 0 -13.91-13zm-72.63 35.89a44.47 44.47 0 1 1 -44.47-44.47 44.7 44.7 0 0 1 44.47 44.47z"></path>
                                                        <path fill="currentColor" d="m508.05 372.9a23.75 23.75 0 0 1 -3.4-32.47 22 22 0 0 1 3.53-3.56 11.17 11.17 0 0 0 3.22-12.13 92.18 92.18 0 0 0 -13.47-26.62 9.87 9.87 0 0 0 -11.61-3.47 21.78 21.78 0 0 1 -28.42-13 21.42 21.42 0 0 1 -1.15-5.25 9.87 9.87 0 0 0 -8.31-8.82 92.28 92.28 0 0 0 -27.54-.45 11.32 11.32 0 0 0 -9.73 8.54c-2.31 11.88-14.19 20.16-26.71 18.12a23.29 23.29 0 0 1 -6.44-2 11.24 11.24 0 0 0 -12.69 2.26 72.15 72.15 0 0 0 -15.69 24.37 11.12 11.12 0 0 0 3.55 12.64 23.6 23.6 0 0 1 4.93 32.11 21.69 21.69 0 0 1 -5.24 5.23 11 11 0 0 0 -3.73 12.6 92.82 92.82 0 0 0 13.4 26.29 11.13 11.13 0 0 0 2.64 2.49 9.8 9.8 0 0 0 9.42.65 21.76 21.76 0 0 1 30.79 18.27 9.82 9.82 0 0 0 8.56 9.15 94.46 94.46 0 0 0 27.37-.25 11.21 11.21 0 0 0 9.47-8.6c2.16-11.89 13.94-20.3 26.47-18.39a23.39 23.39 0 0 1 6.15 1.8 11.16 11.16 0 0 0 12.51-2.55 73.18 73.18 0 0 0 8.86-11.26 72.34 72.34 0 0 0 6.54-13.6 11.06 11.06 0 0 0 -3.28-12.1zm-53-5a29.23 29.23 0 1 1 -9.53-40.22 29.39 29.39 0 0 1 9.53 40.24z"></path>
                                                    </svg>
                                                <?php elseif ($log['level'] === 'error') : ?>
                                                    <svg width="30" height="30" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: red; transform: rotate(45deg)">
                                                        <path d="M6.25 10C6.25 9.80109 6.32902 9.61032 6.46967 9.46967C6.61032 9.32902 6.80109 9.25 7 9.25H9.25V7C9.25 6.80109 9.32902 6.61032 9.46967 6.46967C9.61032 6.32902 9.80109 6.25 10 6.25C10.1989 6.25 10.3897 6.32902 10.5303 6.46967C10.671 6.61032 10.75 6.80109 10.75 7V9.25H13C13.1989 9.25 13.3897 9.32902 13.5303 9.46967C13.671 9.61032 13.75 9.80109 13.75 10C13.75 10.1989 13.671 10.3897 13.5303 10.5303C13.3897 10.671 13.1989 10.75 13 10.75H10.75V13C10.75 13.1989 10.671 13.3897 10.5303 13.5303C10.3897 13.671 10.1989 13.75 10 13.75C9.80109 13.75 9.61032 13.671 9.46967 13.5303C9.32902 13.3897 9.25 13.1989 9.25 13V10.75H7C6.80109 10.75 6.61032 10.671 6.46967 10.5303C6.32902 10.3897 6.25 10.1989 6.25 10Z" fill="currentColor"/>
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10 17C10.9193 17 11.8295 16.8189 12.6788 16.4672C13.5281 16.1154 14.2997 15.5998 14.9497 14.9497C15.5998 14.2997 16.1154 13.5281 16.4672 12.6788C16.8189 11.8295 17 10.9193 17 10C17 9.08075 16.8189 8.1705 16.4672 7.32122C16.1154 6.47194 15.5998 5.70026 14.9497 5.05025C14.2997 4.40024 13.5281 3.88463 12.6788 3.53284C11.8295 3.18106 10.9193 3 10 3C8.14348 3 6.36301 3.7375 5.05025 5.05025C3.7375 6.36301 3 8.14348 3 10C3 11.8565 3.7375 13.637 5.05025 14.9497C6.36301 16.2625 8.14348 17 10 17ZM10 15.5C11.4587 15.5 12.8576 14.9205 13.8891 13.8891C14.9205 12.8576 15.5 11.4587 15.5 10C15.5 8.54131 14.9205 7.14236 13.8891 6.11091C12.8576 5.07946 11.4587 4.5 10 4.5C8.54131 4.5 7.14236 5.07946 6.11091 6.11091C5.07946 7.14236 4.5 8.54131 4.5 10C4.5 11.4587 5.07946 12.8576 6.11091 13.8891C7.14236 14.9205 8.54131 15.5 10 15.5Z" fill="currentColor"/>
                                                    </svg>
                                                    <svg height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g id="Layer_2" data-name="Layer 2">
                                                            <circle id="background" cx="256" cy="256" fill="#f44336" r="256"></circle>
                                                            <path d="m348.6 391a42.13 42.13 0 0 1 -30-12.42l-62.6-62.58-62.6 62.61a42.41 42.41 0 1 1 -60-60l62.6-62.61-62.61-62.6a42.41 42.41 0 0 1 60-60l62.61 62.6 62.6-62.61a42.41 42.41 0 1 1 60 60l-62.6 62.61 62.61 62.6a42.41 42.41 0 0 1 -30 72.4z" fill="#fff"></path>
                                                    </svg>
                                                <?php else : ?>
                                                    <svg style="color: blue" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve">
                                                        <path fill="currentColor" d="M256,0C114.509,0,0,114.496,0,256c0,141.489,114.496,256,256,256c141.491,0,256-114.496,256-256
                                                            C512,114.511,397.504,0,256,0z M282.289,357.621c0,8.088-11.794,16.174-26.284,16.174c-15.164,0-25.946-8.086-25.946-16.174
                                                            V229.234c0-9.435,10.783-15.839,25.946-15.839c14.49,0,26.284,6.404,26.284,15.839V357.621z M256.006,182.396
                                                            c-15.501,0-27.631-11.457-27.631-24.263c0-12.805,12.131-23.925,27.631-23.925c15.164,0,27.296,11.12,27.296,23.925
                                                            C283.302,170.939,271.169,182.396,256.006,182.396z"></path>
                                                    </svg>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $log['created_at']; ?></td>
                                            <td><?php echo $log['action']; ?></td>
                                            <td><?php echo $log['message']; ?></td>
                                            <td>
                                                <div class="logs-actions">
                                                    <a href="#" class="js-mailchimp-woocommerce-view-log-data" data-log-id="<?php echo esc_attr($log['id']); ?>">
                                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M13 10C13 10.7956 12.6839 11.5587 12.1213 12.1213C11.5587 12.6839 10.7956 13 10 13C9.20435 13 8.44129 12.6839 7.87868 12.1213C7.31607 11.5587 7 10.7956 7 10C7 9.20435 7.31607 8.44129 7.87868 7.87868C8.44129 7.31607 9.20435 7 10 7C10.7956 7 11.5587 7.31607 12.1213 7.87868C12.6839 8.44129 13 9.20435 13 10ZM11.5 10C11.5 10.3978 11.342 10.7794 11.0607 11.0607C10.7794 11.342 10.3978 11.5 10 11.5C9.60218 11.5 9.22064 11.342 8.93934 11.0607C8.65804 10.7794 8.5 10.3978 8.5 10C8.5 9.60218 8.65804 9.22064 8.93934 8.93934C9.22064 8.65804 9.60218 8.5 10 8.5C10.3978 8.5 10.7794 8.65804 11.0607 8.93934C11.342 9.22064 11.5 9.60218 11.5 10Z" fill="currentColor"/>
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10 4C7.524 4 5.652 5.23 4.423 6.532C3.87573 7.10988 3.40519 7.75587 3.023 8.454C2.87934 8.71701 2.75566 8.99045 2.653 9.272C2.571 9.499 2.5 9.76 2.5 10C2.5 10.24 2.571 10.501 2.652 10.728C2.74 10.974 2.865 11.252 3.023 11.546C3.34 12.133 3.807 12.816 4.423 13.468C5.652 14.77 7.523 16 10 16C12.476 16 14.348 14.77 15.577 13.468C16.1243 12.8901 16.5948 12.2441 16.977 11.546C17.1207 11.283 17.2443 11.0096 17.347 10.728C17.429 10.501 17.5 10.24 17.5 10C17.5 9.76 17.429 9.499 17.348 9.272C17.245 8.99042 17.121 8.71699 16.977 8.454C16.5948 7.75588 16.1242 7.1099 15.577 6.532C14.348 5.23 12.477 4 10 4ZM4.001 10.002V9.998C4.005 9.978 4.018 9.908 4.065 9.775C4.14246 9.56572 4.23539 9.36249 4.343 9.167C4.66271 8.58414 5.05593 8.04473 5.513 7.562C6.555 6.458 8.058 5.5 10 5.5C11.942 5.5 13.445 6.458 14.486 7.562C14.9431 8.04475 15.3363 8.58415 15.656 9.167C15.786 9.407 15.877 9.614 15.935 9.775C15.982 9.907 15.995 9.978 15.999 9.998V10.002C15.995 10.022 15.982 10.092 15.935 10.225C15.8575 10.4343 15.7646 10.6375 15.657 10.833C15.3373 11.4159 14.9441 11.9553 14.487 12.438C13.445 13.542 11.942 14.5 10 14.5C8.058 14.5 6.555 13.542 5.514 12.438C5.05691 11.9553 4.6637 11.4159 4.344 10.833C4.23604 10.6375 4.14279 10.4343 4.065 10.225C4.018 10.093 4.005 10.022 4.001 10.002Z" fill="currentColor"/>
                                                        </svg>
                                                    </a>
                                                    <a href="#" class="js-mailchimp-woocommerce-copy-data" data-log-id="<?php echo esc_attr($log['id']); ?>">
                                                        <span class="clipboard">
                                                            <svg width="20" height="20" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M7 15H14" stroke="#7C7C7C" stroke-linecap="round"/>
                                                                <path d="M7 12H14" stroke="#7C7C7C" stroke-linecap="round"/>
                                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.25 2.79175C4.2835 2.79175 3.5 3.50063 3.5 4.37508V18.6251C3.5 19.4995 4.2835 20.2084 5.25 20.2084H15.75C16.7165 20.2084 17.5 19.4995 17.5 18.6251V4.37508C17.5 3.50063 16.7165 2.79175 15.75 2.79175H5.25ZM7.875 4.37508H5.25V18.6251H15.75V4.37508H13.125V5.16675C13.125 5.60397 12.7332 5.95842 12.25 5.95842H8.75C8.26675 5.95842 7.875 5.60397 7.875 5.16675V4.37508Z" fill="#7C7C7C"/>
                                                                <mask id="path-4-inside-1_4285_9690" fill="white">
                                                                    <rect x="6" width="9" height="4" rx="1"/>
                                                                </mask>
                                                                <rect x="6" width="9" height="4" rx="1" stroke="#7C7C7C" stroke-width="2.8" mask="url(#path-4-inside-1_4285_9690)"/>
                                                                <path d="M7 9H14" stroke="#7C7C7C" stroke-linecap="round"/>
                                                            </svg>
                                                        </span>
                                                        <span class="dashicons dashicons-yes yes" style="display: none;color: green;"></span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <tr data-log-id="<?php echo esc_attr($log['id']); ?>" style="display: none">
                                        <td colspan="5">
                                            <textarea id="log-content-<?php echo esc_attr($log['id']); ?>" readonly class="logs-data"><?php print_r($log['data']); ?></textarea>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <div class="mc-wc-log-pagination-wrap">
                                <div class="mc-wc-log-pagination">
                                    <a href="#" class="js-mailchimp-woocommerce-prev"  <?php if (intval($page) === 1) echo 'disabled'; ?>>
                                        <svg viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg">
                                            <path d="m390.627 54.627-201.372 201.373 201.372 201.373a32 32 0 1 1 -45.254 45.254l-224-224a32 32 0 0 1 0-45.254l224-224a32 32 0 0 1 45.254 45.254z"></path>
                                        </svg>
                                    </a>
                                    <a href="#" class="js-mailchimp-woocommerce-next" <?php if ($page >= intval($logs['pages'])) echo 'disabled'; ?>>
                                        <svg viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg">
                                            <path d="m121.373 457.373 201.372-201.373-201.372-201.373a32 32 0 0 1 45.254-45.254l224 224a32 32 0 0 1 0 45.254l-224 224a32 32 0 0 1 -45.254-45.254z"></path>
                                        </svg>
                                    </a>
                                </div>

                                <div class="">
                                    <input class="mailchimp-not-option" type="number" value="<?php echo esc_attr($logs['current_page']); ?>" id="mc-log-current-page" max="<?php echo esc_attr($logs['pages']); ?>"/>
                                    <span>/</span>
                                    <span><?php echo $logs['pages']; ?></span>
                                </div>

                                <select class="mc-wc-select-not-bold mailchimp-not-option" id="mailchimp-log-per-page">
                                    <?php
                                    foreach ( array(10, 20, 50, 100) as $page_count) {
                                        echo '<option value="' . esc_attr( $page_count ) . '" ' . selected( $page_count === intval($per_page), true, false ) . '>' . esc_html( $page_count ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>


                    <?php else : ?>
                        <div class="updated woocommerce-message inline">
                            <p>
                                <?php esc_html_e( 'There are currently no logs to view.', 'woocommerce' ); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
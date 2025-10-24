<?php

if ( ! class_exists( 'Mailchimp_Woocommerce_DB_Logger' ) ) {
    class Mailchimp_Woocommerce_DB_Logger
    {

        /**
         * WordPress DB instance.
         */
        protected $wpdb;

        /**
         * Table name.
         */
        protected $table;

        /**
         * Allowed log levels.
         */
        protected $allowed_levels = [ 'info', 'debug', 'enhanced', 'error' ];

        /**
         * Constructor.
         */
        public function __construct() {
            global $wpdb;
            $this->wpdb  = $wpdb;
            $this->table = $wpdb->prefix . 'mailchimp_logs';
        }

        /**
         * Insert a new log entry.
         */
        public function insert( $level = 'info', $action = '', $message = '', $data = [] ) {
            $level = $this->validate_level( $level );

            $this->wpdb->insert(
                $this->table,
                [
                    'level'   => $level,
                    'action'  => sanitize_text_field( $action ),
                    'message' => wp_kses_post( $message ),
                    'data'    => maybe_serialize( $data ),
                ],
                [ '%s', '%s', '%s', '%s' ]
            );

            return $this->wpdb->insert_id;
        }

        /**
         * Update an existing log.
         */
        public function update( $id, $fields = [] ) {
            if ( empty( $fields ) || ! is_array( $fields ) ) {
                return false;
            }

            if ( isset( $fields['level'] ) ) {
                $fields['level'] = $this->validate_level( $fields['level'] );
            }

            if ( isset( $fields['data'] ) ) {
                $fields['data'] = maybe_serialize( $fields['data'] );
            }

            return $this->wpdb->update(
                $this->table,
                $fields,
                [ 'id' => intval( $id ) ],
                null,
                [ '%d' ]
            );
        }

        /**
         * Get one log by ID.
         */
        public function get( $id ) {
            $row = $this->wpdb->get_row(
                $this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", intval( $id ) ),
                ARRAY_A
            );

            if ( ! $row ) {
                return null;
            }

            $row['data'] = maybe_unserialize( $row['data'] );
            return $row;
        }

        /**
         * @param $args
         * @param $params
         * @return string
         *
         * Building query for pagination
         */
        protected function build_where_clause( $args, &$params ) {
            $where = '1=1';

            // Filter by level
            if ( ! empty( $args['level'] ) ) {
                $where .= ' AND level = %s';
                $params[] = $this->validate_level( $args['level'] );
            }

            // Filter by action
            if ( ! empty( $args['action'] ) ) {
                $where .= ' AND action = %s';
                $params[] = sanitize_text_field( $args['action'] );
            }

            // Handle default from_date = start of today
            if ( !empty( $args['from_date'] ) ) {
                $args['from_date'] = date( 'Y-m-d 00:00:00', strtotime( $args['from_date'] ) );
                $where .= ' AND created_at >= %s';
                $params[] = $args['from_date'];
            }

            // Optional to_date
            if ( ! empty( $args['to_date'] ) ) {
                $where .= ' AND created_at <= %s';
                $params[] = date( 'Y-m-d 23:59:59', strtotime( $args['to_date'] ) );
            }

            return $where;
        }

        /**
         * Get all logs (optionally filtered).
         */
        public function all( $args = [] ) {
            $defaults = [
                'level'     => null,
                'action'    => null,
                'from_date' => null,
                'to_date'   => null,
                'limit'     => 50,
                'offset'    => 0,
                'order'     => 'DESC',
            ];

            $args = wp_parse_args( $args, $defaults );
            $params = [];

            $where = $this->build_where_clause( $args, $params );

            $sql = "SELECT * FROM {$this->table} WHERE $where ORDER BY created_at {$args['order']} LIMIT %d OFFSET %d";
            $params[] = intval( $args['limit'] );
            $params[] = intval( $args['offset'] );

            $prepared_sql = $this->wpdb->prepare( $sql, ...$params );
            $rows = $this->wpdb->get_results( $prepared_sql, ARRAY_A );

            foreach ( $rows as &$row ) {
                $row['data'] = maybe_unserialize( $row['data'] );
            }

            return $rows;
        }

        /**
         * Count total logs (for pagination).
         */
        public function count( $args = [] ) {
            $params = [];
            $where = $this->build_where_clause( $args, $params );

            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE $where";
            $prepared_sql = $this->wpdb->prepare( $sql, ...$params );

            return (int) $this->wpdb->get_var( $prepared_sql );
        }

        /**
         * Get paginated logs.
         *
         * @param int $page  Current page number (1-based).
         * @param int $per_page Number of records per page.
         * @param array $filters Optional filters (level, action).
         *
         * @return array {
         *     @type array $logs  Array of log records.
         *     @type int   $total Total number of logs.
         *     @type int   $pages Total number of pages.
         * }
         */
        public function paginate( $page = 1, $per_page = 20, $filters = [] ) {
            $page     = max( 1, intval( $page ) );
            $per_page = max( 1, intval( $per_page ) );
            $offset   = ( $page - 1 ) * $per_page;

            $total = $this->count( $filters );
            $logs  = $this->all( array_merge( $filters, [
                'limit'  => $per_page,
                'offset' => $offset,
            ] ) );

            return [
                'data'  => $logs,
                'total' => $total,
                'current_page' => $page,
                'pages' => ceil( $total / $per_page ),
            ];
        }

        /**
         * Validate and normalize log level.
         */
        protected function validate_level( $level ) {
            $level = strtolower( trim( $level ) );
            return in_array( $level, $this->allowed_levels, true ) ? $level : 'info';
        }
    }
}
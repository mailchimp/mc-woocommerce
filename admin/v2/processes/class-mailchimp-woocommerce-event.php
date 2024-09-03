<?php


class Mailchimp_Woocommerce_Event
{
    // we need to do some more stuff on this before pushing
    protected static $prevent = true;

    protected $event = '';
    protected $title = '';
    protected $integration_name = 'woocommerce';
    protected $client_id = '366074603122';
    protected $org = 'sbseg';
    protected $purpose = 'prod';
    protected $scope = 'mc';
    protected $initiative_name = 'poppin_smu';
    protected $scope_area = 'signup';
    protected $screen = '';
    protected $object = 'account';
    protected $object_detail = 'account_signup';
    protected $action = 'selected';
    protected $ui_object = 'button';
    protected $ui_object_detail = 'activate';
    protected $ui_action = 'clicked';
    protected $ui_access_point = 'center';
    protected $event_params = [];
    protected $last_error = null;
    /** @var null|DateTime */
    protected $date = null;
    protected $user_id = null;
    protected $login_id = null;
    protected $submitted_data = null;
    protected $entrypoint = null;

    public function __construct($mailchimp_user_id = null, $mailchimp_login_id = null)
    {
        $this->user_id = $mailchimp_user_id;
        $this->login_id = $mailchimp_login_id;
    }

    /**
     * @param string $event
     *
     * @return array|null
     */
    public static function find(string $event)
    {
        $events = mailchimp_account_events();
        return array_key_exists($event, $events) ? $events[$event] : null;
    }

    /**
     * @param string $event
     * @param DateTime|null $date
     * @return array|mixed|null
     */
    public static function track(string $event, \DateTime $date = null)
    {
        if (static::$prevent) {
            return null;
        }
        if (!($data = static::find($event)) || !is_array($data) || empty($data)) {
            mailchimp_debug('mailchimp_events', "Could not push tracking event: {$event}, not found in config.");
            return null;
        }

        if (!($mc_user_id = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-mailchimp_user_id', null))) {
            $mc_user_id = null;
        }

        if (!($mc_login_id = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-mailchimp_login_id', null))) {
            $mc_login_id = null;
        }

        if (($entrypoint = static::entrypoint($data))) {
            $data['entry_point'] = $entrypoint;
        }

        unset($data['entry']);

        $payload = (new Mailchimp_Woocommerce_Event($mc_user_id, $mc_login_id))
            ->set_date($date)
            ->configure($data);

        return static::$prevent ? $payload->compile() : $payload->handle();
    }

    /**
     * @param array $data
     * @return mixed|null
     */
    public static function entrypoint(array $data)
    {
        if (isset($data['entry']) && is_array($data['entry']) && count($data['entry']) === 2) {
            list($entry, $validate) = $data['entry'];
            if (!empty($entry) && !$validate) {
                return $entry;
            }

            // TODO some validation between entry and store.
            return $entry;
        }

        return null;
    }

    /**
     * @param \DateTime|null $date
     *
     * @return $this
     */
    public function set_date(\DateTime $date = null)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return \Exception|\Throwable
     */
    public function get_last_error()
    {
        return $this->last_error;
    }

    /**
     * @return array|mixed
     */
    public function handle()
    {
        if (static::$prevent) {
            return null;
        }

        try {
            $this->last_error = null;
            $this->submitted_data = $this->compile();

            $response = wp_remote_post($this->endpoint(), array(
                'timeout'   => 12,
                'blocking'  => true,
                'method'      => 'POST',
                'data_format' => 'body',
                'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
                'body'        => json_encode($this->submitted_data),
            ));

            if ( $response instanceof WP_Error ) {
                mailchimp_error( 'mailchimp_events',"Could not post event data to mailchimp beacon.", $response );

                return $response;
            }

            return json_decode( $response['body'] );
        } catch (\Throwable $e) {
            mailchimp_error( 'mailchimp_events',"Could not post event data to mailchimp beacon.", $e->getMessage() );
            $this->last_error = $e;
            return null;
        }
    }

    /**
     * @return string
     */
    public function endpoint()
    {
        return 'https://beacon.mailchimp.com/external/integration/v2/woocommerce';
    }

    public function get_submitted_data()
    {
        return $this->submitted_data;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function configure(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function compile()
    {
        $format = 'Y-m-d H:i:s';
        $current = new DateTime('now');
        $payload = array(
            'timestamp' => $current->format($format),
            'sentAt' => $this->date ? $this->date->format($format) : $current->format($format),
            'context' => array(
                'internal_mc_user' => false,
                'user_id' => $this->user_id ? $this->user_id : '',
                'login_id' => $this->login_id ? $this->login_id : '',
                'company_id' => '',
                'pseudonym_id' => '',
            ),
            'properties' => array_merge(array(
                'org' => $this->org,
                'purpose' => $this->purpose,
                'scope' => $this->scope,
                'initiative_name' => $this->initiative_name,
                'scope_area' => $this->scope_area,
                'screen' => $this->screen, // make this configurable ( or mandatory )
                'object' => $this->object,
                'object_detail' => $this->object_detail,
                'action' => $this->action,
                'ui_object' => $this->ui_object,
                'ui_object_detail' => $this->ui_object_detail,
                'ui_action' => $this->ui_action,
                'ui_access_point' => $this->ui_access_point,
                'integration_name' => $this->integration_name,
                'integration_id' => $this->client_id,
            ), $this->event_params),
        );

        $payload['event'] = $payload['properties']['object'] === '' ? $payload['properties']['action'] : $payload['properties']['object'] . ':' . $payload['properties']['action'];

        if (empty($this->user_id)) {
            mailchimp_log('mailchimp_events', "mailchimp beacon :: sending anonymous event");
            // remove the user id and login id if we don't have this info yet.
            unset($payload['context']['user_id'], $payload['context']['login_id']);
            $payload['properties']['user_id_required'] = false;
        }

        if (!empty($this->entrypoint)) {
            $payload['properties']['entry_point'] = $this->entrypoint;
        }

        mailchimp_debug('mailchimp_events', "mailchimp beacon tracking payload {$this->event}", $payload);

        return $payload;
    }

    public function set_mailchimp_user_id($id)
    {
        $this->user_id = $id;
        return $this;
    }

    public function set_mailchimp_login_id($id)
    {
        $this->login_id = $id;
        return $this;
    }

    /**
     * @param string $org
     * @return $this
     */
    public function set_org(string $org)
    {
        $this->org = $org;

        return $this;
    }

    /**
     * @param string $purpose
     * @return $this
     */
    public function set_purpose(string $purpose)
    {
        $this->purpose = $purpose;

        return $this;
    }

    /**
     * @param string $scope
     * @return $this
     */
    public function set_scope(string $scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @param string $initiative_name
     * @return $this
     */
    public function set_initiative_name(string $initiative_name)
    {
        $this->initiative_name = $initiative_name;

        return $this;
    }

    /**
     * @param string $scope_area
     * @return $this
     */
    public function set_scope_area(string $scope_area)
    {
        $this->scope_area = $scope_area;

        return $this;
    }

    /**
     * @param string $screen
     * @return $this
     */
    public function set_screen(string $screen)
    {
        $this->screen = $screen;

        return $this;
    }

    /**
     * @param string $object
     * @return $this
     */
    public function set_object(string $object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @param string $object_detail
     * @return $this
     */
    public function set_object_detail(string $object_detail)
    {
        $this->object_detail = $object_detail;

        return $this;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function set_action(string $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @param string $ui_object
     * @return $this
     */
    public function set_ui_object(string $ui_object)
    {
        $this->ui_object = $ui_object;

        return $this;
    }

    /**
     * @param string $ui_object_detail
     * @return $this
     */
    public function set_ui_object_detail(string $ui_object_detail)
    {
        $this->ui_object_detail = $ui_object_detail;

        return $this;
    }

    /**
     * @param string $ui_action
     * @return $this
     */
    public function set_ui_action(string $ui_action)
    {
        $this->ui_action = $ui_action;

        return $this;
    }

    /**
     * @param string $ui_access_point
     * @return $this
     */
    public function set_ui_access_point(string $ui_access_point)
    {
        $this->ui_access_point = $ui_access_point;

        return $this;
    }
}
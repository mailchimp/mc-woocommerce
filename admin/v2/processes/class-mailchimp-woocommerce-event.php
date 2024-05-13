<?php


class Mailchimp_Woocommerce_Event
{
    protected $event = '';
    protected $title = '';
    protected $integration_name = 'woocommerce';
    protected $client_id = 'CLIENT_ID_COMING_SOON';
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
    protected $date = null;
    protected $user_id = null;
    protected $login_id = null;
    protected $submitted_data = null;
    protected $entrypoint = null;

    public function __construct(string|null $mailchimp_user_id = null, string|null $mailchimp_login_id = null)
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
     * @param \DateTime|null $date
     * @param bool $fake
     *
     * @return array|mixed|null
     */
    public static function track(string $event, \DateTime $date = null, bool $fake = false)
    {
        if (!($data = static::find($event)) || !is_array($data) || empty($data)) {
            mailchimp_debug('mailchimp_events', "Could not push tracking event: {$event}, not found in config.");
            return null;
        }

        // TODO find the proper function in plugin.
        if (!($mc_user_id = get_option('mailchimp_user_id', null))) {
            $mc_user_id = null;
        }

        if (!($mc_login_id = mailchimp_get_store_id())) {
            $mc_login_id = null;
        }

        if (($entrypoint = static::entrypoint($data))) {
            $data['entry_point'] = $entrypoint;
        }

        unset($data['entry']);

        $payload = (new Mailchimp_Woocommerce_Event($mc_user_id, $mc_login_id))
            ->set_date($date)
            ->configure($data);

        return $fake ? $payload->compile() : $payload->handle();
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
                wp_send_json_error( $response );
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
        // TODO replace with woocommerce endpoints.
        return match ($this->purpose) {
            "prod" => 'https://beacon.mailchimp.com/external/integration/v2/woocommerce',
            "production" => 'https://beacon.mailchimp.com/external/integration/v2/woocommerce',
            "staging" => 'https://beacon-shared.mailchimp.com/external/integration/v2/woocommerce',
            default => throw new \RuntimeException('Must set the purpose ( environment ) for events.')
        };
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
        $payload = [
            'timestamp' => now(),
            'event' => $this->event,
            'sentAt' => $this->date ?? now(),
            'context' => [
                'internal_mc_user' => false,
                'user_id' => $this->user_id ?? '',
                'login_id' => $this->login_id ?? '',
                'company_id' => '',
                'pseudonym_id' => '',
            ],
            'properties' => array_merge([
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
            ], $this->event_params),
        ];

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
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_org(string $org): Mailchimp_Woocommerce_Event
    {
        $this->org = $org;

        return $this;
    }

    /**
     * @param string $purpose
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_purpose(string $purpose): Mailchimp_Woocommerce_Event
    {
        $this->purpose = $purpose;

        return $this;
    }

    /**
     * @param string $scope
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_scope(string $scope): Mailchimp_Woocommerce_Event
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @param string $initiative_name
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_initiative_name(string $initiative_name): Mailchimp_Woocommerce_Event
    {
        $this->initiative_name = $initiative_name;

        return $this;
    }

    /**
     * @param string $scope_area
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_scope_area(string $scope_area): Mailchimp_Woocommerce_Event
    {
        $this->scope_area = $scope_area;

        return $this;
    }

    /**
     * @param string $screen
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_screen(string $screen): Mailchimp_Woocommerce_Event
    {
        $this->screen = $screen;

        return $this;
    }

    /**
     * @param string $object
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_object(string $object): Mailchimp_Woocommerce_Event
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @param string $object_detail
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_object_detail(string $object_detail): Mailchimp_Woocommerce_Event
    {
        $this->object_detail = $object_detail;

        return $this;
    }

    /**
     * @param string $action
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_action(string $action): Mailchimp_Woocommerce_Event
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @param string $ui_object
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_ui_object(string $ui_object): Mailchimp_Woocommerce_Event
    {
        $this->ui_object = $ui_object;

        return $this;
    }

    /**
     * @param string $ui_object_detail
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_ui_object_detail(string $ui_object_detail): Mailchimp_Woocommerce_Event
    {
        $this->ui_object_detail = $ui_object_detail;

        return $this;
    }

    /**
     * @param string $ui_action
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_ui_action(string $ui_action): Mailchimp_Woocommerce_Event
    {
        $this->ui_action = $ui_action;

        return $this;
    }

    /**
     * @param string $ui_access_point
     *
     * @return Mailchimp_Woocommerce_Event
     */
    public function set_ui_access_point(string $ui_access_point): Mailchimp_Woocommerce_Event
    {
        $this->ui_access_point = $ui_access_point;

        return $this;
    }
}
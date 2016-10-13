<?php namespace Frlnc\Slack;

use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;
use Frlnc\Slack\Core\Commander;

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 8/12/16
 * Time: 9:36 AM
 */
class Logger
{
    private static $instance = null;

    /**
     * @var null|Commander
     */
    private $commander = null;
    public $api_token = null;
    public $channel = null;


    /**
     * @return mixed
     */
    public static function instance()
    {
        if (empty(static::$instance)) {
            $vars = mailchimp_environment_variables();
            static::$instance = new Logger(
                (isset($vars->slack_token) ? $vars->slack_token : null),
                (isset($vars->slack_channel) ? $vars->slack_channel : null)
            );
        }
        return static::$instance;
    }

    /**
     * Logger constructor.
     * @param string $api_token
     * @param string $channel
     */
    public function __construct($api_token = null, $channel = null)
    {
        if ($api_token && $channel) {
            $this->setup($api_token, $channel);
        }
    }

    /**
     * @param $api_token
     * @param $channel
     * @return $this
     */
    public function setup($api_token, $channel)
    {
        $this->channel = $channel;
        $this->api_token = $api_token;

        $curl = new CurlInteractor;
        $curl->setResponseFactory(new SlackResponseFactory);

        $this->commander = new Commander($this->api_token, $curl);

        return $this;
    }

    /**
     * @param $message
     * @return Logger
     */
    public function notice($message)
    {
        if (empty($this->commander) || empty($this->api_token) || empty($this->channel)) {
            return $this;
        }

        try {
            $this->commander->execute('chat.postMessage', array(
                'channel' => '#'.$this->channel,
                'text'    => $message
            ));
        } catch (\Exception $e) {

        }

        return $this;
    }
}

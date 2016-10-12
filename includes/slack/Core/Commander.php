<?php namespace Frlnc\Slack\Core;

use InvalidArgumentException;
use Frlnc\Slack\Contracts\Http\Interactor;

class Commander {

    /**
     * The default command headers.
     *
     * @var array
     */
    protected static $defaultHeaders = array();

    /**
     * The commands.
     *
     * @var array
     */
    protected static $commands = array(
        'api.test' => array(
            'endpoint' => '/api.test',
            'token'    => false
        ),
        'auth.test' => array(
            'endpoint' => '/auth.test',
            'token'    => true
        ),
        'channels.archive' => array(
            'token'    => true,
            'endpoint' => '/channels.archive'
        ),
        'channels.create' => array(
            'token'    => true,
            'endpoint' => '/channels.create'
        ),
        'channels.history' => array(
            'token'    => true,
            'endpoint' => '/channels.history'
        ),
        'channels.info' => array(
            'token'    => true,
            'endpoint' => '/channels.info'
        ),
        'channels.invite' => array(
            'token'    => true,
            'endpoint' => '/channels.invite'
        ),
        'channels.join' => array(
            'token'    => true,
            'endpoint' => '/channels.join'
        ),
        'channels.kick' => array(
            'token'    => true,
            'endpoint' => '/channels.kick'
        ),
        'channels.leave' => array(
            'token'    => true,
            'endpoint' => '/channels.leave'
        ),
        'channels.list' => array(
            'token'    => true,
            'endpoint' => '/channels.list'
        ),
        'channels.mark' => array(
            'token'    => true,
            'endpoint' => '/channels.mark'
        ),
        'channels.rename' => array(
            'token'    => true,
            'endpoint' => '/channels.rename'
        ),
        'channels.setPurpose' => array(
            'token'    => true,
            'endpoint' => '/channels.setPurpose',
            'format'   => array(
                'purpose'
            ),
        ),
        'channels.setTopic' => array(
            'token'    => true,
            'endpoint' => '/channels.setTopic',
            'format'   => array(
                'topic'
            )
        ),
        'channels.unarchive' => array(
            'token'    => true,
            'endpoint' => '/channels.unarchive'
        ),
        'chat.delete' => array(
            'token'    => true,
            'endpoint' => '/chat.delete'
        ),
        'chat.postMessage' => array(
            'token'    => true,
            'endpoint' => '/chat.postMessage',
            'format'   => array(
                'text',
                'username'
            ),
        ),
        'chat.update' => array(
            'token'    => true,
            'endpoint' => '/chat.update',
            'format'   => array(
                'text'
            )
        ),
        'dnd.endDnd' => array(
            'token'    => true,
            'endpoint' => '/dnd.endDnd'
        ),
        'dnd.endSnooze' => array(
            'token'    => true,
            'endpoint' => '/dnd.endSnooze'
        ),
        'dnd.info' => array(
            'token'    => true,
            'endpoint' => '/dnd.info'
        ),
        'dnd.setSnooze' => array(
            'token'    => true,
            'endpoint' => '/dnd.setSnooze'
        ),
        'dnd.teamInfo' => array(
            'token'    => true,
            'endpoint' => '/dnd.teamInfo'
        ),
        'emoji.list' => array(
            'token'    => true,
            'endpoint' => '/emoji.list'
        ),
        'files.comments.add' => array(
            'token'    => true,
            'endpoint' => '/files.comments.add'
        ),
        'files.comments.delete' => array(
            'token'    => true,
            'endpoint' => '/files.comments.delete'
        ),
        'files.comments.edit' => array(
            'token'    => true,
            'endpoint' => '/files.comments.edit'
        ),
        'files.delete' => array(
            'token'    => true,
            'endpoint' => '/files.delete'
        ),
        'files.info' => array(
            'token'    => true,
            'endpoint' => '/files.info'
        ),
        'files.list' => array(
            'token'    => true,
            'endpoint' => '/files.list'
        ),
        'files.revokePublicURL' => array(
            'token'    => true,
            'endpoint' => '/files.revokePublicURL'
        ),
        'files.sharedPublcURL' => array(
            'token'    => true,
            'endpoint' => '/files.sharedPublcURL'
        ),
        'files.upload' => array(
            'token'    => true,
            'endpoint' => '/files.upload',
            'post'     => true,
            'headers'  => array(
                'Content-Type' => 'multipart/form-data'
            ),
            'format'   => array(
                'filename',
                'title',
                'initial_comment'
            ),
        ),
        'groups.archive' => array(
            'token'    => true,
            'endpoint' => '/groups.archive'
        ),
        'groups.close' => array(
            'token'    => true,
            'endpoint' => '/groups.close'
        ),
        'groups.create' => array(
            'token'    => true,
            'endpoint' => '/groups.create',
            'format'   => array(
                'name'
            ),
        ),
        'groups.createChild' => array(
            'token'    => true,
            'endpoint' => '/groups.createChild'
        ),
        'groups.history' => array(
            'token'    => true,
            'endpoint' => '/groups.history'
        ),
        'groups.info' => array(
            'token'    => true,
            'endpoint' => '/groups.info'
        ),
        'groups.invite' => array(
            'token'    => true,
            'endpoint' => '/groups.invite'
        ),
        'groups.kick' => array(
            'token'    => true,
            'endpoint' => '/groups.kick'
        ),
        'groups.leave' => array(
            'token'    => true,
            'endpoint' => '/groups.leave'
        ),
        'groups.list' => array(
            'token'    => true,
            'endpoint' => '/groups.list'
        ),
        'groups.mark' => array(
            'token'    => true,
            'endpoint' => '/groups.mark'
        ),
        'groups.open' => array(
            'token'    => true,
            'endpoint' => '/groups.open'
        ),
        'groups.rename' => array(
            'token'    => true,
            'endpoint' => '/groups.rename'
        ),
        'groups.setPurpose' => array(
            'token'    => true,
            'endpoint' => '/groups.setPurpose',
            'format'   => array(
                'purpose'
            ),
        ),
        'groups.setTopic' => array(
            'token'    => true,
            'endpoint' => '/groups.setTopic',
            'format'   => array(
                'topic'
            ),
        ),
        'groups.unarchive' => array(
            'token'    => true,
            'endpoint' => '/groups.unarchive'
        ),
        'im.close' => array(
            'token'    => true,
            'endpoint' => '/im.close'
        ),
        'im.history' => array(
            'token'    => true,
            'endpoint' => '/im.history'
        ),
        'im.list' => array(
            'token'    => true,
            'endpoint' => '/im.list'
        ),
        'im.mark' => array(
            'token'    => true,
            'endpoint' => '/im.mark'
        ),
        'im.open' => array(
            'token'    => true,
            'endpoint' => '/im.open'
        ),
        'mpim.close' => array(
            'token'    => true,
            'endpoint' => '/mpim.close'
        ),
        'mpmim.history' => array(
            'token'    => true,
            'endpoint' => '/mpmim.history'
        ),
        'mpim.list' => array(
            'token'    => true,
            'endpoint' => '/mpim.list'
        ),
        'mpim.mark' => array(
            'token'    => true,
            'endpoint' => '/mpim.mark'
        ),
        'mpim.open' => array(
            'token'    => true,
            'endpoint' => '/mpim.open'
        ),
        'oauth.access' => array(
            'token'    => false,
            'endpoint' => '/oauth.access'
        ),
        'pins.add' => array(
            'token'    => true,
            'endpoint' => '/pins.add'
        ),
        'pins.list' => array(
            'token'    => true,
            'endpoint' => '/pins.list'
        ),
        'pins.remove' => array(
            'token'    => true,
            'endpoint' => '/pins.remove'
        ),
        'reactions.add' => array(
            'token'    => true,
            'endpoint' => '/reactions.add'
        ),
        'reactions.get' => array(
            'token'    => true,
            'endpoint' => '/reactions.get'
        ),
        'reactions.list' => array(
            'token'    => true,
            'endpoint' => '/reactions.list'
        ),
        'reactions.remove' => array(
            'token'    => true,
            'endpoint' => '/reactions.remove'
        ),
        'rtm.start' => array(
            'token'    => true,
            'endpoint' => '/rtm.start'
        ),
        'search.all' => array(
            'token'    => true,
            'endpoint' => '/search.all'
        ),
        'search.files' => array(
            'token'    => true,
            'endpoint' => '/search.files'
        ),
        'search.messages' => array(
            'token'    => true,
            'endpoint' => '/search.messages'
        ),
        'stars.add' => array(
            'token'    => true,
            'endpoint' => '/stars.add'
        ),
        'stars.list' => array(
            'token'    => true,
            'endpoint' => '/stars.list'
        ),
        'stars.remove' => array(
            'token'    => true,
            'endpoint' => '/stars.remove'
        ),
        'team.accessLogs' => array(
            'token'    => true,
            'endpoint' => '/team.accessLogs'
        ),
        'team.info' => array(
            'token'    => true,
            'endpoint' => '/team.info'
        ),
        'team.integrationLogs' => array(
            'token'    => true,
            'endpoint' => '/team.integrationLogs'
        ),
        'usergroups.create' => array(
            'token'    => true,
            'endpoint' => '/usergroups.create'
        ),
        'usergroups.disable' => array(
            'token'    => true,
            'endpoint' => '/usergroups.disable'
        ),
        'usergroups.enable' => array(
            'token'    => true,
            'endpoint' => '/usergroups.enable'
        ),
        'usergroups.list' => array(
            'token'    => true,
            'endpoint' => '/usergroups.list'
        ),
        'usergroups.update' => array(
            'token'    => true,
            'endpoint' => '/usergroups.update'
        ),
        'usergroups.users.list' => array(
            'token'    => true,
            'endpoint' => '/usergroups.users.list'
        ),
        'usergroups.users.update' => array(
            'token'    => true,
            'endpoint' => '/usergroups.users.update'
        ),
        'users.getPresence' => array(
            'token'    => true,
            'endpoint' => '/users.getPresence'
        ),
        'users.info' => array(
            'token'    => true,
            'endpoint' => '/users.info'
        ),
        'users.list' => array(
            'token'    => true,
            'endpoint' => '/users.list'
        ),
        'users.setActive' => array(
            'token'    => true,
            'endpoint' => '/users.setActive'
        ),
        'users.setPresence' => array(
            'token'    => true,
            'endpoint' => '/users.setPresence'
        ),
        'users.admin.invite' => array(
            'token'    => true,
            'endpoint' => '/users.admin.invite'
        ),
    );

    /**
     * The base URL.
     *
     * @var string
     */
    protected static $baseUrl = 'https://slack.com/api';

    /**
     * The API token.
     *
     * @var string
     */
    protected $token;

    /**
     * The Http interactor.
     *
     * @var \Frlnc\Slack\Contracts\Http\Interactor
     */
    protected $interactor;

    /**
     * @param string $token
     * @param \Frlnc\Slack\Contracts\Http\Interactor $interactor
     */
    public function __construct($token, Interactor $interactor)
    {
        $this->token = $token;
        $this->interactor = $interactor;
    }

    /**
     * Executes a command.
     *
     * @param  string $command
     * @param  array $parameters
     * @return \Frlnc\Slack\Contracts\Http\Response
     */
    public function execute($command, array $parameters = array())
    {
        if (!isset(self::$commands[$command]))
            throw new InvalidArgumentException("The command '{$command}' is not currently supported");

        $command = self::$commands[$command];

        if ($command['token'])
            $parameters = array_merge($parameters, array('token' => $this->token));

        if (isset($command['format']))
            foreach ($command['format'] as $format)
                if (isset($parameters[$format]))
                    $parameters[$format] = self::format($parameters[$format]);

        $headers = array();
        if (isset($command['headers']))
            $headers = $command['headers'];

        $url = self::$baseUrl . $command['endpoint'];

        if (isset($command['post']) && $command['post'])
            return $this->interactor->post($url, array(), $parameters, $headers);

        return $this->interactor->get($url, $parameters, $headers);
    }

    /**
     * Sets the token.
     *
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Formats a string for Slack.
     *
     * @param  string $string
     * @return string
     */
    public static function format($string)
    {
        $string = str_replace('&', '&amp;', $string);
        $string = str_replace('<', '&lt;', $string);
        $string = str_replace('>', '&gt;', $string);

        return $string;
    }

}

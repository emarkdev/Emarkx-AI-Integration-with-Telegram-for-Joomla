<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot;

defined('TB_BASE_PATH') || define('TB_BASE_PATH', __DIR__);
defined('TB_BASE_COMMANDS_PATH') || define('TB_BASE_COMMANDS_PATH', TB_BASE_PATH . '/Commands');

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Longman\TelegramBot\Commands\Command;
use Longman\TelegramBot\Commands\SystemCommands\GenericCommand;
use Longman\TelegramBot\Entities\File;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Class Telegram
 * @package Longman\TelegramBot
 *
 * @method ServerResponse getUpdates(array $data)              Use this method to receive incoming updates using long polling (wiki). An Array of Update objects is returned.
 * @method ServerResponse setWebhook(array $data)              Use this method to specify a url and receive incoming updates via an outgoing webhook. Whenever there is an update for the bot, we will send an HTTPS POST request to the specified url, containing a JSON-serialized Update. In case of an unsuccessful request, we will give up after a reasonable amount of attempts. Returns true.
 * @method ServerResponse deleteWebhook()                      Use this method to remove webhook integration if you decide to switch back to getUpdates. Returns True on success. Requires no parameters.
 * @method ServerResponse getWebhookInfo()                     Use this method to get current webhook status. Requires no parameters. On success, returns a WebhookInfo object. If the bot is using getUpdates, will return an object with the url field empty.
 * @method ServerResponse getMe()                              A simple method for testing your bot's auth token. Requires no parameters. Returns basic information about the bot in form of a User object.
 * @method ServerResponse forwardMessage(array $data)          Use this method to forward messages of any kind. On success, the sent Message is returned.
 * @method ServerResponse sendPhoto(array $data)               Use this method to send photos. On success, the sent Message is returned.
 * @method ServerResponse sendAudio(array $data)               Use this method to send audio files, if you want Telegram clients to display them in the music player. Your audio must be in the .mp3 format. On success, the sent Message is returned. Bots can currently send audio files of up to 50 MB in size, this limit may be changed in the future.
 * @method ServerResponse sendDocument(array $data)            Use this method to send general files. On success, the sent Message is returned. Bots can currently send files of any type of up to 50 MB in size, this limit may be changed in the future.
 * @method ServerResponse sendSticker(array $data)             Use this method to send .webp stickers. On success, the sent Message is returned.
 * @method ServerResponse sendVideo(array $data)               Use this method to send video files, Telegram clients support mp4 videos (other formats may be sent as Document). On success, the sent Message is returned. Bots can currently send video files of up to 50 MB in size, this limit may be changed in the future.
 * @method ServerResponse sendAnimation(array $data)           Use this method to send animation files (GIF or H.264/MPEG-4 AVC video without sound). On success, the sent Message is returned. Bots can currently send animation files of up to 50 MB in size, this limit may be changed in the future.
 * @method ServerResponse sendVoice(array $data)               Use this method to send audio files, if you want Telegram clients to display the file as a playable voice message. For this to work, your audio must be in an .ogg file encoded with OPUS (other formats may be sent as Audio or Document). On success, the sent Message is returned. Bots can currently send voice messages of up to 50 MB in size, this limit may be changed in the future.
 * @method ServerResponse sendVideoNote(array $data)           Use this method to send video messages. On success, the sent Message is returned.
 * @method ServerResponse sendMediaGroup(array $data)          Use this method to send a group of photos or videos as an album. On success, an array of the sent Messages is returned.
 * @method ServerResponse sendLocation(array $data)            Use this method to send point on the map. On success, the sent Message is returned.
 * @method ServerResponse editMessageLiveLocation(array $data) Use this method to edit live location messages sent by the bot or via the bot (for inline bots). A location can be edited until its live_period expires or editing is explicitly disabled by a call to stopMessageLiveLocation. On success, if the edited message was sent by the bot, the edited Message is returned, otherwise True is returned.
 * @method ServerResponse stopMessageLiveLocation(array $data) Use this method to stop updating a live location message sent by the bot or via the bot (for inline bots) before live_period expires. On success, if the message was sent by the bot, the sent Message is returned, otherwise True is returned.
 * @method ServerResponse sendVenue(array $data)               Use this method to send information about a venue. On success, the sent Message is returned.
 * @method ServerResponse sendContact(array $data)             Use this method to send phone contacts. On success, the sent Message is returned.
 * @method ServerResponse sendChatAction(array $data)          Use this method when you need to tell the user that something is happening on the bot's side. The status is set for 5 seconds or less (when a message arrives from your bot, Telegram clients clear its typing status). Returns True on success.
 * @method ServerResponse getUserProfilePhotos(array $data)    Use this method to get a list of profile pictures for a user. Returns a UserProfilePhotos object.
 * @method ServerResponse getFile(array $data)                 Use this method to get basic info about a file and prepare it for downloading. For the moment, bots can download files of up to 20MB in size. On success, a File object is returned. The file can then be downloaded via the link https://api.telegram.org/file/bot<token>/<file_path>, where <file_path> is taken from the response. It is guaranteed that the link will be valid for at least 1 hour. When the link expires, a new one can be requested by calling getFile again.
 * @method ServerResponse kickChatMember(array $data)          Use this method to kick a user from a group, a supergroup or a channel. In the case of supergroups and channels, the user will not be able to return to the group on their own using invite links, etc., unless unbanned first. The bot must be an administrator in the chat for this to work and must have the appropriate admin rights. Returns True on success.
 * @method ServerResponse unbanChatMember(array $data)         Use this method to unban a previously kicked user in a supergroup or channel. The user will not return to the group or channel automatically, but will be able to join via link, etc. The bot must be an administrator for this to work. Returns True on success.
 * @method ServerResponse restrictChatMember(array $data)      Use this method to restrict a user in a supergroup. The bot must be an administrator in the supergroup for this to work and must have the appropriate admin rights. Pass True for all boolean parameters to lift restrictions from a user. Returns True on success.
 * @method ServerResponse promoteChatMember(array $data)       Use this method to promote or demote a user in a supergroup or a channel. The bot must be an administrator in the chat for this to work and must have the appropriate admin rights. Pass False for all boolean parameters to demote a user. Returns True on success.
 * @method ServerResponse exportChatInviteLink(array $data)    Use this method to export an invite link to a supergroup or a channel. The bot must be an administrator in the chat for this to work and must have the appropriate admin rights. Returns exported invite link as String on success.
 * @method ServerResponse setChatPhoto(array $data)            Use this method to set a new profile photo for the chat. Photos can't be changed for private chats. The bot must be an administrator in the chat for this to work and must have the appropriate admin rights. Returns True on success.
 * @method ServerResponse deleteChatPhoto(array $data)         Use this method to delete a chat photo. Photos can't be changed for private chats. The bot must be an administrator in the chat for this to work and must have the appropriate admin rights. Returns True on success.
 * @method ServerResponse setChatTitle(array $data)            Use this method to change the title of a chat. Titles can't be changed for private chats. The bot must be an administrator in the chat for this to work and must have the appropriate admin rights. Returns True on success.
 * @method ServerResponse setChatDescription(array $data)      Use this method to change the description of a supergroup or a channel. The bot must be an administrator in the chat for this to work and must have the appropriate admin rights. Returns True on success.
 * @method ServerResponse pinChatMessage(array $data)          Use this method to pin a message in a supergroup or a channel. The bot must be an administrator in the chat for this to work and must have the ‘can_pin_messages’ admin right in the supergroup or ‘can_edit_messages’ admin right in the channel. Returns True on success.
 * @method ServerResponse unpinChatMessage(array $data)        Use this method to unpin a message in a supergroup or a channel. The bot must be an administrator in the chat for this to work and must have the ‘can_pin_messages’ admin right in the supergroup or ‘can_edit_messages’ admin right in the channel. Returns True on success.
 * @method ServerResponse leaveChat(array $data)               Use this method for your bot to leave a group, supergroup or channel. Returns True on success.
 * @method ServerResponse getChat(array $data)                 Use this method to get up to date information about the chat (current name of the user for one-on-one conversations, current username of a user, group or channel, etc.). Returns a Chat object on success.
 * @method ServerResponse getChatAdministrators(array $data)   Use this method to get a list of administrators in a chat. On success, returns an Array of ChatMember objects that contains information about all chat administrators except other bots. If the chat is a group or a supergroup and no administrators were appointed, only the creator will be returned.
 * @method ServerResponse getChatMembersCount(array $data)     Use this method to get the number of members in a chat. Returns Int on success.
 * @method ServerResponse getChatMember(array $data)           Use this method to get information about a member of a chat. Returns a ChatMember object on success.
 * @method ServerResponse setChatStickerSet(array $data)       Use this method to set a new group sticker set for a supergroup. The bot must be an administrator in the chat for this to work and must have the appropriate admin rights. Use the field can_set_sticker_set optionally returned in getChat requests to check if the bot can use this method. Returns True on success.
 * @method ServerResponse deleteChatStickerSet(array $data)    Use this method to delete a group sticker set from a supergroup. The bot must be an administrator in the chat for this to work and must have the appropriate admin rights. Use the field can_set_sticker_set optionally returned in getChat requests to check if the bot can use this method. Returns True on success.
 * @method ServerResponse answerCallbackQuery(array $data)     Use this method to send answers to callback queries sent from inline keyboards. The answer will be displayed to the user as a notification at the top of the chat screen or as an alert. On success, True is returned.
 * @method ServerResponse answerInlineQuery(array $data)       Use this method to send answers to an inline query. On success, True is returned.
 * @method ServerResponse editMessageText(array $data)         Use this method to edit text and game messages sent by the bot or via the bot (for inline bots). On success, if edited message is sent by the bot, the edited Message is returned, otherwise True is returned.
 * @method ServerResponse editMessageCaption(array $data)      Use this method to edit captions of messages sent by the bot or via the bot (for inline bots). On success, if edited message is sent by the bot, the edited Message is returned, otherwise True is returned.
 * @method ServerResponse editMessageMedia(array $data)        Use this method to edit audio, document, photo, or video messages. On success, if the edited message was sent by the bot, the edited Message is returned, otherwise True is returned.
 * @method ServerResponse editMessageReplyMarkup(array $data)  Use this method to edit only the reply markup of messages sent by the bot or via the bot (for inline bots). On success, if edited message is sent by the bot, the edited Message is returned, otherwise True is returned.
 * @method ServerResponse deleteMessage(array $data)           Use this method to delete a message, including service messages, with certain limitations. Returns True on success.
 * @method ServerResponse getStickerSet(array $data)           Use this method to get a sticker set. On success, a StickerSet object is returned.
 * @method ServerResponse uploadStickerFile(array $data)       Use this method to upload a .png file with a sticker for later use in createNewStickerSet and addStickerToSet methods (can be used multiple times). Returns the uploaded File on success.
 * @method ServerResponse createNewStickerSet(array $data)     Use this method to create new sticker set owned by a user. The bot will be able to edit the created sticker set. Returns True on success.
 * @method ServerResponse addStickerToSet(array $data)         Use this method to add a new sticker to a set created by the bot. Returns True on success.
 * @method ServerResponse setStickerPositionInSet(array $data) Use this method to move a sticker in a set created by the bot to a specific position. Returns True on success.
 * @method ServerResponse deleteStickerFromSet(array $data)    Use this method to delete a sticker from a set created by the bot. Returns True on success.
 * @method ServerResponse sendInvoice(array $data)             Use this method to send invoices. On success, the sent Message is returned.
 * @method ServerResponse answerShippingQuery(array $data)     If you sent an invoice requesting a shipping address and the parameter is_flexible was specified, the Bot API will send an Update with a shipping_query field to the bot. Use this method to reply to shipping queries. On success, True is returned.
 * @method ServerResponse answerPreCheckoutQuery(array $data)  Once the user has confirmed their payment and shipping details, the Bot API sends the final confirmation in the form of an Update with the field pre_checkout_query. Use this method to respond to such pre-checkout queries. On success, True is returned.
 * @method ServerResponse setPassportDataErrors(array $data)   Informs a user that some of the Telegram Passport elements they provided contains errors. The user will not be able to re-submit their Passport to you until the errors are fixed (the contents of the field for which you returned the error must change). Returns True on success. Use this if the data submitted by the user doesn't satisfy the standards your service requires for any reason. For example, if a birthday date seems invalid, a submitted document is blurry, a scan shows evidence of tampering, etc. Supply some details in the error message to make sure the user knows how to correct the issues.
 * @method ServerResponse sendGame(array $data)                Use this method to send a game. On success, the sent Message is returned.
 * @method ServerResponse setGameScore(array $data)            Use this method to set the score of the specified user in a game. On success, if the message was sent by the bot, returns the edited Message, otherwise returns True. Returns an error, if the new score is not greater than the user's current score in the chat and force is False.
 * @method ServerResponse getGameHighScores(array $data)       Use this method to get data for high score tables. Will return the score of the specified user and several of his neighbors in a game. On success, returns an Array of GameHighScore objects
 */
class Telegram
{
    /**
     * Version
     *
     * @var string
     */
    protected $version = '0.55.1';

    /**
     * Telegram API key
     *
     * @var string
     */
    protected $api_key = '';

    /**
     * Telegram Bot username
     *
     * @var string
     */
    protected $bot_username = '';

    /**
     * Telegram Bot id
     *
     * @var string
     */
    protected $bot_id = '';

    /**
     * Custom commands paths
     *
     * @var array
     */
    protected $commands_paths = [];

    /**
     * Current Update object
     *
     * @var \Longman\TelegramBot\Entities\Update
     */
    protected $update;

    /**
     * Upload path
     *
     * @var string
     */
    protected $upload_path;

    /**
     * Download path
     *
     * @var string
     */
    protected $download_path;

    /**
     * MySQL integration
     *
     * @var boolean
     */
    protected $mysql_enabled = false;

    /**
     * PDO object
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Commands config
     *
     * @var array
     */
    protected $commands_config = [];

    /**
     * Admins list
     *
     * @var array
     */
    protected $admins_list = [];

    /**
     * ServerResponse of the last Command execution
     *
     * @var \Longman\TelegramBot\Entities\ServerResponse
     */
    protected $last_command_response;

    /**
     * Botan.io integration
     *
     * @var boolean
     */
    protected $botan_enabled = false;

    /**
     * Check if runCommands() is running in this session
     *
     * @var boolean
     */
    protected $run_commands = false;

    /**
     * Is running getUpdates without DB enabled
     *
     * @var bool
     */
    protected $getupdates_without_database = false;

    /**
     * Last update ID
     * Only used when running getUpdates without a database
     *
     * @var integer
     */
    protected $last_update_id = null;

	/**
	 * URI of the Telegram API
	 *
	 * @var string
	 */
	private $api_base_uri = 'https://api.telegram.org';

	/**
	 * Guzzle Client object
	 *
	 * @var \GuzzleHttp\Client
	 */
	private $client;

	/**
	 * Input value of the request
	 *
	 * @var string
	 */
	private $input;

	/**
	 * Request limiter
	 *
	 * @var boolean
	 */
	private $limiter_enabled;

	/**
	 * Request limiter's interval between checks
	 *
	 * @var float
	 */
	private $limiter_interval;

	/**
	 * Available actions to send
	 *
	 * This is basically the list of all methods listed on the official API documentation.
	 *
	 * @link https://core.telegram.org/bots/api
	 *
	 * @var array
	 */
	private static $actions = [
		'getUpdates',
		'setWebhook',
		'deleteWebhook',
		'getWebhookInfo',
		'getMe',
		'sendMessage',
		'forwardMessage',
		'sendPhoto',
		'sendAudio',
        'sendDocument',
		'sendSticker',
		'sendVideo',
		'sendAnimation',
		'sendVoice',
		'sendVideoNote',
		'sendMediaGroup',
		'sendLocation',
		'editMessageLiveLocation',
		'stopMessageLiveLocation',
		'sendVenue',
		'sendContact',
		'sendChatAction',
		'getUserProfilePhotos',
		'getFile',
		'kickChatMember',
		'unbanChatMember',
		'restrictChatMember',
		'promoteChatMember',
		'exportChatInviteLink',
		'setChatPhoto',
		'deleteChatPhoto',
		'setChatTitle',
		'setChatDescription',
		'pinChatMessage',
		'unpinChatMessage',
		'leaveChat',
		'getChat',
		'getChatAdministrators',
		'getChatMembersCount',
		'getChatMember',
		'setChatStickerSet',
		'deleteChatStickerSet',
		'answerCallbackQuery',
		'answerInlineQuery',
		'editMessageText',
		'editMessageCaption',
		'editMessageMedia',
		'editMessageReplyMarkup',
		'deleteMessage',
		'getStickerSet',
		'uploadStickerFile',
		'createNewStickerSet',
		'addStickerToSet',
		'setStickerPositionInSet',
		'deleteStickerFromSet',
		'sendInvoice',
		'answerShippingQuery',
		'answerPreCheckoutQuery',
		'setPassportDataErrors',
		'sendGame',
		'setGameScore',
		'getGameHighScores',
	];

	/**
	 * Some methods need a dummy param due to certain cURL issues.
	 *
	 * @see addDummyParamIfNecessary()
	 *
	 * @var array
	 */
	private static $actions_need_dummy_param = [
		'deleteWebhook',
		'getWebhookInfo',
		'getMe',
	];

    /**
     * Telegram constructor.
     *
     * @param string $api_key
     * @param string $bot_username
     *
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function __construct($api_key, $bot_username = '')
    {
        if (empty($api_key)) {
            throw new TelegramException('API KEY not defined!');
        }
        preg_match('/(\d+)\:[\w\-]+/', $api_key, $matches);
        if (!isset($matches[1])) {
            throw new TelegramException('Invalid API KEY defined!');
        }
        $this->bot_id  = $matches[1];
        $this->api_key = $api_key;

        if (!empty($bot_username)) {
            $this->bot_username = $bot_username;
        }

        //Add default system commands path
        $this->addCommandsPath(TB_BASE_COMMANDS_PATH . '/SystemCommands');

	    $this->setClient(new Client([ 'base_uri' => $this->api_base_uri]));
    }

	/**
	 * Set a custom Guzzle HTTP Client object
	 *
	 * @param Client $client
	 *
	 * @throws TelegramException
	 */
	public function setClient(Client $client)
	{
		if (!($client instanceof Client)) {
			throw new TelegramException('Invalid GuzzleHttp\Client pointer!');
		}

		$this->client = $client;
	}

	/**
	 * Set input from custom input or stdin and return it
	 *
	 * @return string
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	public function fetchInput()
	{
		// First check if a custom input has been set, else get the PHP input.
		if (!$this->input) {
			$this->input = file_get_contents('php://input');
		}

		// Make sure we have a string to work with.
		if (!is_string($this->input)) {
			throw new TelegramException('Input must be a string!');
		}

		TelegramLog::update($this->input);

		return $this->input;
	}

	/**
	 * Generate general fake server response
	 *
	 * @param array $data Data to add to fake response
	 *
	 * @return array Fake response data
	 */
	public static function generateGeneralFakeServerResponse(array $data = [])
	{
		//PARAM BINDED IN PHPUNIT TEST FOR TestServerResponse.php
		//Maybe this is not the best possible implementation

		//No value set in $data ie testing setWebhook
		//Provided $data['chat_id'] ie testing sendMessage

		$fake_response = ['ok' => true]; // :)

		if ($data === []) {
			$fake_response['result'] = true;
		}

		//some data to let iniatilize the class method SendMessage
		if (isset($data['chat_id'])) {
			$data['message_id'] = '1234';
			$data['date']       = '1441378360';
			$data['from']       = [
				'id'         => 123456789,
				'first_name' => 'botname',
				'username'   => 'namebot',
			];
			$data['chat']       = ['id' => $data['chat_id']];

			$fake_response['result'] = $data;
		}

		return $fake_response;
	}

	/**
	 * Properly set up the request params
	 *
	 * If any item of the array is a resource, reformat it to a multipart request.
	 * Else, just return the passed data as form params.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function setUpRequestParams(array $data)
	{
		$has_resource = false;
		$multipart    = [];

		// Convert any nested arrays into JSON strings.
		array_walk($data, function (&$item) {
			is_array($item) && $item = json_encode($item);
		});

		//Reformat data array in multipart way if it contains a resource
		foreach ($data as $key => $item) {
			$has_resource |= (is_resource($item) || $item instanceof \GuzzleHttp\Psr7\Stream);
			$multipart[]  = ['name' => $key, 'contents' => $item];
		}
		if ($has_resource) {
			return ['multipart' => $multipart];
		}

		return ['form_params' => $data];
	}

	/**
	 * Execute HTTP Request
	 *
	 * @param string $action Action to execute
	 * @param array  $data   Data to attach to the execution
	 *
	 * @return string Result of the HTTP Request
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	public function execute($action, array $data = [])
	{
		//Fix so that the keyboard markup is a string, not an object
		if (isset($data['reply_markup'])) {
			$data['reply_markup'] = json_encode($data['reply_markup']);
		}

		$result                  = null;
		$request_params          = $this->setUpRequestParams($data);
		$request_params['debug'] = TelegramLog::getDebugLogTempStream();

		try {
			$response = $this->client->post(
				'/bot' . $this->getApiKey() . '/' . $action,
				$request_params
			);
			$result   = (string) $response->getBody();

			//Logging getUpdates Update
			if ($action === 'getUpdates') {
				TelegramLog::update($result);
			}
		} catch (RequestException $e) {
			$result = ($e->getResponse()) ? (string) $e->getResponse()->getBody() : '';
		} finally {
			//Logging verbose debug output
			TelegramLog::endDebugLogTempStream('Verbose HTTP Request output:' . PHP_EOL . '%s' . PHP_EOL);
			TelegramLog::debug($result);
		}

		return $result;
	}

	/**
	 * Download file
	 *
	 * @param \Longman\TelegramBot\Entities\File $file
	 *
	 * @return boolean
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	public function downloadFile(File $file)
	{
		if (empty($download_path = $this->getDownloadPath())) {
			throw new TelegramException('Download path not set!');
		}

		$tg_file_path = $file->getFilePath();
		$file_path    = $download_path . '/' . $tg_file_path;

		$file_dir = dirname($file_path);
		//For safety reasons, first try to create the directory, then check that it exists.
		//This is in case some other process has created the folder in the meantime.
		if (!@mkdir($file_dir, 0755, true) && !is_dir($file_dir)) {
			throw new TelegramException('Directory ' . $file_dir . ' can\'t be created');
		}

		$debug_handle = TelegramLog::getDebugLogTempStream();

		try {
			$this->client->get(
				'/file/bot' . $this->getApiKey() . '/' . $tg_file_path,
				['debug' => $debug_handle, 'sink' => $file_path]
			);

			return filesize($file_path) > 0;
		} catch (RequestException $e) {
			return ($e->getResponse()) ? (string) $e->getResponse()->getBody() : '';
		} finally {
			//Logging verbose debug output
			TelegramLog::endDebugLogTempStream('Verbose HTTP File Download Request output:' . PHP_EOL . '%s' . PHP_EOL);
		}
	}

	/**
	 * Encode file
	 *
	 * @param string $file
	 *
	 * @return resource
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	public function encodeFile($file)
	{
		$fp = fopen($file, 'rb');
		if ($fp === false) {
			throw new TelegramException('Cannot open "' . $file . '" for reading');
		}

		return $fp;
	}

	/**
	 * Send command
	 *
	 * @todo Fake response doesn't need json encoding?
	 * @todo Write debug entry on failure
	 *
	 * @param string $action
	 * @param array  $data
	 *
	 * @return \Longman\TelegramBot\Entities\ServerResponse
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	public function send($action, array $data = [])
	{
		$this->ensureValidAction($action);
		$this->addDummyParamIfNecessary($action, $data);

		$bot_username = $this->getBotUsername();

		if (defined('PHPUNIT_TESTSUITE')) {
			$fake_response = self::generateGeneralFakeServerResponse($data);

			return new ServerResponse($fake_response, $bot_username);
		}

		$this->ensureNonEmptyData($data);

		$this->limitTelegramRequests($action, $data);

		$raw_response = $this->execute($action, $data);
		$response = json_decode($raw_response, true);

		if (null === $response) {
			TelegramLog::debug($raw_response);
			throw new TelegramException('Telegram returned an invalid response!');
		}

		$response = new ServerResponse($response, $bot_username);

		return $response;
	}

	/**
	 * Add a dummy parameter if the passed action requires it.
	 *
	 * If a method doesn't require parameters, we need to add a dummy one anyway,
	 * because of some cURL version failed POST request without parameters.
	 *
	 * @link https://github.com/php-telegram-bot/core/pull/228
	 *
	 * @todo Would be nice to find a better solution for this!
	 *
	 * @param string $action
	 * @param array  $data
	 */
	protected function addDummyParamIfNecessary($action, array &$data)
	{
		if (in_array($action, self::$actions_need_dummy_param, true)) {
			// Can be anything, using a single letter to minimise request size.
			$data = ['d'];
		}
	}

	/**
	 * Make sure the data isn't empty, else throw an exception
	 *
	 * @param array $data
	 *
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	private function ensureNonEmptyData(array $data)
	{
		if (count($data) === 0) {
			throw new TelegramException('Data is empty!');
		}
	}

	/**
	 * Make sure the action is valid, else throw an exception
	 *
	 * @param string $action
	 *
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	private function ensureValidAction($action)
	{
		if (!in_array($action, self::$actions, true)) {
			throw new TelegramException('The action "' . $action . '" doesn\'t exist!');
		}
	}

	/**
	 * Use this method to send text messages. On success, the sent Message is returned
	 *
	 * @link https://core.telegram.org/bots/api#sendmessage
	 *
	 * @param array $data
	 *
	 * @return \Longman\TelegramBot\Entities\ServerResponse
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	public function sendMessage(array $data)
	{
		$text = $data['text'];

		do {
			//Chop off and send the first message
			$data['text'] = mb_substr($text, 0, 4096);
			$response     = $this->send('sendMessage', $data);

			//Prepare the next message
			$text = mb_substr($text, 4096);
		} while (mb_strlen($text, 'UTF-8') > 0);

		return $response;
	}

	/**
	 * Any statically called method should be relayed to the `send` method.
	 *
	 * @param string $action
	 * @param array  $data
	 *
	 * @return \Longman\TelegramBot\Entities\ServerResponse
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	public function __call($action, array $data)
	{
		// Make sure to add the action being called as the first parameter to be passed.
		array_unshift($data, $action);

		// @todo Use splat operator for unpacking when we move to PHP 5.6+
		return call_user_func_array(array($this, 'send'), $data);
	}

	/**
	 * Return an empty Server Response
	 *
	 * No request to telegram are sent, this function is used in commands that
	 * don't need to fire a message after execution
	 *
	 * @return \Longman\TelegramBot\Entities\ServerResponse
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	public function emptyResponse()
	{
		return new ServerResponse(['ok' => true, 'result' => true], null);
	}

	/**
	 * Send message to all active chats
	 *
	 * @param string $callback_function
	 * @param array  $data
	 * @param array  $select_chats_params
	 *
	 * @return array
	 * @throws TelegramException
	 */
	public function sendToActiveChats(
		$callback_function,
		array $data,
		array $select_chats_params
	) {
		$this->ensureValidAction($callback_function);

		$chats = DB::selectChats($select_chats_params);

		$results = [];
		if (is_array($chats)) {
			foreach ($chats as $row) {
				$data['chat_id'] = $row['chat_id'];
				$results[]       = $this->send($callback_function, $data);
			}
		}

		return $results;
	}

	/**
	 * Enable request limiter
	 *
	 * @param boolean $enable
	 * @param array   $options
	 *
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	public function setLimiter($enable = true, array $options = [])
	{
		if (DB::isDbConnected()) {
			$options_default = [
				'interval' => 1,
			];

			$options = array_merge($options_default, $options);

			if (!is_numeric($options['interval']) || $options['interval'] <= 0) {
				throw new TelegramException('Interval must be a number and must be greater than zero!');
			}

			$this->limiter_interval = $options['interval'];
			$this->limiter_enabled  = $enable;
		}
	}

	/**
	 * This functions delays API requests to prevent reaching Telegram API limits
	 *  Can be disabled while in execution by 'setLimiter(false)'
	 *
	 * @link https://core.telegram.org/bots/faq#my-bot-is-hitting-limits-how-do-i-avoid-this
	 *
	 * @param string $action
	 * @param array  $data
	 *
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	private function limitTelegramRequests($action, array $data = [])
	{
		if ($this->limiter_enabled) {
			$limited_methods = [
				'sendMessage',
				'forwardMessage',
				'sendPhoto',
				'sendAudio',
				'sendDocument',
				'sendSticker',
				'sendVideo',
				'sendAnimation',
				'sendVoice',
				'sendVideoNote',
				'sendMediaGroup',
				'sendLocation',
				'editMessageLiveLocation',
				'stopMessageLiveLocation',
				'sendVenue',
				'sendContact',
				'sendInvoice',
				'sendGame',
				'setGameScore',
				'editMessageText',
				'editMessageCaption',
				'editMessageMedia',
				'editMessageReplyMarkup',
				'setChatTitle',
				'setChatDescription',
				'setChatStickerSet',
				'deleteChatStickerSet',
				'setPassportDataErrors',
			];

			$chat_id           = isset($data['chat_id']) ? $data['chat_id'] : null;
			$inline_message_id = isset($data['inline_message_id']) ? $data['inline_message_id'] : null;

			if (($chat_id || $inline_message_id) && in_array($action, $limited_methods)) {
				$timeout = 60;

				while (true) {
					if ($timeout <= 0) {
						throw new TelegramException('Timed out while waiting for a request spot!');
					}

					$requests = DB::getTelegramRequestCount($chat_id, $inline_message_id);

					$chat_per_second   = ($requests['LIMIT_PER_SEC'] == 0); // No more than one message per second inside a particular chat
					$global_per_second = ($requests['LIMIT_PER_SEC_ALL'] < 30);    // No more than 30 messages per second to different chats
					$groups_per_minute = (((is_numeric($chat_id) && $chat_id > 0) || !is_null($inline_message_id)) || ((!is_numeric($chat_id) || $chat_id < 0) && $requests['LIMIT_PER_MINUTE'] < 20));    // No more than 20 messages per minute in groups and channels

					if ($chat_per_second && $global_per_second && $groups_per_minute) {
						break;
					}

					$timeout--;
					usleep($this->limiter_interval * 1000000);
				}

				DB::insertTelegramRequest($action, $data);
			}
		}
	}

	/**
     * Initialize Database connection
     *
     * @param array  $credential
     * @param string $table_prefix
     * @param string $encoding
     *
     * @return \Longman\TelegramBot\Telegram
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function enableMySql(array $credential, $table_prefix = null, $encoding = 'utf8mb4')
    {
        $this->pdo = DB::initialize($credential, $this, $table_prefix, $encoding);
        ConversationDB::initializeConversation();
        $this->mysql_enabled = true;

        return $this;
    }

    /**
     * Initialize Database external connection
     *
     * @param PDO    $external_pdo_connection PDO database object
     * @param string $table_prefix
     *
     * @return \Longman\TelegramBot\Telegram
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function enableExternalMySql($external_pdo_connection, $table_prefix = null)
    {
        $this->pdo = DB::externalInitialize($external_pdo_connection, $this, $table_prefix);
        ConversationDB::initializeConversation();
        $this->mysql_enabled = true;

        return $this;
    }

    /**
     * Get commands list
     *
     * @return array $commands
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function getCommandsList()
    {
        $commands = [];

        foreach ($this->commands_paths as $path) {
            try {
                //Get all "*Command.php" files
                $files = new RegexIterator(
                    new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path)
                    ),
                    '/^.+Command.php$/'
                );

                foreach ($files as $file) {
                    //Remove "Command.php" from filename
                    $command      = $this->sanitizeCommand(substr($file->getFilename(), 0, -11));
                    $command_name = strtolower($command);

                    if (array_key_exists($command_name, $commands)) {
                        continue;
                    }

                    require_once $file->getPathname();

                    $command_obj = $this->getCommandObject($command);
                    if ($command_obj instanceof Command) {
                        $commands[$command_name] = $command_obj;
                    }
                }
            } catch (Exception $e) {
                throw new TelegramException('Error getting commands from path: ' . $path);
            }
        }

        return $commands;
    }

    /**
     * Get an object instance of the passed command
     *
     * @param string $command
     *
     * @return \Longman\TelegramBot\Commands\Command|null
     */
    public function getCommandObject($command)
    {
        $which = ['System'];
        $this->isAdmin() && $which[] = 'Admin';
        $which[] = 'User';

        foreach ($which as $auth) {
            $command_namespace = __NAMESPACE__ . '\\Commands\\' . $auth . 'Commands\\' . $this->ucfirstUnicode($command) . 'Command';
            if (class_exists($command_namespace)) {
                return new $command_namespace($this, $this->update);
            }
        }

        return null;
    }

    /**
     * Set custom input string for debug purposes
     *
     * @param string $input (json format)
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Get custom input string for debug purposes
     *
     * @return string
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Get the ServerResponse of the last Command execution
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     */
    public function getLastCommandResponse()
    {
        return $this->last_command_response;
    }

    /**
     * Handle getUpdates method
     *
     * @param int|null $limit
     * @param int|null $timeout
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function handleGetUpdates($limit = null, $timeout = null)
    {
        if (empty($this->bot_username)) {
            throw new TelegramException('Bot Username is not defined!');
        }

        if (!DB::isDbConnected() && !$this->getupdates_without_database) {
            return new ServerResponse(
                [
                    'ok'          => false,
                    'description' => 'getUpdates needs MySQL connection! (This can be overridden - see documentation)',
                ],
                $this->bot_username
            );
        }

        $offset = 0;

        //Take custom input into account.
        if ($this->input) {
            $response = new ServerResponse(json_decode($this->input, true), $this->bot_username);
        } else {
            if (DB::isDbConnected()) {
                //Get last update id from the database
                $last_update = DB::selectTelegramUpdate(1);
                $last_update = reset($last_update);

                $this->last_update_id = isset($last_update['id']) ? $last_update['id'] : null;
            }

            if ($this->last_update_id !== null) {
                $offset = $this->last_update_id + 1;    //As explained in the telegram bot API documentation
            }

            $response = $this->getUpdates((
                [
                    'offset'  => $offset,
                    'limit'   => $limit,
                    'timeout' => $timeout,
                ]
            ));
        }

        if ($response->isOk()) {
            $results = $response->getResult();

            //Process all updates
            /** @var Update $result */
            foreach ($results as $result) {
                $this->processUpdate($result);
            }

            if (!DB::isDbConnected() && !$this->input && $this->last_update_id !== null && $offset === 0) {
                //Mark update(s) as read after handling
                $this->getUpdates(
                    [
                        'offset'  => $this->last_update_id + 1,
                        'limit'   => 1,
                        'timeout' => $timeout,
                    ]
                );
            }
        }

        return $response;
    }

    /**
     * Handle bot request from webhook
     *
     * @return bool
     *
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function handle()
    {
        if (empty($this->bot_username)) {
            throw new TelegramException('Bot Username is not defined!');
        }

        $this->fetchInput();

        if (empty($this->input)) {
            throw new TelegramException('Input is empty!');
        }

        $post = json_decode($this->input, true);
        if (empty($post)) {
            throw new TelegramException('Invalid JSON!');
        }

        if ($response = $this->processUpdate(new Update($post, $this->bot_username))) {
            return $response->isOk();
        }

        return false;
    }

    /**
     * Get the command name from the command type
     *
     * @param string $type
     *
     * @return string
     */
    protected function getCommandFromType($type)
    {
        return $this->ucfirstUnicode(str_replace('_', '', $type));
    }

    /**
     * Process bot Update request
     *
     * @param \Longman\TelegramBot\Entities\Update $update
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function processUpdate(Update $update)
    {
        $this->update = $update;
        $this->last_update_id = $update->getUpdateId();

        //Make sure we have an up-to-date command list
        //This is necessary to "require" all the necessary command files!
        $this->getCommandsList();

        //If all else fails, it's a generic message.
        $command = 'genericmessage';

        $update_type = $this->update->getUpdateType();
        if ($update_type === 'message') {
            $message = $this->update->getMessage();

            //Load admin commands
            if ($this->isAdmin()) {
                $this->addCommandsPath(TB_BASE_COMMANDS_PATH . '/AdminCommands', false);
            }

            $type = $message->getType();
            if ($type === 'command') {
                $command = $message->getCommand();
                if ($this->getCommandObject($command)->isSystemCommand() && !($this->getCommandObject($command) instanceof GenericCommand)) {
                    $command = 'generic';
                }
            } elseif (in_array($type, [
                'new_chat_members',
                'left_chat_member',
                'new_chat_title',
                'new_chat_photo',
                'delete_chat_photo',
                'group_chat_created',
                'supergroup_chat_created',
                'channel_chat_created',
                'migrate_to_chat_id',
                'migrate_from_chat_id',
                'pinned_message',
                'invoice',
                'successful_payment',
            ], true)
            ) {
                $command = $this->getCommandFromType($type);
            }
        } else {
            $command = $this->getCommandFromType($update_type);
        }

        //Make sure we don't try to process update that was already processed
        $last_id = DB::selectTelegramUpdate(1, $this->update->getUpdateId());
        if ($last_id && count($last_id) === 1) {
            TelegramLog::debug('Duplicate update received, processing aborted!');
            return $this->emptyResponse();
        }

        DB::insertRequest($this->update);

        return $this->executeCommand($command);
    }

    /**
     * Execute /command
     *
     * @param string $command
     *
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function executeCommand($command)
    {
        $command     = strtolower($command);
        $command_obj = $this->getCommandObject($command);

        if (!$command_obj || !$command_obj->isEnabled()) {
            //Failsafe in case the Generic command can't be found
            if ($command === 'generic') {
                throw new TelegramException('Generic command missing!');
            }

            //Handle a generic command or non existing one
            $this->last_command_response = $this->executeCommand('generic');
        } else {
            //Botan.io integration, make sure only the actual command user executed is reported
            if ($this->botan_enabled) {
                Botan::lock($command);
            }

            //execute() method is executed after preExecute()
            //This is to prevent executing a DB query without a valid connection
            $this->last_command_response = $command_obj->preExecute();

            //Botan.io integration, send report after executing the command
            if ($this->botan_enabled) {
                Botan::track($this->update, $command);
            }
        }

        return $this->last_command_response;
    }

    /**
     * Sanitize Command
     *
     * @param string $command
     *
     * @return string
     */
    protected function sanitizeCommand($command)
    {
        return str_replace(' ', '', $this->ucwordsUnicode(str_replace('_', ' ', $command)));
    }

    /**
     * Enable a single Admin account
     *
     * @param integer $admin_id Single admin id
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function enableAdmin($admin_id)
    {
        if (!is_int($admin_id) || $admin_id <= 0) {
            TelegramLog::error('Invalid value "%s" for admin.', $admin_id);
        } elseif (!in_array($admin_id, $this->admins_list, true)) {
            $this->admins_list[] = $admin_id;
        }

        return $this;
    }

    /**
     * Enable a list of Admin Accounts
     *
     * @param array $admin_ids List of admin ids
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function enableAdmins(array $admin_ids)
    {
        foreach ($admin_ids as $admin_id) {
            $this->enableAdmin($admin_id);
        }

        return $this;
    }

    /**
     * Get list of admins
     *
     * @return array
     */
    public function getAdminList()
    {
        return $this->admins_list;
    }

    /**
     * Check if the passed user is an admin
     *
     * If no user id is passed, the current update is checked for a valid message sender.
     *
     * @param int|null $user_id
     *
     * @return bool
     */
    public function isAdmin($user_id = null)
    {
        if ($user_id === null && $this->update !== null) {
            //Try to figure out if the user is an admin
            $update_methods = [
                'getMessage',
                'getEditedMessage',
                'getChannelPost',
                'getEditedChannelPost',
                'getInlineQuery',
                'getChosenInlineResult',
                'getCallbackQuery',
            ];
            foreach ($update_methods as $update_method) {
                $object = call_user_func([$this->update, $update_method]);
                if ($object !== null && $from = $object->getFrom()) {
                    $user_id = $from->getId();
                    break;
                }
            }
        }

        return ($user_id === null) ? false : in_array($user_id, $this->admins_list, true);
    }

    /**
     * Check if user required the db connection
     *
     * @return bool
     */
    public function isDbEnabled()
    {
        if ($this->mysql_enabled) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add a single custom commands path
     *
     * @param string $path   Custom commands path to add
     * @param bool   $before If the path should be prepended or appended to the list
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function addCommandsPath($path, $before = true)
    {
        if (!is_dir($path)) {
            TelegramLog::error('Commands path "%s" does not exist.', $path);
        } elseif (!in_array($path, $this->commands_paths, true)) {
            if ($before) {
                array_unshift($this->commands_paths, $path);
            } else {
                $this->commands_paths[] = $path;
            }
        }

        return $this;
    }

    /**
     * Add multiple custom commands paths
     *
     * @param array $paths  Custom commands paths to add
     * @param bool  $before If the paths should be prepended or appended to the list
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function addCommandsPaths(array $paths, $before = true)
    {
        foreach ($paths as $path) {
            $this->addCommandsPath($path, $before);
        }

        return $this;
    }

    /**
     * Return the list of commands paths
     *
     * @return array
     */
    public function getCommandsPaths()
    {
        return $this->commands_paths;
    }

    /**
     * Set custom upload path
     *
     * @param string $path Custom upload path
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function setUploadPath($path)
    {
        $this->upload_path = $path;

        return $this;
    }

    /**
     * Get custom upload path
     *
     * @return string
     */
    public function getUploadPath()
    {
        return $this->upload_path;
    }

    /**
     * Set custom download path
     *
     * @param string $path Custom download path
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function setDownloadPath($path)
    {
        $this->download_path = $path;

        return $this;
    }

    /**
     * Get custom download path
     *
     * @return string
     */
    public function getDownloadPath()
    {
        return $this->download_path;
    }

    /**
     * Set command config
     *
     * Provide further variables to a particular commands.
     * For example you can add the channel name at the command /sendtochannel
     * Or you can add the api key for external service.
     *
     * @param string $command
     * @param array  $config
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function setCommandConfig($command, array $config)
    {
        $this->commands_config[$command] = $config;

        return $this;
    }

    /**
     * Get command config
     *
     * @param string $command
     *
     * @return array
     */
    public function getCommandConfig($command)
    {
        return isset($this->commands_config[$command]) ? $this->commands_config[$command] : [];
    }

    /**
     * Get API key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * Get Bot name
     *
     * @return string
     */
    public function getBotUsername()
    {
        return $this->bot_username;
    }

    /**
     * Get Bot Id
     *
     * @return string
     */
    public function getBotId()
    {
        return $this->bot_id;
    }

    /**
     * Get Version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Replace function `ucwords` for UTF-8 characters in the class definition and commands
     *
     * @param string $str
     * @param string $encoding (default = 'UTF-8')
     *
     * @return string
     */
    protected function ucwordsUnicode($str, $encoding = 'UTF-8')
    {
        return mb_convert_case($str, MB_CASE_TITLE, $encoding);
    }

    /**
     * Replace function `ucfirst` for UTF-8 characters in the class definition and commands
     *
     * @param string $str
     * @param string $encoding (default = 'UTF-8')
     *
     * @return string
     */
    protected function ucfirstUnicode($str, $encoding = 'UTF-8')
    {
        return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding)
               . mb_strtolower(mb_substr($str, 1, mb_strlen($str), $encoding), $encoding);
    }

    /**
     * Enable Botan.io integration
     *
     * @param  string $token
     * @param  array  $options
     *
     * @return \Longman\TelegramBot\Telegram
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function enableBotan($token, array $options = [])
    {
        Botan::initializeBotan($token, $options);
        $this->botan_enabled = true;

        return $this;
    }

    /**
     * Enable requests limiter
     *
     * @param  array $options
     *
     * @return \Longman\TelegramBot\Telegram
     */
    public function enableLimiter(array $options = [])
    {
        $this->setLimiter(true, $options);

        return $this;
    }

    /**
     * Run provided commands
     *
     * @param array $commands
     *
     * @throws TelegramException
     */
    public function runCommands($commands)
    {
        if (!is_array($commands) || empty($commands)) {
            throw new TelegramException('No command(s) provided!');
        }

        $this->run_commands  = true;
        $this->botan_enabled = false;   // Force disable Botan.io integration, we don't want to track self-executed commands!

        $result = $this->getMe();

        if ($result->isOk()) {
            $result = $result->getResult();

            $bot_id       = $result->getId();
            $bot_name     = $result->getFirstName();
            $bot_username = $result->getUsername();
        } else {
            $bot_id       = $this->getBotId();
            $bot_name     = $this->getBotUsername();
            $bot_username = $this->getBotUsername();
        }


        $this->enableAdmin($bot_id);    // Give bot access to admin commands
        $this->getCommandsList();       // Load full commands list

        foreach ($commands as $command) {
            $this->update = new Update(
                [
                    'update_id' => 0,
                    'message'   => [
                        'message_id' => 0,
                        'from'       => [
                            'id'         => $bot_id,
                            'first_name' => $bot_name,
                            'username'   => $bot_username,
                        ],
                        'date'       => time(),
                        'chat'       => [
                            'id'   => $bot_id,
                            'type' => 'private',
                        ],
                        'text'       => $command,
                    ],
                ]
            );

            $this->executeCommand($this->update->getMessage()->getCommand());
        }
    }

    /**
     * Is this session initiated by runCommands()
     *
     * @return bool
     */
    public function isRunCommands()
    {
        return $this->run_commands;
    }

    /**
     * Switch to enable running getUpdates without a database
     *
     * @param bool $enable
     */
    public function useGetUpdatesWithoutDatabase($enable = true)
    {
        $this->getupdates_without_database = $enable;
    }

    /**
     * Return last update id
     *
     * @return int
     */
    public function getLastUpdateId()
    {
        return $this->last_update_id;
    }
}

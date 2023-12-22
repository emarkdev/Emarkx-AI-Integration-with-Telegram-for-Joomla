<?php
/**
* Emarkx AI Integration for Joomla! - v1.0.7
*
* For Joomla! CMS (v3.x and v4.x)
* @link              https://emarkdev.com
* @since             1.0.7
* @package           Joomla.Administrator
* @subpackage        com_emarkx_telegram_ai
* 
* Author:            Emarkx (Ephrain Marchan)
* Website:			 https://Emarkdev.com
* License:           GPL-2.0+, http://www.gnu.org/licenses/gpl-2.0.txt
* 
*
* ███████╗███╗   ███╗ █████╗ ██████╗ ██╗  ██╗██████╗ ███████╗██╗   ██╗
* ██╔════╝████╗ ████║██╔══██╗██╔══██╗██║ ██╔╝██╔══██╗██╔════╝██║   ██║
* █████╗  ██╔████╔██║███████║██████╔╝█████╔╝ ██║  ██║█████╗  ██║   ██║
* ██╔══╝  ██║╚██╔╝██║██╔══██║██╔══██╗██╔═██╗ ██║  ██║██╔══╝  ╚██╗ ██╔╝
* ███████╗██║ ╚═╝ ██║██║  ██║██║  ██║██║  ██╗██████╔╝███████╗ ╚████╔╝ 
* ╚══════╝╚═╝     ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝╚═════╝ ╚══════╝  ╚═══╝  
*   
*/

defined('_JEXEC') or die;

// Include the autoloader
require_once JPATH_SITE . '/plugins/system/emarkx-telegram-ai/vendor/autoload.php';

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\User;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Cookie\CookieJar;

class PlgSystememarkxTelegramAi extends CMSPlugin
{
	private $chat_ids_option_name = 'emarkx_telegram_ai_chat_ids';
	private $bot_api_key_option_name = 'emarkx_telegram_ai_bot_api_key';
	private $gpt_api_key_option_name = 'emarkx_telegram_ai_gpt_api_key';
	private $webhook_set_flag_option_name = 'emarkx_telegram_ai_webhook_set_flag';

    

	public function triggerCustomEvent()
	{

        // $this->TestTelegramWebhook();

        // Check if the webhook has already been set
        if (!$this->isWebhookSet()) {
            $this->set_telegram_bot_webhook();
            $this->setWebhookSetFlag();
        }

        $this->processTelegramWebhook();

	}
	
    private function isWebhookSet()
    {
        $params = $this->getPluginParams();
        $flag = $params->get($this->webhook_set_flag_option_name);
        return ($flag === true);
    }

    private function setWebhookSetFlag()
    {
        $params = $this->getPluginParams();
        $params->set($this->webhook_set_flag_option_name, true);
        
    }
    

    private function processTelegramWebhook()
    {
        $params = $this->getPluginParams();
        $chat_ids = $params->get('emarkx_telegram_ai_chat_ids');
        
        $app = \Joomla\CMS\Factory::getApplication();
        $input = $app->input;
    
        $data = json_decode(file_get_contents('php://input'), true);
        
        $chat_id = $data['message']['chat']['id'] ?? null;
        $message = $data['message']['text'] ?? null;
    
        
        // Check if the required keys are set and not null
        if ($chat_id !== null && $message !== null) {
            $message = $this->sanitizeTelegramMessage2($message);
    
            // Define the allowed chat ID(s)
            $allowed_chat_ids = explode(',', $chat_ids);
    
            // Check if the received chat ID is allowed
            if (in_array($chat_id, $allowed_chat_ids)) {
                // Retrieve the last stored message for the chat ID
                $last_message = $this->getLastStoredMessage();
    
           
                // Compare the received message with the last stored message for the chat ID
                if ($last_message !== $message) {
                    // Update the stored message with the new message for the chat ID
                    $this->updateLastStoredMessage($message);
                    
                    $this->setLastSessionValue('1');
        
                    // Perform actions based on the received message
                    // For example, call the GPT API and send a response back to the chat ID
                    $response = $this->callGptApi($message);
                    $this->sendTelegramMessage($chat_id, $response);
    
                    // Return a response indicating successful message processing
                    // echo 'message sent';
                } else {
                    // Plugin is thinking that the message is the same out of two reasons. If its the 5 hours glitch reason, lets fix that one.
                    
                    
                    $stored_message = $this->getLastSessionValue();
                    
                    // $this->sendTelegramMessage($chat_id, 'Stored message '.$stored_message );
                    
                    if ($stored_message == '1'){
                        // Get current URL and refresh the joomla session
                        $uri = \Joomla\CMS\Uri\Uri::getInstance();
                        $currentUrl = $uri->toString(array('scheme', 'host', 'port'));
            
                        $api_url = $currentUrl.'/administrator';
                        
                        
                        $this->sendTelegramMessage($chat_id, 'Session Expired or you sent the same message. Click this link to renew session (no need to log in) -> '.$api_url );
						
						$this->setLastSessionValue('0');
                    }
                    
                
                }
            } else {
                // Return an error response if the chat ID is not allowed
                // echo 'Unauthorized chat ID';
            }
        }
    }
    
    
    public function getLastSessionValue()
    {
        // Get the current plugin directory
        $pluginDir = dirname(__FILE__);
    
        // Build the file path
        $filePath = $pluginDir . '/session_values.txt';
    
        if (file_exists($filePath)) {
            $lastSessionData = file_get_contents($filePath);
            return $lastSessionData !== false ? $lastSessionData : null;
        }
        return null;
    }
    
    public function setLastSessionValue($message)
    {
        // Get the current plugin directory
        $pluginDir = dirname(__FILE__);
    
        // Build the file path
        $filePath = $pluginDir . '/session_values.txt';
    
        file_put_contents($filePath, $message);
    }
    
    
    
    // Retrieve the last stored message for the chat ID
    private function getLastStoredMessage()
    {
        // Retrieve the stored message from your preferred storage mechanism (e.g., database, file, session) 
        
        $stored_message = $this->getLastSessionData();
        
        return $this->sanitizeTelegramMessage2($stored_message);
    }
    
    
    
    // Update the last stored message for the chat ID
    private function updateLastStoredMessage($message)
    {
        // Update the stored message using your preferred storage mechanism (e.g., database, file, session)
        
        $this->setLastSessionData($message);
    }
    
    
    
    public function getLastSessionData()
    {
        // Get the current plugin directory
        $pluginDir = dirname(__FILE__);
    
        // Build the file path
        $filePath = $pluginDir . '/session_data.txt';
    
        if (file_exists($filePath)) {
            $lastSessionData = file_get_contents($filePath);
            return $lastSessionData !== false ? $lastSessionData : null;
        }
        return null;
    }
    
    public function setLastSessionData($message)
    {
        // Get the current plugin directory
        $pluginDir = dirname(__FILE__);
    
        // Build the file path
        $filePath = $pluginDir . '/session_data.txt';
    
        file_put_contents($filePath, $message);
    }


    
    // Sanitize the Telegram message to handle special characters and coding replies
    private function sanitizeTelegramMessage2($message)
    {
        // Perform any necessary sanitization or normalization on the message here
        // You can customize this method based on your requirements
    
        // For example, you can remove special characters and trim the message
        $sanitized_message = preg_replace('/[^A-Za-z0-9]/', ' ', $message);
        $sanitized_message = trim($sanitized_message);
    
        return $sanitized_message;
    }
    
	

    private function TestTelegramWebhook()
	{
	    $message = 'Plugin Activated'; // test message
	    $params = $this->getPluginParams();
        $chat_ids = $params->get('emarkx_telegram_ai_chat_ids');
        
        if (strpos($chat_ids, ',') !== false) {
            // Define the allowed chat ID(s)
    		// $chat_ids_string = implode(',', $chat_ids);
    		$allowed_chat_ids = explode(',', $chat_ids);
    		
    		foreach ($allowed_chat_ids as $chat_id) {
		        if (!empty($chat_id)) {
		            $this->sendTelegramMessage($chat_id, $message);
		        }
            }
        
        }
        else
        {
            $this->sendTelegramMessage($chat_ids, $message);
        }
	}
	
	
    private function callGptApi($message)
    {
        $params = $this->getPluginParams();
        $gpt_api_key = $params->get('emarkx_telegram_ai_gpt_api_key');
    
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $gpt_api_key,
        ];
		$data = [
			"model" => "gpt-3.5-turbo",
			"messages" => [
				[
					"role" => "user",
					"content" => $message,
				],
			],
		];
    
        $client = new Client();
    
        try {
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $data,
            ]);
    
            $statusCode = $response->getStatusCode();
            $responseData = $response->getBody()->getContents();
    
            if ($statusCode === 200 && !empty($responseData)) {
                $responseData = json_decode($responseData, true);
    
                // print_r($responseData);
                
                if (isset($responseData['choices'][0]['message']['content'])) {
                    return $responseData['choices'][0]['message']['content'];
                }
            }
        } catch (RequestException $e) {
           if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                // echo 'Request failed with error: ' . $e->getMessage() . ', Response body: ' . $responseBody;
            } else {
                // echo 'Request failed with error: ' . $e->getMessage();
            }
            
        }
    
        return 'Failed to generate a response.';
    }
	
	
	
    private function sendTelegramMessage($chat_id, $message)
    {
        $params = $this->getPluginParams();
        $bot_token = $params->get('emarkx_telegram_ai_bot_api_key');
        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";

        $data = [
            'chat_id' => $chat_id,
            'text' => $this->sanitizeTelegramMessage($message),
        ];
    
        $client = new Client();
    
        try {
            $response = $client->post($url, [
                'json' => $data,
            ]);
    
            $statusCode = $response->getStatusCode();
    
            if ($statusCode === 200) {
                // echo 'Telegram message sent successfully.';
            } else {
                // echo 'Failed to send Telegram message. Status Code: ' . $statusCode;
            }
        } catch (\Exception $e) {
            // echo 'Error sending Telegram message: ' . $e->getMessage();
        }
    }


	private function sanitizeTelegramMessage($message)
	{

		$message = htmlspecialchars_decode($message);
		$message = html_entity_decode($message, ENT_QUOTES);
		
		return $message;
	}
	

    private function getPluginParams()
    {
        $plugin = JPluginHelper::getPlugin('system', 'emarkx-telegram-ai');
        $params = new Joomla\Registry\Registry($plugin->params);
        
        return $params;
    }
    
    
    // Set up the Telegram bot webhook URL
    public function set_telegram_bot_webhook()
    {
        $params = $this->getPluginParams();
        $bot_token = $params->get('emarkx_telegram_ai_bot_api_key');
        $webhook_url = JURI::base() . 'index.php?option=com_my_plugin&task=telegram-webhook';
    
        // Generate a nonce for the Joomla REST API
        $nonce = JSession::getFormToken();
    
        $api_url = "https://api.telegram.org/bot{$bot_token}/setWebhook";
        $client = new Client();
    
        try {
            $response = $client->post($api_url, [
                'form_params' => ['url' => $webhook_url],
                // 'headers' => ['X-CSRF-Token' => $nonce], // Include the nonce in the request headers
            ]);
    
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new Exception('Telegram webhook setup failed with status code: ' . $statusCode);
            }
        } catch (RequestException $e) {
            // Handle request exception
            error_log('Telegram webhook setup failed: ' . $e->getMessage());
        }
    }
    



    
}

// Create an instance of the JEventDispatcher class
$dispatcher = new Joomla\Event\Dispatcher;

// Create an instance of the PlgSystememarkxTelegramAi class
$obj = new PlgSystememarkxTelegramAi($dispatcher, array());

// Trigger the custom event
$obj->triggerCustomEvent();

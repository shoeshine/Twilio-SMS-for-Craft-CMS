<?php
namespace Craft;

class TwilioSms_SmsController extends BaseController {
  // this method formats phone numbers
  private function format_number($number, $addplus = true) {
    // remove all non-number characters from the string
    $number = preg_replace('/[^0-9,]|,[0-9]*$/', '', $number) . "\r\n";
    $number = $addplus ? '+' . $number : $number;
    return $number;
  }

  // detect if the request is ajax
  private function is_ajax() {
    // check the HTTP_X_REQUESTED_WITH header
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
      // request is ajax
      return true;
    }

    // request is not ajax
    return false;
  }

  // this method sends sms messages from the front end
  public function actionSendSms() {
    // require POST
    $this->requirePostRequest();

    // get the plugin settings
    $settings = craft()->plugins->getPlugin('twilioSms')->getSettings()->attributes;
    // format the phone number (trim and remove non-numeric characters)
    $number   = trim($this->format_number($_POST['sms_user_phone'], false));
    // format the message
    $message  = trim($_POST['sms_message']);
    // validation error(s)
    $errors   = array();

    // include the Twilio REST API
    require_once(CRAFT_PLUGINS_PATH . 'twiliosms/resources/php/Twilio.php');

    // make sure the number was not empty
    if(!strlen($number)) {
      $errors[] = $settings['numberMissingMsg'];
      craft()->userSession->setFlash('phone', $settings['numberMissingMsg']);
    // make sure number is greater than 5 digits
    // 5 digits is a valid length for a phone number in the Solomon Islands
    } else if(strlen($number) < 5) {
      $errors[] = $settings['numberShortMsg'];
      craft()->userSession->setFlash('phone', $settings['numberShortMsg']);
    // the phone number entered isn't a number
    } else if(!is_numeric($number)) {
      $errors[] = $settings['numberInvalidMsg'];
      craft()->userSession->setFlash('phone', $settings['numberInvalidMsg']);
    }

    // the message was empty
    if(!strlen($message)) {
      $errors[] = $settings['messageMissingMsg'];
      craft()->userSession->setFlash('message', $settings['messageMissingMsg']);
    }

    // there were errors. exit and output them
    if(!empty($errors)) {
      // if the admin post setting is ajax
      if($settings['ajaxOrRedirect'] === 'ajax') {
        exit('{"success": false, "errors": ' . json_encode($errors) . '}');
      }

      // there were errors, let's retain their input
      craft()->userSession->setFlash('user_number',  $_POST['sms_user_phone']);
      craft()->userSession->setFlash('user_message', $_POST['sms_message']);
    // no errors, send the sms
    } else {
      // Twilio-assigned number
      $from  = $settings['from'];
      // numbers to send to, made into an array delimited by line breaks
      $toArr = explode("\r\n", $settings['to']);

      // sms message body
      $msg  = $settings['msgPrefix'] . "\r\n"; // prefix
      $msg .= "----\r\n";
      $msg .= $message . "\r\n";               // message
      $msg .= $number . "\r\n";                // the number the user filled in the form
      $msg .= "----\r\n";
      $msg .= $settings['msgPostfix'];         // postfix

      // set SID and AuthToken
      $sid  = $settings['sid'];
      $auth = $settings['authToken'];

      // create a Twilio REST client instance
      $twilio = new \Services_Twilio($sid, $auth);

      // send the message to each number
      foreach($toArr as $idx => $to) {
        $twilio->account->sms_messages->create($from, $to, $msg);
      }

      // if admin post setting is redirect and not ajax
      if($settings['ajaxOrRedirect'] === 'redirect' && trim($settings['redirect']) !== '') {
        // redirect
        header('Location: ' . $settings['redirect']);
      }

      // if we made it here, it sent successfully
      echo '{"success": true, "msg": "' . $settings['successMsg'] . '"}';
      // exit to prevent HTML rendering
      exit;
    }
  }

  // this method sends test sms messages from the plugin admin
  public function actionTestSms() {
    // require POST & ajax
    $this->requirePostRequest();
    $this->requireAjaxRequest();

    // include the Twilio REST API
    require_once(CRAFT_PLUGINS_PATH . 'twiliosms/resources/php/Twilio.php');

    // validation error(s)
    $errors  = array();
    // numbers in the "Send Message To" field
    $toArr   = array();
    // the test message
    $message = 'This is a test message from your Twilio SMS plugin';

    // make sure the "from" number is posted
    if(isset($_POST['from'])) {
      // format the number
      $from = $this->format_number($_POST['from']);

      // validate the phone number
      // 5 digits is a valid length for a phone number in the Solomon Islands
      // with country code (required by Twilio) it's 8, and the "+" in front
      // makes the minimum length 9
      if(strlen($from) < 9) {
        $errors[] = 'Twilio Number appears to be invalid.';
      }
    // the number was left empty in the settings
    } else {
      $errors[] = 'Twilio Number is required.';
    }

    // make sure the "to" number is posted
    if(isset($_POST['to']) && trim($_POST['to']) !== '') {
      // numbers to send to, made into an array delimited by line breaks
      $toArr = explode("\n", $_POST['to']);

      // format the numbers
      foreach($toArr as $idx => $to) {
        // format the number (trim and remove dashes)
        $formatted = $this->format_number($to);

        // validate the phone number
        // 5 digits is a valid length for a phone number in the Solomon Islands
        // with country code (required by Twilio) it's 8, and the "+" in front
        // makes the minimum length 9
        if(strlen($formatted) < 9) {
          $errors[] = 'The number ' . $to . ' in the "Send Message To" field is too short.';
        } else {
          $toArr[$idx] = $formatted;
        }
      }
    // the number was left empty in the settings
    } else {
      $errors[] = 'Send Message To number is required.';
    }

    // if they have a prefix setting
    if(isset($_POST['prefix'])) {
      $prefix = trim($_POST['prefix']);
    }

    // if they have a postfix setting
    if(isset($_POST['postfix'])) {
      $postfix = trim($_POST['postfix']);
    }

    // make sure an SID is set
    if(isset($_POST['sid']) && trim($_POST['sid']) !== '') {
      // format the SID
      $sid = trim($_POST['sid']);

      // Twilio SIDs must be exactly 34 characters
      if(strlen($sid) !== 34) {
        $errors[] = 'SID must be 34 characters.';
      }
    // their SID was not 34 characters
    } else {
      $errors[] = 'SID is required.';
    }

    // make sure an auth token is set
    if(isset($_POST['auth']) && trim($_POST['auth']) !== '') {
      // format the auth token
      $auth = trim($_POST['auth']);

      // Twilio auth tokens must be exactly 32 characters
      if(strlen($auth) !== 32) {
        $errors[] = 'Auth token must be 32 characters.';
      }
    // they didn't set an auth token
    } else {
      $errors[] = 'Auth token is required.';
    }

    // there were errors. exit and output them
    if(!empty($errors)) {
      exit('{"success": false, "errors": ' . json_encode($errors) . '}');
    }

    // the message body
    $msg  = $prefix . "\r\n";  // prefix
    $msg .= "----\r\n";
    $msg .= $message . "\r\n"; // message
    $msg .= "1234567890\r\n";  // test number
    $msg .= "----\r\n";
    $msg .= $postfix;          // postfix

    // make a new instance of the Twilio REST client
    $twilio = new \Services_Twilio($sid, $auth);

    // send the message
    foreach($toArr as $idx => $to) {
      $twilio->account->sms_messages->create($from, $to, $msg);
    }

    // if we made it here, it sent successfully
    echo '{"success": true}';

    // exit to prevent HTML rendering
    exit;
  }
}
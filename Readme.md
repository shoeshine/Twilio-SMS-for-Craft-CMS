## Installing ##

 1. Extract `twilio-sms-craft-2.zip` and copy the `/twiliosms` folder to your `/craft/plugins` folder
 2. Navigate to Settings > Plugins in the Craft admin
 3. Enable the Twilio SMS Plugin

## Configuring ##

 1. Navigate to the plugins' settings page (the cog icon)
 2. Input your Twilio Number (the one assigned to you from Twilio). This number *must* include the country code and not have any spaces.
 3. **Send Message To:** your SMS-capable phone number, beginning with the country code. You may add as many numbers as you like (one per line); all numbers added must be added to, and verified in, your Twilio account unless you have a **paid** Twilio subscription. Numbers should not include spaces.
 4. **Message prefix:** an optional field if you'd like to add a custom prefix to all messages sent via the plugin.
 5. **Message postfix:** an optional field if you'd like to append custom text to the end of all messages sent via the plugin.
 6. **SID:** your Twilio-assigned SID.
 7. **Auth Token:** your Twilio-assigned auth token.
 8. **Action after sending SMS:**
	 - Choose **AJAX** to receive a JSON response from the plugin for success or errors on the front-end. This will automatically include `plugins/twiliosms/resources/js/sms-front.js` into your plugin template.
	 - Choose **Redirect** if you'd like to redirect users to a custom page (such as a "Thank You" page) after the SMS has been sent. Errors will also be handled by the plugin with the appropriate template parts (see the "Front-end template" section below).
		 -  You will need to set a **Redirect to** URL if the Redirect option is chosen. This is the page your users will be taken to after the SMS is sent successfully (example: `/sms-thank-you`).
 9. Save your settings or click the *Send a test text* button. Clicking *Send a test text* will send a text to your *Send message to* number(s) to verify that your settings are correct.

## Front-end template example ##

There is a sample front-end template included with the plugin in `/examples/twiliosms.html`. Include this file in your `/craft/templates/` to use it. It includes:

 1. A block for getting error messages from the session (used to display form errors if the "**Redirect**" option is chosen in the admin settings).

 2. A block for displaying error messages for the "**Redirect**" option.
	 - You may remove these blocks if you are using the **AJAX** option in the admin settings.

 3. A div with an ID of `sms-status`, which is used for the **AJAX** option. This div will contain all errors returned by the plugin controller via JSON, or a success message if the SMS message was sent successfully.
	 - You may remove this div if you are using the **Redirect** option in the admin settings.

 4. You can set the success message text in `plugins/twiliosms/resources/js/sms-front.js`, and the error message texts in `plugins/twiliosms/controllers/TwilioSms_SmsController.php` in the `actionSendSms()` method. **Do not** modify the `actionTestSms()` method, as it is used for the *Send a test text* button in the admin settings.

 5. The form itself. Values `{{ user_message }}` and `{{ user_number }}` are automatically set in the event that the Redirect option is chosen in the admin settings and a form error was returned (these are simply the values entered by the user prior to submitting the erroneous form).

## Example template usage ##

 1. Navigate to the Craft admin and choose *Settings > Sections*.
 2. Create a new section with the following values:
	 - **Name:** Twilio SMS
	 - **Handle:** TwilioSms
	 - **Section Type:** Single
	 - **URI:** The URI you wish to use for the front-end; ex: *twilio-sms*
	 - **Entry Template:** twiliosms.html
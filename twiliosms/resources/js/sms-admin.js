// Show error messages on the page
// @param array errors - errors to show
function showSmsErrors(errorList) {
  if(errorList) {
    // open an errors list
    var errors = '<ul class="errors">';

    // create a list item for each error
    errorList.forEach(function(error) {
      errors += '<li>' + error + '</li>';
    });

    // close the errors list
    errors += '</ul>';

    // display errors list
    $('#settings-test-sms-status').html('<strong>The following errors occurred:</strong>' + errors);
  }
}

// when they click the "Send a test button"
$('#settings-sms-test').on('click', function(e) {
  // prevent form submit
  e.preventDefault();

  // craft csrf token
  var csrf    = $('[name="CRAFT_CSRF_TOKEN"]').val();
  // hidden action field (default: twilioSms/Sms/testSms)
  var action  = 'twilioSms/Sms/testSms';
  // "Twilio Number" field
  var from    = $('#settings-from').val();
  // "Send Message To" field
  var to      = $('#settings-to').val();
  // "Message prefix" field
  var prefix  = $('#settings-msgPrefix').val();
  // "Message postfix" field
  var postfix = $('#settings-msgPostfix').val();
  // Twilio SID field
  var sid     = $('#settings-sid').val();
  // Twilio auth token field
  var auth    = $('#settings-authToken').val();
  // the querystring to send
  var data    = '';

  // build the querystring. values must be uri encoded
  data += 'CRAFT_CSRF_TOKEN=' + encodeURIComponent(csrf);    // craft csrf token
  data += '&action='          + encodeURIComponent(action);  // action (default: twilioSms/Sms/testSms)
  data += '&from='            + encodeURIComponent(from);    // "Twilio Number" field
  data += '&to='              + encodeURIComponent(to);      // "Send Message To" field
  data += '&prefix='          + encodeURIComponent(prefix);  // "Message prefix" field
  data += '&postfix='         + encodeURIComponent(postfix); // "Message postfix" field
  data += '&sid='             + encodeURIComponent(sid);     // Twilio SID field
  data += '&auth='            + encodeURIComponent(auth);    // Twilio auth token field

  // send the ajax post
  $.ajax({
    type: 'POST',
    // all Craft post requests must be sent to /
    // with an action in the querystring of pluginHandle/Controller/methodName
    url : '/',
    data: data,
    // send a header saying this is url encoded form data
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    // successfully sent the post
    success: function(result) {
      // parse the response as json
      var res = JSON.parse(result);

      // if the message sent successfully
      if(res.success) {
        // show the success message
        $('#settings-test-sms-status').text('A test message was sent successfully to: ' + to + ' ');
      // there were errors
      } else {
        showSmsErrors(res.errors);
      }
    },
    statusCode: {
      500: function() {
        showSmsErrors(['An unknown error occurred while trying to send the SMS. Please ensure all settings are correct.']);
      }
  }
  }); // end ajax post
}); // end "Send a test text" click event
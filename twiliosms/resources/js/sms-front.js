// wait until the document is loaded
document.addEventListener('DOMContentLoaded', function() {
  // the front-end sms form
  var form = document.getElementById('sms-form');

  // modern browsers
  if(form.addEventListener) {
    form.addEventListener('submit', process_form, false);
  // old IE
  } else if(form.attachEvent) {
    form.attachEvent('onsubmit', process_form);
  }

  // validate the form, format the data and send the ajax post
  function process_form(e) {
    // div which shows success message or error message(s)
    var $statusEl = document.getElementById('sms-status');
    // the phone number entered by the user
    var number    = document.getElementById('sms-number').value;
    // the message entered by the user
    var message   = document.getElementById('sms-message').value;
    // craft csrf token
    var csrf      = document.getElementsByName('CRAFT_CSRF_TOKEN')[0].value;
    // hidden action field (default: twilioSms/Sms/sendSms)
    var action    = document.getElementsByName('action')[0].value;

    // prevent form submit
    e.preventDefault();

    // disable the submit button to prevent double sending
    document.getElementById('sms-submit').disabled = true;

    // create a new ajax instance
    var http = new XMLHttpRequest();
    // all Craft post requests must be sent to /
    // with an action in the querystring of pluginHandle/Controller/methodName
    var url  = '/';
    // querystring to send
    var data = '';

    // encode all our POST data because it gets sent as a querystring
    data += 'CRAFT_CSRF_TOKEN=' + encodeURIComponent(csrf);    // craft csrf token
    data += '&action='          + encodeURIComponent(action);  // action (default: twilioSms/Sms/sendSms)
    data += '&sms_user_phone='  + encodeURIComponent(number);  // phone number entered by the user
    data += '&sms_message='     + encodeURIComponent(message); // message entered by the user

    // prepare ajax post
    http.open('POST', url, true);
    // send a header saying this is url encoded form data
    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    // call anon function when the state changes
    http.onreadystatechange = function() {
      // the post was sent, re-enable the submit button
      document.getElementById('sms-submit').disabled = false;

      // make sure the status was a success
      if(http.readyState === 4 && http.status === 200) {
        // parse the response as json
        var res = JSON.parse(http.responseText);

        // if the message sent successfully
        if(res.success) {
          // reset the form
          form.reset();
          // display the success message
          $statusEl.innerHTML = res.msg;
          $statusEl.style.display = 'block';
        // the back end processor returned errors
        } else {
          // display the error message(s)
          var status = '<strong>The following errors occurred:</strong><br />';
          status += '<ul>';

          res.errors.forEach(function(error) {
            status += '<li>' + error + '</li>';
          });

          status += '</ul>';

          // display errors
          $statusEl.innerHTML = status;
          $statusEl.style.display = 'block';
        }
      }
    } // end http.onreadystatechange

    // send the post
    http.send(data);
  } // end function process_form()
}); // end DOMContentLoaded
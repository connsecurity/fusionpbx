$(function () {
    // window.chatwoot = {};
    // chatwoot.inbox_identifier = inbox_identifier;
    // chatwoot.chatwoot_api_url = "https://chat.connsecurity.com.br/api/v1/";
	// chatwoot.contact_pubsub_token = contact_pubsub_token;
	// chatwoot.account_id = account_id;
	// chatwoot.user_id = user_id;

    // for better performance - to avoid searching in DOM
    var content = $('#content');
    var input = $('#input');
    var status = $('#status');
    var xhttp = new XMLHttpRequest();

    // if user is running mozilla then use it's built-in WebSocket
    window.WebSocket = window.WebSocket || window.MozWebSocket;

    // if browser doesn't support WebSocket, just show some notification and exit
    if (!window.WebSocket) {
        content.html($('<p>', { text: 'Sorry, but your browser doesn\'t '
                                    + 'support WebSockets.'} ));
        input.hide();
        $('span').hide();
        return;
    }

	// open connection
	var connection = new WebSocket('wss://chat.connsecurity.com.br/cable');

	connection.onopen = function () {
		
		// subscribe websocket
        connection.send(JSON.stringify({
			command:"subscribe", 
			identifier: JSON.stringify({ 
				channel: "RoomChannel", 
				pubsub_token: chatwoot.contact_pubsub_token,
				account_id: chatwoot.account_id,
        		user_id: chatwoot.user_id
			})
		}));
        input.removeAttr('disabled');
        status.text('Send Message:');
    };

	connection.onmessage = function(message) {
		try {
            var json = JSON.parse(message.data);
        } catch (e) {
            console.log('This doesn\'t look like a valid JSON: ', message.data);
            return;
        }

		if (json.type === 'ping') {
			//ignore

		} else if (json.type === 'welcome') {
            getConversations();

        } else if (json.message.event === 'message.created') {
			console.log('here comes message', json);
			addMessage(json.message.data.sender.name, json.message.data.content); 

		} else {
			console.log(message.data);
		}
	};

	connection.onerror = function(event) {
	console.error('WebSocket error:', event);
	};

	connection.onclose = function(event) {
	console.log('WebSocket connection closed:', event.code, event.reason);
	};

	/**
     * Send mesage when user presses Enter key
     */
    input.keydown(function(e) {
        if (e.keyCode === 13) {
            var msg = $(this).val();
            if (!msg) {
                return;
            }
            // send the message to chatwoot
            //connection.send(msg);
            //sendMessage(msg);
            addMessage("me", msg)
            $(this).val('');
        }
    });

	/**
     * Add message to the chat window
     */
	function addMessage(author, message) {
        content.append('<p><span>' + author + '</span> @ ' + ':' +  message + '</p>');
        content.scrollTop(1000000);
    }

    // Send Message to contact
    function sendMessage(msg) {
        let url = "TODO";
        xhttp.open("POST", url, false);
        xhttp.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
        xhttp.send(JSON.stringify({content: msg}));
    }

    async function getConversations() {
        let url = "https://chat.connsecurity.com.br/api/v1/accounts/"+chatwoot.account_id+"/conversations";
        let init = {
            method: "GET",
            headers: {
                "Content-Type": "application/json;charset=UTF-8",
                "api_access_token": chatwoot.user_api_access_token
            }
        }
        const response = await fetch(url, init);
        const jsonData = await response.json();
        console.log("getConversations response:");
        console.log(jsonData);
    }
});
$(function () {
    // window.chatwoot = {};
    // chatwoot.inbox_identifier = inbox_identifier;
    // chatwoot.chatwoot_api_url = "https://chat.connsecurity.com.br/api/v1/";
	// chatwoot.contact_pubsub_token = contact_pubsub_token;
	// chatwoot.account_id = account_id;
	// chatwoot.user_id = user_id;

    // for better performance - to avoid searching in DOM
    const $input = $('#message');
    const $conversation_list = $('#conversation_list');
    const $conversation_header = $('#conversation_header');
    const $conversation_messages = $('#conversation_messages');

    // if user is running mozilla then use it's built-in WebSocket
    window.WebSocket = window.WebSocket || window.MozWebSocket;

    // if browser doesn't support WebSocket, just show some notification and exit
    if (!window.WebSocket) {
        console.log('Sorry, but your browser doesn\'t support WebSockets.');
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
    $input.keydown(function(e) {
        if (e.keyCode === 13) {
            var msg = $(this).val();
            if (!msg) {
                return;
            }
            // send the message to chatwoot
            //connection.send(msg);
            sendMessage(msg);
            addMessage("me", msg)
            $(this).val('');
        }
    });

	/**
     * Add message to the chat window
     */
	function addMessage(author, message) {
        //TODO
    }

    // Send Message to contact
    function sendMessage(msg) {
        //TODO
    }

    async function getConversations() {
        let url = `https://chat.connsecurity.com.br/api/v1/accounts/${chatwoot.account_id}/conversations`;
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
        
        // Check the conversations array
        const conversations = jsonData.data.payload;
        if (Array.isArray(conversations) && conversations) {

            conversations.forEach(conversation => {
                $conversation_list.append(`
                                        <div class='conversation_item'>
                                            <div class='name'>${conversation.meta.sender.name}</div>
                                            <div class='last_message'>${conversation.last_non_activity_message.content}</div>
                                        </div>
                                        `);
            });
        } else {
            console.log("Error getting conversations");
            return;
        }

        // Load messages of the first conversation
        $conversation_header.text(conversations[0].meta.sender.name);
        getMessages(conversations[0].id);
    }

    async function getMessages(inbox_id) {
        let url = `https://chat.connsecurity.com.br/api/v1/accounts/${chatwoot.account_id}/conversations/${inbox_id}/messages`;
        let init = {
            method: "GET",
            headers: {
                "Content-Type": "application/json;charset=UTF-8",
                "api_access_token": chatwoot.user_api_access_token
            }
        }
        const response = await fetch(url, init);
        const jsonData = await response.json();
        console.log("getMessages response:");
        console.log(jsonData.payload);

        // Check the messages array
        const messages = jsonData.payload;
        if (Array.isArray(messages) && messages) {

            messages.forEach(message => {
                $conversation_messages.append(`
                                        <div class='message ${message.message_type ? 'sent' : 'received'}'>
                                            ${message.content}
                                        </div>
                                        `);
            });
        } else {
            console.log("Error getting messages");
            return;
        }
    }
});
(function () {
    // window.chatwoot = {};
    // chatwoot.inbox_identifier = inbox_identifier;
    // chatwoot.chatwoot_api_url = "https://chat.connsecurity.com.br/api/v1/";
	// chatwoot.contact_pubsub_token = contact_pubsub_token;
	// chatwoot.account_id = account_id;
	// chatwoot.user_id = user_id;

    // for better performance - to avoid searching in DOM
    const message_input = document.getElementById('message_input');
    const conversation_list = document.getElementById('conversation_list');
    const conversation_header = document.getElementById('conversation_header');
    const conversation_messages = document.getElementById('conversation_messages');

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
    //TODO

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
                
                let conversation_item = createElement("div", "conversation_item");
                let name = createElement("div", "name", conversation.meta.sender.name);
                let last_message = createElement("div", "last_message", conversation.last_non_activity_message.content);

                conversation_item.appendChild(name);
                conversation_item.appendChild(last_message);
                conversation_list.appendChild(conversation_item);
            });
        } else {
            console.log("Error getting conversations");
            return;
        }

        // Load messages of the first conversation
        conversation_header.textContent = conversations[0].meta.sender.name;
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
                let message_elem = createElement("div", `message ${message.message_type ? 'sent' : 'received'}`, message.content);
                conversation_messages.appendChild(message_elem);
            });
        } else {
            console.log("Error getting messages");
            return;
        }
    }

    function createElement(tagName, classes, content) {
        const elem = document.createElement(tagName);
        elem.className = classes;
        elem.textContent = content;
        return elem;
    }
})();
(function () {
    // window.chatwoot = {};
    // chatwoot.inbox_identifier = inbox_identifier;
    // chatwoot.chatwoot_api_url = "https://chat.connsecurity.com.br/api/v1/";
    // chatwoot.contact_pubsub_token = contact_pubsub_token;
    // chatwoot.account_id = account_id;
    // chatwoot.user_id = user_id;

    // for better performance - to avoid searching in DOM
    const message_input_elem = document.getElementById('message_input');
    const send_button_elem = document.getElementById('send_button');
    const conversation_list_elem = document.getElementById('conversation_list');
    const conversation_list = [];
    const conversation_header_elem = document.getElementById('conversation_header');
    const conversation_messages_elem = document.getElementById('conversation_messages');
    let active_conversation_elem;

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
            command: "subscribe",
            identifier: JSON.stringify({
                channel: "RoomChannel",
                pubsub_token: chatwoot.contact_pubsub_token,
                account_id: chatwoot.account_id,
                user_id: chatwoot.user_id
            })
        }));
    };

    connection.onmessage = function (message) {
        try {
            var json = JSON.parse(message.data);
        } catch (e) {
            console.log('This doesn\'t look like a valid JSON: ', message.data);
            return;
        }

        if (json.type === 'ping') {
            return;
        }

        if (json.type === 'welcome') {
            return;
        }

        if (json.type === 'confirm_subscription') {
            getConversations();
            return;
        }

        if (json.message.event === 'conversation.created') {
            console.log('New Conversation');
            createConversation(json.message.data);
            return;
        }

        if (json.message.event === 'message.created') {
            console.log('New Message');
            handleMessage(json.message.data);
            return;
        }

        console.log(json.message);
    };    

    connection.onerror = function (event) {
        console.error('WebSocket error:', event);
    };

    connection.onclose = function (event) {
        console.log('WebSocket connection closed:', event.code, event.reason);
    };

    function handleMessage(message) {
        if (message.account_id !== chatwoot.account_id) {
            console.log("this is not mine");
            return;
        }

        const conversation_item_elem = findConversationById(message.conversation_id);

        if (conversation_item_elem) {
            updateLastMessage(conversation_item_elem, message.content);
        }

        if (message.conversation_id === active_conversation_elem.conversation_id) {
            appendMessage(message.content, message.message_type);
            return;
        }
    }

    function createConversation(data) {
        appendConversation(data.id, data.meta.sender.name);
    }

    async function getConversations() {
        let url = `https://chat.connsecurity.com.br/api/v1/accounts/${chatwoot.account_id}/conversations`;
        const jsonData = await request(url, "GET");
        console.log("getConversations response:");
        console.log(jsonData);

        // Check the conversations array
        const conversations = jsonData.data.payload;
        if (Array.isArray(conversations) && conversations.length) {
            conversations.forEach(conversation => {
                appendConversation(conversation.id, conversation.meta.sender.name, conversation.last_non_activity_message.content);
            });
        } else {
            console.log("Error getting conversations");
            return;
        }

        // Load first conversation
        makeConversationActive(conversation_list_elem.firstElementChild);
    }

    async function getMessages(conversation_id) {
        let url = `https://chat.connsecurity.com.br/api/v1/accounts/${chatwoot.account_id}/conversations/${conversation_id}/messages`;
        const jsonData = await request(url, "GET");
        console.log("getMessages response:");
        console.log(jsonData.payload);

        // Check the messages array
        const messages = jsonData.payload;
        if (Array.isArray(messages) && messages.length) {
            messages.forEach(message => {
                appendMessage(message.content, message.message_type);
            });
        } else {
            console.log("Error getting messages");
            return;
        }
    }

    function appendConversation(id, name, last_message) {
        let name_elem = createElement("div", "name", name);
        let last_message_elem = createElement("div", "last_message", last_message);
        let conversation_item_elem = createElement("div", "conversation_item");

        conversation_item_elem.conversation_id = id;
        conversation_item_elem.appendChild(name_elem);
        conversation_item_elem.appendChild(last_message_elem);

        conversation_list.push(conversation_item_elem);
        conversation_list_elem.appendChild(conversation_item_elem);
    }

    function appendMessage(content, type) {
        let message_elem = createElement("div", `message ${type ? 'sent' : 'received'}`, content);
        conversation_messages_elem.appendChild(message_elem);
    }

    function createElement(tagName, classes, content) {
        const elem = document.createElement(tagName);
        elem.className = classes;
        elem.textContent = content;
        return elem;
    }

    function makeConversationActive(conversation_elem) {

        if (active_conversation_elem) {
            active_conversation_elem.classList.remove("active");
        }
        active_conversation_elem = conversation_elem;
        active_conversation_elem.classList.add("active");

        updateConversationHeader(active_conversation_elem);
        emptyMessages();
        getMessages(active_conversation_elem.conversation_id);
    }

    function updateConversationHeader(conversation_elem) {
        conversation_header_elem.textContent = conversation_elem.querySelector(".name").textContent;
    }

    function emptyMessages() {
        conversation_messages_elem.replaceChildren();
    }

    function findConversationById(conversation_id) {
        return conversation_list.find(conversation => { return conversation.conversation_id === conversation_id });
    }

    function updateLastMessage(conversation_item_elem, message) {
        conversation_item_elem.querySelector(".last_message").textContent = message;
    }

    async function sendMessage(message, conversation_id) {
        clearInput();
        postMessage(message, conversation_id);
    }

    function clearInput() {
        message_input_elem.value = "";
    }

    async function postMessage(message, conversation_id) {
        let url = `https://chat.connsecurity.com.br/api/v1/accounts/${chatwoot.account_id}/conversations/${conversation_id}/messages`;
        let body = {
            content: message
        };
        const jsonData = await request(url, "POST", body);
        console.log(jsonData);
    }
    
    async function request(url, method, body) {
        let init = {
            method: method,
            headers: {
                "Content-Type": "application/json;charset=UTF-8",
                "api_access_token": chatwoot.user_api_access_token
            },
            body: JSON.stringify(body)
        };
        try {
            const response = await fetch(url, init);
            return await response.json();

        } catch (error) {
            console.error(error);
        }
    }

    /**
     * Listeners
     */
    conversation_list_elem.addEventListener("click", function (e) {
        let conversation_item_elem = e.target.closest(".conversation_item");
        makeConversationActive(conversation_item_elem);
    });

    message_input_elem.addEventListener("keyup", function (e) {
        if (e.key === "Enter") {
            sendMessage(message_input_elem.value, active_conversation_elem.conversation_id);
        }        
    });

    send_button_elem.addEventListener("click", function () {
        sendMessage(message_input_elem.value, active_conversation_elem.conversation_id);
    });

})();
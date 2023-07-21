(function () {
    const message_input_elem = document.getElementById('message_input');
    const send_button_elem = document.getElementById('send_button');
    const conversation_list_elem = document.getElementById('conversation_list');
    const conversation_list = [];
    const contact_name_elem = document.getElementById('contact_name');
    const chat_messages_elem = document.getElementById('chat_messages');
    const default_picture_elem = createElement("img", "picture");
    default_picture_elem.src = "user_icon_3.svg?v=2";
    let active_conversation_elem;

    // if user is running mozilla then use it's built-in WebSocket
    window.WebSocket = window.WebSocket || window.MozWebSocket;

    // if browser doesn't support WebSocket, just show some notification and exit
    if (!window.WebSocket) {
        console.log('Sorry, but your browser doesn\'t support WebSockets.');
        return;
    }

    // open connection
    var connection = new WebSocket(chatwoot.websocket_url);

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

    connection.onmessage = async function (message) {
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
            await getConversations();
            makeConversationActive(conversation_list_elem.querySelector(".conversation_item"));
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

        if (json.message.event === 'conversation.status_changed') {
            console.log('status change');
            changeConversationStatus(json.message.data);
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
        if (message.account_id != chatwoot.account_id) {
            console.log("this is not mine");
            return;
        }

        const conversation_item_elem = findConversationById(message.conversation_id);

        if (conversation_item_elem) {
            updateLastMessage(conversation_item_elem, message.content, message.created_at);
        }

        if (message.conversation_id === active_conversation_elem.conversation_id) {
            appendMessage(message.content, message.message_type, message.created_at);
            return;
        }
    }

    function createConversation(data) {
        appendConversation(data.id, data.meta.sender.name);
    }

    async function getConversations() {
        let url = `handlers/conversations.php`;
        const jsonData = await request(url, "GET");
        console.log("getConversations response:");
        console.log(jsonData);

        // Check the conversations array
        const conversations = jsonData.payload || jsonData.data.payload;
        if (Array.isArray(conversations) && conversations.length) {
            conversations.forEach(conversation => {
                appendConversation(conversation.id,
                    conversation.meta.sender.name,
                    conversation.last_non_activity_message.content,
                    conversation.last_non_activity_message.created_at,
                    conversation.status);
            });
        } else {
            console.log("Error getting conversations");
            return;
        }
    }

    function changeConversationStatus(conversation) {
        const status_list = /open|pending|snoozed|resolved/;
        let conversation_item_elem = findConversationById(conversation.id);
        conversation_item_elem.className = conversation_item_elem.className.replace(status_list, conversation.status);
    }

    var earliest_message_id;
    async function getMessages(conversation_id, before = false) {
        if (before) {
            var url = `handlers/messages.php?id=${conversation_id}&before=${earliest_message_id}`;
        } else {
            var url = `handlers/messages.php?id=${conversation_id}`;
        }
        const jsonData = await request(url, "GET");
        console.log("getMessages response:");
        console.log(jsonData.payload);

        // Update earliest_message_id
        earliest_message_id = jsonData.payload[0].id;

        // Check the messages array
        const messages = jsonData.payload;
        if (Array.isArray(messages) && messages.length) {
            if (before) {
                messages.reverse().forEach(message => {
                    appendMessage(message.content, message.message_type, message.created_at, true);
                });
            } else {
                messages.forEach(message => {
                    appendMessage(message.content, message.message_type, message.created_at);
                });
            }
        } else {
            console.log("Error getting messages");
            return;
        }
    }

    function appendConversation(id, name, last_message, last_message_epoch, status) {
        //status = ["open", "resolved", "pending", "snoozed"]

        let picture_elem = default_picture_elem.cloneNode();
        let contact_elem = createElement("div", "contact");
        let name_elem = createElement("div", "name", name);
        let last_message_elem = createElement("div", "last_message", last_message);
        let last_message_time = convert_epoch_to_local(last_message_epoch);
        let last_message_time_elem = createElement("div", "time", last_message_time);
        let conversation_item_elem = createElement("div", `conversation_item ${status}`);

        contact_elem.appendChild(name_elem);
        contact_elem.appendChild(last_message_elem);

        conversation_item_elem.conversation_id = id;
        conversation_item_elem.appendChild(picture_elem);
        conversation_item_elem.appendChild(contact_elem);
        conversation_item_elem.appendChild(last_message_time_elem);

        conversation_list.push(conversation_item_elem);
        conversation_list_elem.appendChild(conversation_item_elem);
    }

    function appendMessage(content, type, message_epoch, prepend = false) {

        const type_class = {
            0: 'received',
            1: 'sent',
            2: 'system',
        };
        let creation_time = convert_epoch_to_local(message_epoch);

        let message_time_elem = createElement("div", `message_time`, creation_time);
        let message_elem = createElement("div", `message ${type_class[type]}`, content);
        message_elem.appendChild(message_time_elem);

        if (prepend) {
            chat_messages_elem.prepend(message_elem);
        } else {
            chat_messages_elem.appendChild(message_elem);
            scrollToBottom();
        }
    }

    function scrollToBottom() {
        chat_messages_elem.scrollTop = chat_messages_elem.scrollHeight;
    }

    function scrollTo(height) {
        chat_messages_elem.scrollTop = chat_messages_elem.scrollHeight - height;
    }

    function createElement(tagName, classes, content) {
        const elem = document.createElement(tagName);
        elem.className = classes;
        elem.textContent = content;
        return elem;
    }

    async function makeConversationActive(conversation_elem) {
        if (active_conversation_elem) {
            active_conversation_elem.classList.remove("active");
        }
        active_conversation_elem = conversation_elem;
        active_conversation_elem.classList.add("active");

        updateChatHeader(active_conversation_elem);
        emptyMessages();
        await getMessages(active_conversation_elem.conversation_id);

        //get older messages if scroll is not activated
        while (!isScrollActivated() && earliest_message_id > 1) {
            await getMessages(active_conversation_elem.conversation_id, true);
            scrollToBottom();
        }
    }

    function updateChatHeader(conversation_elem) {
        contact_name_elem.textContent = conversation_elem.querySelector(".name").textContent;
    }

    function emptyMessages() {
        chat_messages_elem.replaceChildren();
    }

    function findConversationById(conversation_id) {
        return conversation_list.find(conversation => { return conversation.conversation_id === conversation_id });
    }

    function updateLastMessage(conversation_item_elem, message, message_epoch) {
        let message_time = convert_epoch_to_local(message_epoch);
        conversation_item_elem.querySelector(".time").textContent = message_time;
        conversation_item_elem.querySelector(".last_message").textContent = message;
    }

    async function sendMessage(message, conversation_id) {
        clearInput();
        if (message && message.trim()) {
            postMessage(message, conversation_id);
        }
    }

    function clearInput() {
        message_input_elem.value = "";
    }

    function convert_epoch_to_local(epoch_time) {
        let date = new Date(epoch_time * 1000);
        let hour = date.getHours();
        let minute = "0" + date.getMinutes();
        let local_time = hour + ':' + minute.slice(-2);
        return local_time;
    }

    async function postMessage(message, conversation_id) {
        let url = `handlers/messages.php?id=${conversation_id}`;
        let body = {
            content: message
        };
        const jsonData = await request(url, "POST", body);
        console.log(jsonData);
    }

    async function request(url, method, body) {
        let init = {
            method: method,
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
        if (conversation_item_elem) {
            makeConversationActive(conversation_item_elem);
        }
    });

    message_input_elem.addEventListener("keydown", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            sendMessage(message_input_elem.value, active_conversation_elem.conversation_id);
        }
    });

    send_button_elem.addEventListener("click", function (e) {
        e.preventDefault();
        sendMessage(message_input_elem.value, active_conversation_elem.conversation_id);
    });

    //get older messages when scroll to top
    chat_messages_elem.addEventListener("scroll", async function (e) {
        if (e.target.scrollTop === 0 && earliest_message_id > 1) {
            const scrollOffset = chat_messages_elem.scrollHeight - chat_messages_elem.scrollTop;
            await getMessages(active_conversation_elem.conversation_id, true);
            scrollTo(scrollOffset);
        }
    });

    //detect chat_messages_elem has scroll activated
    function isScrollActivated() {
        return chat_messages_elem.scrollHeight > chat_messages_elem.clientHeight;
    };
})();
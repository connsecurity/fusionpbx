(function () {
    const message_input_elem = document.getElementById('message_input');
    const whatsapp_templates_button_elem = document.getElementById('templates_button');
    const send_button_elem = document.getElementById('send_button');
    const conversation_list_elem = document.getElementById('conversation_list');
    const conversations_data = new Map();
    const contact_name_elem = document.getElementById('contact_name');
    const dropdown_pane_elem = document.getElementById('dropdown_pane');
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

        if (message.conversation_id === getConversationId(active_conversation_elem)) {
            appendMessage(message.content, message.message_type, message.created_at);
            return;
        }
    }

    function createConversation(conversation) {
        const conversationData = {
            id: conversation.id,
            name: conversation.meta.sender.name,
            last_message: conversation.last_non_activity_message.content,
            last_message_epoch: conversation.last_non_activity_message.created_at,
            status: conversation.status,
            inbox_id: conversation.inbox_id
        };

        conversations_data.set(conversation.id, conversationData);

        const conversation_elem = createConversationElement(conversationData);
        appendConversation(conversation_elem);
    }

    async function getConversations() {
        const path = `chat/conversations.php`;
        const jsonData = await request(path, "GET");
        console.log("getConversations response:");
        console.log(jsonData);

        // Check the conversations array
        const conversations = jsonData.payload || jsonData.data.payload;
        if (Array.isArray(conversations) && conversations.length) {
            conversations.forEach(conversation => {
                createConversation(conversation);
            });
        } else {
            console.log("Error getting conversations");
            return;
        }
    }

    function changeConversationStatus(conversation) {
        const status_list = /open|pending|snoozed|resolved/;
        let conversation_item_elem = findConversationById(conversation.id);
        const conversation_data = conversations_data.get(conversation.id);
        conversation_data.status = conversation.status;
        conversation_item_elem.className = conversation_item_elem.className.replace(status_list, conversation.status);

        if (conversation.id == getConversationId(active_conversation_elem)) {
            updateButtons();
        }
    }

    var earliest_message_id;
    async function getMessages(conversation_id, before = false) {
        const path = before
            ? `chat/messages.php?id=${conversation_id}&before=${earliest_message_id}`
            : `chat/messages.php?id=${conversation_id}`;
        const jsonData = await request(path, "GET");
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

    function appendConversation(conversation_elem) {        
        conversation_list_elem.appendChild(conversation_elem);
    }

    function createDefaultPictureElement() {
        return default_picture_elem.cloneNode();
    }

    function createContactElement(name, last_message) {
        let contact_elem = createElement("div", "contact");
        let name_elem = createElement("p", "name", name);
        let last_message_elem = createElement("p", "last_message", last_message);
        contact_elem.appendChild(name_elem);
        contact_elem.appendChild(last_message_elem);
        return contact_elem;
    }

    function createLastMessageTimeElement(last_message_epoch) {
        let last_message_time = convert_epoch_to_local(last_message_epoch);
        let last_message_time_elem = createElement("div", "time", last_message_time);
        return last_message_time_elem;
    }

    function createConversationElement(conversation) {
        const { id, name, last_message, last_message_epoch, status } = conversation;

        let picture_elem = createDefaultPictureElement();
        let contact_elem = createContactElement(name, last_message);
        let last_message_time_elem = createLastMessageTimeElement(last_message_epoch);

        let conversation_item_elem = createElement("div", `conversation_item ${status}`);
        conversation_item_elem.appendChild(picture_elem);
        conversation_item_elem.appendChild(contact_elem);
        conversation_item_elem.appendChild(last_message_time_elem);
        conversation_item_elem.id = `conversation_${id}`;

        return conversation_item_elem;
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
        updateButtons();
        emptyMessages();
        await getMessages(getConversationId(active_conversation_elem));

        //get older messages if scroll is not activated
        while (!isScrollActivated() && earliest_message_id > 1) {
            await getMessages(getConversationId(active_conversation_elem), true);
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
        return document.getElementById(`conversation_${conversation_id}`);
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

    const status_map = {
        resolved: { status: "resolved" },
        open: { status: "open" },
        mark_as_pending: { status: "pending" },
        snooze_next_reply: { status: "snoozed" },
        snooze_tomorrow: { status: "snoozed", snooze_until: getTomorrowEpoch() },
        snooze_next_week: { status: "snoozed", snooze_until: getNextWeekEpoch() }
    };
    async function toggleConversationStatus(status_option, conversation_id) {
        const { status, snooze_until } = status_map[status_option];
        const path = 'chat/conversations.php';
        const body = {
            action: 'toggle_status',
            conversation_id: conversation_id,
            status: status,
            snooze_until: snooze_until
        };
        const jsonData = await request(path, "POST", body);
        console.log(jsonData);
    }

    function getTomorrowEpoch() {
        let tomorrow = new Date();
        tomorrow.setHours(24,0,0,0);
        return tomorrow.getTime()/1000;
    }

    function getNextWeekEpoch() {
        let next_week = new Date();
        next_week.setHours(24*7,0,0,0);
        return next_week.getTime()/1000;
    }

    function getConversationId(conversation_elem) {
        return +conversation_elem.id.slice(13);
    }

    function clearInput() {
        message_input_elem.value = "";
    }

    function toggleDropdownPane() {
        if (dropdown_pane_elem.style.display === "none") {
            dropdown_pane_elem.style.display = "flex";
        } else {
            dropdown_pane_elem.style.display = "none";
        } 
    }

    function convert_epoch_to_local(epoch_time) {
        let date = new Date(epoch_time * 1000);
        let hour = date.getHours();
        let minute = "0" + date.getMinutes();
        let local_time = hour + ':' + minute.slice(-2);
        return local_time;
    }

    async function postMessage(message, conversation_id, template_params = null) {
        const path = `chat/messages.php?id=${conversation_id}`;
        const body = {
            content: message,
            template_params: template_params
        };
        const jsonData = await request(path, "POST", body);
        console.log(jsonData);
    }

    async function getWhatsappTemplates() {
        const active_conversation_data = conversations_data.get(getConversationId(active_conversation_elem));
        const path = `chat/templates.php?inbox_id=${active_conversation_data.inbox_id}`;
        return request(path, "GET");
    }

    async function request(path, method, body) {
        const url = `/api/${path}`;
        const init = {
            method: method,
            body: JSON.stringify(body)
        };
        try {
            const response = await fetch(url, init);
            return response.json();

        } catch (error) {
            console.error(error);
        }
    }

    /**
     * Buttons
     */

    const action_button_elem = document.getElementById('action_button');
    const options_button_elem = document.getElementById('options_button');

    function updateButtons() {
        const active_conversation_data = conversations_data.get(getConversationId(active_conversation_elem));
        //if conversation is open, button to resolve, else button to open
        if (active_conversation_data.status === "open") {
            action_button_elem.value = "resolved";
            action_button_elem.className = "resolved";
            action_button_elem.textContent = chatwoot.label_resolve;            
        } else {
            action_button_elem.value = "open";
            action_button_elem.className = "open";
            action_button_elem.textContent = chatwoot.label_open;
        }
    }

    /**
     * Templates Modal
     */
    const templates_list_elem = document.getElementById("template_list");
    const template_process_elem = document.getElementById("template_process");
    const template_content_elem = document.getElementById("template_content");
    const templates_back_button_elem = document.getElementById('templates_back_button');
    const templates_send_button_elem = document.getElementById('templates_send_button');
    let selected_template = null;
    const templates_data = new Map();

    async function openTemplatesModal() {
        templates_list_elem.replaceChildren();
        const templates = await getWhatsappTemplates();
        if (Array.isArray(templates) && templates.length) {
            templates.forEach(template => {
                appendTemplate(template);
            });
        }
        modal_open('modal_templates');
    }

    function appendTemplate(template) {
        const {name, language, components, category} = template;
        const template_elem = createElement("div", "whatsapp_template");

        //header
        const template_name_elem = createElement("span", "template_name", name);
        const template_language_elem = createElement("span", "template_language", language);
        const template_category_elem = createElement("span", "template_category", category);        
        const template_header_elem = createElement("div", "template_header");
        template_header_elem.appendChild(template_name_elem);        
        template_header_elem.appendChild(template_language_elem);
        template_header_elem.appendChild(template_category_elem);
        template_elem.appendChild(template_header_elem);

        //components
        const template_components_elem = createElement("div", "template_components");
        components.forEach(component => {
            const {type, text} = component;

            const component_type_elem = createElement("span", "component_type", type);
            template_components_elem.appendChild(component_type_elem);

            const component_text_elem = createElement("span", "component_text", text);
            template_components_elem.appendChild(component_text_elem);
        });
        template_elem.appendChild(template_components_elem);        

        //save template data for later use
        templates_data.set(template_elem, template);

        templates_list_elem.appendChild(template_elem);
    }

    function showTemplate(template) {
        const {name, components} = template;

        components.forEach(component => {
            const {type, text} = component;
            if (type == "BODY") {
                //append text to template_content_elem as textarea
                const template_text_elem = createElement("textarea", type, text);
                template_text_elem.readOnly = true;
                template_content_elem.appendChild(template_text_elem);                
            }
            else {
                //append text to template_content_elem as span
                const template_text_elem = createElement("span", type, text);
                template_content_elem.appendChild(template_text_elem);
            }
        });
        
        //hide template list and show template content
        templates_list_elem.style.display = "none";
        template_process_elem.style.display = "flex";
    }

    function sendTemplate(template) {
        const {name, language, components, category} = template;
        //get text from the component with type BODY
        let message = "";
        components.forEach(component => {
            const {type, text} = component;
            if (type == "BODY") {
                message = text;
            }
        });
        //get template params
        const template_params = {
            name: name,
            language: language,
            category: category,
            processed_params: {
                //TODO: get params from template
            }
        };
        //send template message
        postMessage(message, getConversationId(active_conversation_elem), template_params);
    }


    function hideTemplate() {
        //clear template content
        template_content_elem.replaceChildren();
        //hide template content and show template list
        templates_list_elem.style.display = "flex";
        template_process_elem.style.display = "none";
    }

    /**
     * Listeners
     */

    //select conversation
    conversation_list_elem.addEventListener("click", function (e) {
        let conversation_item_elem = e.target.closest(".conversation_item");
        if (conversation_item_elem) {
            makeConversationActive(conversation_item_elem);
        }
    });

    //send message on enter
    message_input_elem.addEventListener("keydown", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            sendMessage(message_input_elem.value, getConversationId(active_conversation_elem));
        }
    });

    //send message
    send_button_elem.addEventListener("click", function (e) {
        e.preventDefault();
        sendMessage(message_input_elem.value, getConversationId(active_conversation_elem));
    });

    //get older messages when scroll to top
    chat_messages_elem.addEventListener("scroll", async function (e) {
        if (e.target.scrollTop === 0 && earliest_message_id > 1) {
            const scrollOffset = chat_messages_elem.scrollHeight - chat_messages_elem.scrollTop;
            await getMessages(getConversationId(active_conversation_elem), true);
            scrollTo(scrollOffset);
        }
    });

    //toggle conversation status
    action_button_elem.addEventListener("click", function (e) {
        toggleConversationStatus(action_button_elem.value, getConversationId(active_conversation_elem));
    });

    //open dropdown pane
    options_button_elem.addEventListener("click", function (e) {
        e.stopPropagation();
        toggleDropdownPane();
    });   
    
    //close dropdown pane when clicking outside
    document.addEventListener("click", function (e) {
        if (!dropdown_pane_elem.contains(e.target)) {
            dropdown_pane_elem.style.display = "none";
        }
    });
    
    //dropdown options
    dropdown_pane_elem.addEventListener("click", function (e) {
        const selected_button = e.target.closest("button");
        console.log (selected_button);
        toggleConversationStatus(selected_button.id, getConversationId(active_conversation_elem));
    });

    //detect chat_messages_elem has scroll activated
    function isScrollActivated() {
        return chat_messages_elem.scrollHeight > chat_messages_elem.clientHeight;
    };

    //open whatsapp templates modal
    whatsapp_templates_button_elem.addEventListener("click", function (e) {
        openTemplatesModal();
    });

    //go back to templates list
    templates_back_button_elem.addEventListener("click", function (e) {
        hideTemplate();
        selected_template = null;
    });

    //select template
    templates_list_elem.addEventListener("click", function (e) {
        const selected_template_elem = e.target.closest(".whatsapp_template");
        selected_template = templates_data.get(selected_template_elem);
        showTemplate(selected_template);
        
    });

    //send template
    templates_send_button_elem.addEventListener("click", function (e) {
        if (selected_template){
            sendTemplate(selected_template);
        }
    });
})();
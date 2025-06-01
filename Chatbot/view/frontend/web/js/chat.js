define([
    'jquery',
    'mage/template',
    'jquery/ui'
], function($, mageTemplate) {
    'use strict';
    
    $.widget('iu.chatbot', {
        options: {
            webhookUrl: '',
            projectId: '',
            sessionId: ''
        },

        _create: function() {
            this._initElements();
            this._bindEvents();
        },

        _initElements: function() {
            this.chatbotBtn = this.element.find('#iu-chatbot-btn');
            this.chatbotWindow = this.element.find('#iu-chatbot-window');
            this.chatbotClose = this.element.find('#iu-chatbot-close');
            this.chatbotMessages = this.element.find('#iu-chatbot-messages');
            this.chatbotInput = this.element.find('#iu-chatbot-input');
            this.chatbotSend = this.element.find('#iu-chatbot-send');
        },

        _bindEvents: function() {
            this.chatbotBtn.on('click', this._toggleChat.bind(this));
            this.chatbotClose.on('click', this._closeChat.bind(this));
            this.chatbotSend.on('click', this._sendMessage.bind(this));
            this.chatbotInput.on('keypress', this._handleKeypress.bind(this));
            $(document).on('click', this._handleOutsideClick.bind(this));
        },

        _toggleChat: function() {
            this.chatbotWindow.toggleClass('active');
            this.chatbotInput.focus();
            this._scrollToBottom();
        },

        _closeChat: function() {
            this.chatbotWindow.removeClass('active');
        },

        _addMessage: function(message, sender = 'user') {
            const msgDiv = $('<div>').addClass('iu-chat-message')
                .addClass(sender === 'user' ? 'iu-chat-user' : 'iu-chat-bot')
                .text(message);
                
            this.chatbotMessages.append(msgDiv);
            this._scrollToBottom();
        },

        _showLoading: function() {
            const loadingDiv = $('<div>').addClass('iu-chatbot-loading');
            loadingDiv.append(
                $('<span>'), $('<span>'), $('<span>')
            );
            this.chatbotMessages.append(loadingDiv);
            this._scrollToBottom();
            return loadingDiv;
        },

        _scrollToBottom: function() {
            this.chatbotMessages.scrollTop(this.chatbotMessages[0].scrollHeight);
        },

        _sendToDialogflow: function(text) {
            const loading = this._showLoading();
            
            $.ajax({
                url: this.options.webhookUrl,
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    text: text
                }),
                dataType: 'json',
                success: function(response) {
                    loading.remove();
                    if (response.fulfillmentText) {
                        this._addMessage(response.fulfillmentText, 'bot');
                    }
                }.bind(this),
                error: function() {
                    loading.remove();
                    this._addMessage("Sorry, I'm having trouble connecting. Please try again later.", 'bot');
                }.bind(this)
            });
        },

        _sendMessage: function() {
            const message = this.chatbotInput.val().trim();
            if (!message) return;
            
            this._addMessage(message, 'user');
            this.chatbotInput.val('');
            this._sendToDialogflow(message);
        },

        _handleKeypress: function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this._sendMessage();
            }
        },

        _handleOutsideClick: function(e) {
            if (!$(e.target).closest('#iu-chatbot-container').length && 
                this.chatbotWindow.hasClass('active')) {
                this._closeChat();
            }
        }
    });

    return $.iu.chatbot;
});
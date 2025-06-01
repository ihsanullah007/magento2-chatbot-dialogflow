var config = {
    map: {
        '*': {
            'iuChatbot': 'IU_Chatbot/js/chat'
        }
    },
    shim: {
        'IU_Chatbot/js/chat': {
            deps: ['jquery']
        }
    }
};
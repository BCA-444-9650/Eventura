/**
 * Eventura - Chatbot JavaScript
 * Rule-based FAQ chatbot
 */

const chatbotResponses = {
    greetings: [
        'Hello! How can I help you with events today?',
        'Hi there! What would you like to know about our events?',
        'Welcome! I\'m here to help with any event-related questions.'
    ],
    
    event_questions: {
        keywords: ['event', 'events', 'upcoming', 'what events'],
        response: 'You can browse all upcoming events in the Events section. Click on "Events" in the navigation menu to see what\'s available!'
    },
    
    registration_questions: {
        keywords: ['register', 'registration', 'how to register', 'sign up'],
        response: 'To register for an event: 1) Go to Events page, 2) Find an event you like, 3) Click "Register Now" button. Your QR ticket will be generated automatically!'
    },
    
    qr_questions: {
        keywords: ['qr', 'ticket', 'code', 'scan', 'entry'],
        response: 'After registering, you\'ll get a unique QR code ticket. Show this at the event entry for validation. Each QR code can only be used once for entry and once for food (if available).'
    },
    
    food_questions: {
        keywords: ['food', 'meal', 'lunch', 'dinner', 'eat'],
        response: 'Events marked with "Food Available" include meal coupons in your QR ticket. Present your QR code at the food counter to redeem.'
    },
    
    timing_questions: {
        keywords: ['time', 'when', 'schedule', 'duration'],
        response: 'Each event shows its date and time on the event card. Make sure to arrive on time as entry validation closes 30 minutes after event start time.'
    },
    
    venue_questions: {
        keywords: ['where', 'venue', 'location', 'place', 'address'],
        response: 'The venue location is displayed on each event card. You can also see it on your ticket after registration.'
    },
    
    cancel_questions: {
        keywords: ['cancel', 'unregister', 'remove registration'],
        response: 'Please contact an admin or teacher if you need to cancel your registration. You can find contact info on your profile page.'
    },
    
    help_questions: {
        keywords: ['help', 'support', 'contact', 'problem', 'issue'],
        response: 'For additional help, please contact the system administrator at admin@eventura.com or speak to a teacher.'
    },
    
    thanks: {
        keywords: ['thank', 'thanks'],
        response: 'You\'re welcome! Enjoy the event! 🎉'
    },
    
    bye: {
        keywords: ['bye', 'goodbye', 'see you'],
        response: 'Goodbye! Have a great day! 👋'
    }
};

let chatOpen = false;

function toggleChatbot() {
    const body = document.getElementById('chatbotBody');
    const toggle = document.getElementById('chatbotToggle');
    chatOpen = !chatOpen;
    
    if (chatOpen) {
        body.classList.add('show');
        toggle.classList.remove('fa-chevron-up');
        toggle.classList.add('fa-chevron-down');
        setTimeout(() => document.getElementById('chatInput')?.focus(), 100);
    } else {
        body.classList.remove('show');
        toggle.classList.remove('fa-chevron-down');
        toggle.classList.add('fa-chevron-up');
    }
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Add user message
    addMessage(message, 'user');
    input.value = '';
    
    // Get bot response
    const response = getBotResponse(message);
    
    // Add bot response after short delay
    setTimeout(() => addMessage(response, 'bot'), 500);
}

function addMessage(text, sender) {
    const container = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}`;
    messageDiv.innerHTML = `<div class="message-content">${text}</div>`;
    container.appendChild(messageDiv);
    container.scrollTop = container.scrollHeight;
}

function getBotResponse(input) {
    const lowerInput = input.toLowerCase();
    
    // Check for greetings
    if (/^(hi|hello|hey|greetings)/.test(lowerInput)) {
        return getRandomResponse(chatbotResponses.greetings);
    }
    
    // Check all question categories
    for (const category in chatbotResponses) {
        if (category === 'greetings') continue;
        
        const categoryData = chatbotResponses[category];
        if (categoryData.keywords && categoryData.response) {
            for (const keyword of categoryData.keywords) {
                if (lowerInput.includes(keyword)) {
                    return categoryData.response;
                }
            }
        }
    }
    
    // Default response
    return "I'm not sure about that. Try asking about: events, registration, QR codes, food, timing, or venue. Or contact support for help!";
}

function getRandomResponse(responses) {
    return responses[Math.floor(Math.random() * responses.length)];
}

// Handle Enter key
document.addEventListener('DOMContentLoaded', function() {
    const chatInput = document.getElementById('chatInput');
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
});

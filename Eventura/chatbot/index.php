<?php
require_once __DIR__ . '/../config/config.php';

// Optional: Require login for chatbot
// require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventura Assistant - AI Chatbot</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        .chatbot-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--bg-primary);
        }
        
        .chatbot-header {
            background: var(--bg-secondary);
            padding: 20px;
            box-shadow: var(--shadow-light);
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 100;
        }
        
        .bot-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        
        .bot-info h2 {
            color: var(--text-primary);
            margin: 0;
            font-size: 20px;
        }
        
        .bot-info p {
            color: var(--text-secondary);
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            display: flex;
            gap: 12px;
            max-width: 80%;
            animation: messageSlide 0.3s ease;
        }
        
        @keyframes messageSlide {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        
        .message.bot {
            align-self: flex-start;
        }
        
        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .message.user .message-avatar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .message.bot .message-avatar {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
        }
        
        .message-content {
            background: var(--bg-secondary);
            padding: 15px 18px;
            border-radius: 18px;
            box-shadow: var(--shadow-light);
            position: relative;
        }
        
        .message.user .message-content {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .message-text {
            margin: 0;
            line-height: 1.5;
            word-wrap: break-word;
        }
        
        .message-time {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 5px;
            text-align: right;
        }
        
        .message.user .message-time {
            text-align: left;
        }
        
        .typing-indicator {
            display: none;
            align-items: center;
            gap: 12px;
            align-self: flex-start;
            max-width: 80%;
        }
        
        .typing-indicator.show {
            display: flex;
        }
        
        .typing-dots {
            display: flex;
            gap: 4px;
            padding: 15px 18px;
            background: var(--bg-secondary);
            border-radius: 18px;
            box-shadow: var(--shadow-light);
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--text-muted);
            animation: typingDot 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typingDot {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.4;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }
        
        .chat-input-container {
            background: var(--bg-secondary);
            padding: 20px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            border-top: 1px solid var(--border-color);
        }
        
        .chat-input-wrapper {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .chat-input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid var(--border-color);
            border-radius: 25px;
            font-size: 16px;
            background: var(--bg-tertiary);
            color: var(--text-primary);
            resize: none;
            min-height: 50px;
            max-height: 120px;
            font-family: inherit;
            transition: var(--transition);
        }
        
        .chat-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .send-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        
        .send-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }
        
        .send-button:active {
            transform: scale(0.95);
        }
        
        .send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .quick-actions {
            padding: 15px 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .quick-action-btn {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            color: var(--text-secondary);
            transition: var(--transition);
        }
        
        .quick-action-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .welcome-message {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }
        
        .welcome-message h3 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        .welcome-message p {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .chatbot-header {
                padding: 15px 20px;
            }
            
            .bot-info h2 {
                font-size: 18px;
            }
            
            .chat-messages {
                padding: 15px;
            }
            
            .message {
                max-width: 90%;
            }
            
            .chat-input-container {
                padding: 15px;
            }
            
            .chat-input-wrapper {
                gap: 10px;
            }
            
            .send-button {
                width: 45px;
                height: 45px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="chatbot-container">
        <!-- Header -->
        <header class="chatbot-header">
            <div class="bot-avatar">🤖</div>
            <div class="bot-info">
                <h2>Eventura Assistant</h2>
                <p>Your AI helper for event management</p>
            </div>
        </header>

        <!-- Chat Messages -->
        <div class="chat-messages" id="chatMessages">
            <!-- Welcome Message -->
            <div class="welcome-message">
                <h3>👋 Welcome to Eventura Assistant!</h3>
                <p>I'm here to help you with information about events, registrations, QR codes, and more. Feel free to ask me anything!</p>
                <div class="quick-actions">
                    <button class="quick-action-btn" onclick="sendQuickMessage('How do I register for events?')">📝 Register for Events</button>
                    <button class="quick-action-btn" onclick="sendQuickMessage('How do QR codes work?')">📱 QR Code Help</button>
                    <button class="quick-action-btn" onclick="sendQuickMessage('What events are upcoming?')">📅 Upcoming Events</button>
                    <button class="quick-action-btn" onclick="sendQuickMessage('How do I contact support?')">💬 Contact Support</button>
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div class="typing-indicator" id="typingIndicator">
            <div class="message-avatar bot">🤖</div>
            <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        </div>

        <!-- Chat Input -->
        <div class="chat-input-container">
            <div class="chat-input-wrapper">
                <textarea 
                    id="chatInput" 
                    class="chat-input" 
                    placeholder="Type your message here..."
                    rows="1"
                    onkeydown="handleKeyPress(event)"
                    oninput="autoResize(this)"
                ></textarea>
                <button id="sendButton" class="send-button" onclick="sendMessage()" disabled>
                    ➤
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script>
        let isTyping = false;

        // Initialize chat
        document.addEventListener('DOMContentLoaded', function() {
            const chatInput = document.getElementById('chatInput');
            chatInput.focus();
        });

        // Handle key press
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        // Auto resize textarea
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
            
            // Enable/disable send button
            const sendButton = document.getElementById('sendButton');
            sendButton.disabled = !textarea.value.trim();
        }

        // Send message
        function sendMessage() {
            const chatInput = document.getElementById('chatInput');
            const message = chatInput.value.trim();
            
            if (!message || isTyping) return;
            
            // Add user message
            addMessage(message, 'user');
            
            // Clear input
            chatInput.value = '';
            autoResize(chatInput);
            
            // Show typing indicator
            showTypingIndicator();
            
            // Get bot response
            setTimeout(() => {
                const response = getBotResponse(message);
                hideTypingIndicator();
                addMessage(response, 'bot');
            }, 1000 + Math.random() * 1000);
        }

        // Send quick message
        function sendQuickMessage(message) {
            const chatInput = document.getElementById('chatInput');
            chatInput.value = message;
            sendMessage();
        }

        // Add message to chat
        function addMessage(text, sender) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}`;
            
            const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const avatar = sender === 'user' ? '👤' : '🤖';
            
            messageDiv.innerHTML = `
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    <p class="message-text">${text}</p>
                    <div class="message-time">${time}</div>
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Show/hide typing indicator
        function showTypingIndicator() {
            isTyping = true;
            document.getElementById('typingIndicator').classList.add('show');
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function hideTypingIndicator() {
            isTyping = false;
            document.getElementById('typingIndicator').classList.remove('show');
        }

        // Get bot response (rule-based)
        function getBotResponse(message) {
            const lowerMessage = message.toLowerCase();
            
            // Event Registration
            if (lowerMessage.includes('register') || lowerMessage.includes('sign up')) {
                return 'To register for events: 1️⃣ Browse events on the dashboard 2️⃣ Click "Register Now" on any event 3️⃣ Your QR codes will be generated automatically. Make sure your profile is complete first!';
            }
            
            // QR Code Help
            if (lowerMessage.includes('qr') || lowerMessage.includes('code')) {
                return '📱 QR Codes are your digital tickets! Each event gives you Entry QR and Food QR (if available). Show them at the venue for validation. Each QR can only be used once for security.';
            }
            
            // Upcoming Events
            if (lowerMessage.includes('upcoming') || lowerMessage.includes('events')) {
                return '📅 Check the "Browse Events" section to see all upcoming events. You can filter by date, search by title, and register directly from the list. Events with food available will show a 🍕 icon!';
            }
            
            // Profile Issues
            if (lowerMessage.includes('profile') || lowerMessage.includes('complete')) {
                return '👤 Complete your profile by adding your student ID, roll number, course, and year. This is required before you can register for events. Go to Profile > Edit to update your information.';
            }
            
            // Login Issues
            if (lowerMessage.includes('login') || lowerMessage.includes('password')) {
                return '🔐 Login issues? Try: 1️⃣ Use Google OAuth for quick login 2️⃣ Reset password if forgotten 3️⃣ Check email verification. Contact admin if problems persist.';
            }
            
            // Contact Support
            if (lowerMessage.includes('contact') || lowerMessage.includes('support') || lowerMessage.includes('help')) {
                return '💬 Need help? Contact us through: 1️⃣ Admin dashboard (if you\'re admin/teacher) 2️⃣ Email: support@eventura.com 3️⃣ Visit college IT office. We typically respond within 24 hours!';
            }
            
            // Food Coupons
            if (lowerMessage.includes('food') || lowerMessage.includes('meal')) {
                return '🍕 Food QR codes work like digital coupons! When an event offers food, you\'ll get a separate Food QR code. Show it at the food counter to claim your meal. Remember: one-time use only!';
            }
            
            // Event Creation (for teachers/admins)
            if (lowerMessage.includes('create') || lowerMessage.includes('organize')) {
                return '📝 To create events: 1️⃣ Go to Dashboard > Create Event 2️⃣ Fill in event details (title, description, date, time, venue) 3️⃣ Set food availability if applicable 4️⃣ Publish! Students can then register.';
            }
            
            // Technical Issues
            if (lowerMessage.includes('error') || lowerMessage.includes('problem') || lowerMessage.includes('issue')) {
                return '🔧 Technical issues? Try: 1️⃣ Refresh the page 2️⃣ Clear browser cache 3️⃣ Check internet connection 4️⃣ Try a different browser. If issues persist, screenshot the error and contact support.';
            }
            
            // Mobile Access
            if (lowerMessage.includes('mobile') || lowerMessage.includes('phone')) {
                return '📱 Eventura works great on mobile! Access through your phone browser. All features including QR code scanning and event registration are mobile-optimized. Save the bookmark for easy access!';
            }
            
            // Default response
            return `I understand you're asking about "${message}". Here are some things I can help you with:\n\n📝 Event registration\n📱 QR code usage\n📅 Upcoming events\n👤 Profile completion\n🔐 Login issues\n🍕 Food coupons\n💬 Contact support\n\nTry asking about any of these topics, or contact human support for complex issues!`;
        }
    </script>
</body>
</html>

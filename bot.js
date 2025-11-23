const { useState, useEffect, useRef } = React;

function ChatBot() {
    const [open, setOpen] = useState(false);
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [typing, setTyping] = useState(false);
    const [thinkingDots, setThinkingDots] = useState('');
    const messagesEndRef = useRef(null);
    const cache = useRef({});

    // Auto-scroll
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, typing]);

    // Typing dots animation
    useEffect(() => {
        if (!typing) return;
        let count = 0;
        const interval = setInterval(() => {
            setThinkingDots('.'.repeat((count % 3) + 1));
            count++;
        }, 400);
        return () => clearInterval(interval);
    }, [typing]);

    // Typewriter animation
    const typeWriterEffect = (text, callback) => {
        let index = 0;
        const speed = 25;
        const interval = setInterval(() => {
            setMessages(prev =>
                prev.map((m, i, arr) =>
                    i === arr.length - 1 && m.role === 'assistant'
                        ? { ...m, content: text.substring(0, index) }
                        : m
                )
            );
            index++;
            if (index > text.length) {
                clearInterval(interval);
                callback?.();
            }
        }, speed);
    };

    // Send message via AJAX
    const sendMessage = async (question) => {
        if (!question?.trim() || question.length > 500) return;
        const trimmed = question.trim();

        if (cache.current[trimmed]) {
            setMessages(prev => [
                ...prev,
                { role: 'user', content: trimmed },
                { role: 'assistant', content: cache.current[trimmed] }
            ]);
            return;
        }

        setMessages(prev => [...prev, { role: 'user', content: trimmed }]);
        setTyping(true);
        setInput('');

        const MAX_HISTORY = 10;
        const historyToSend = [...messages, { role: 'user', content: trimmed }]
            .filter(m => m?.content)
            .slice(-MAX_HISTORY)
            .map(m => ({ role: m.role === 'bot' ? 'assistant' : m.role, content: m.content }));

        try {
            const response = await fetch(ChatBotAjax.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: new URLSearchParams({
                    action: 'chatbot_request',
                    payload: JSON.stringify({
                        question: trimmed,
                        history: historyToSend
                    })
                })
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();

            const botAnswer =
                data?.choices?.[0]?.message?.content ||
                data?.error?.message ||
                'Ошибка получения ответа от сервера.';

            cache.current[trimmed] = botAnswer;

            setTyping(false);
            setTimeout(() => {
                setMessages(prev => [...prev, { role: 'assistant', content: '' }]);
                typeWriterEffect(botAnswer);
            }, 300);
        } catch (err) {
            console.error('ChatBot AJAX error:', err);
            setTyping(false);
            setMessages(prev => [...prev, { role: 'assistant', content: 'Ошибка сети или сервер недоступен.' }]);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        sendMessage(input);
    };

    const templates = [
        "Что такое PixelTrade?",
        "Как добавить товар в избранное?",
        "Как отредактировать профиль?",
        "Какие преимущества у PixelTrade?"
    ];

    return React.createElement('div', { className: 'chatbot-wrapper' },
        React.createElement('div', { className: 'chatbot-header-btn' },
            React.createElement('button', {
                id: 'chatbot-toggle-btn',
                className: `chatbot-toggle ${open ? 'active' : ''}`,
                onClick: () => setOpen(!open),
                'aria-label': open ? 'Закрыть чат-бот' : 'Открыть чат-бот'
            }, open ? '✖' : '💬')
        ),
        React.createElement('div', { id: 'chatbot-container', className: `chatbot-container ${open ? 'open' : ''}` },
            React.createElement('h1', { className: 'chatbot-title' }, 'Помощник PixelTrade'),
            React.createElement('div', { className: 'chatbot-templates' },
                templates.map((t, i) =>
                    React.createElement('button', {
                        key: i,
                        onClick: () => sendMessage(t),
                        className: 'chatbot-template-btn'
                    }, t)
                )
            ),
            React.createElement('div', { className: 'chatbot-window' },
                messages.map((m, i) =>
                    React.createElement('div', {
                        key: i,
                        className: `chatbot-message ${m.role === 'assistant' ? 'bot' : m.role}`,
                        dangerouslySetInnerHTML: { __html: m.content }
                    })
                ),
                typing && React.createElement('div', { className: 'chatbot-message bot' }, `Бот печатает${thinkingDots}`),
                React.createElement('div', { ref: messagesEndRef })
            ),
            React.createElement('form', { onSubmit: handleSubmit, className: 'chatbot-form' },
                React.createElement('input', {
                    type: 'text',
                    value: input,
                    onChange: e => setInput(e.target.value),
                    placeholder: 'Задайте вопрос о PixelTrade',
                    maxLength: 500,
                    id: 'chatbot-input'
                }),
                React.createElement('button', { type: 'submit', id: 'chatbot-submit-btn' }, 'Отправить')
            )
        )
    );
}

document.addEventListener('DOMContentLoaded', () => {
    const chatbotRoot = document.getElementById('chatbot-root');
    if (chatbotRoot && !chatbotRoot._reactRootContainer) {
        ReactDOM.createRoot(chatbotRoot).render(React.createElement(ChatBot));
    }
});

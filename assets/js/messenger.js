(function(){
  const { useState, useEffect, useRef, useCallback } = React;

  window.dmApi = async function(path, opts={}) {
    try {
      const res = await fetch(SIMPLE_DM.rest + path, {
        headers: { 'X-WP-Nonce': SIMPLE_DM.nonce, 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        ...opts
      });
      if (!res.ok) {
        const errText = await res.text();
        throw new Error(`HTTP ${res.status}: ${errText}`);
      }
      return res.json();
    } catch(err) {
      console.error('dmApi error:', err);
      throw err;
    }
  };

  const translationCache = new Map();
  let globalOpenThread = null;

  function ChatList({ threads, currentId, onSelect }) {
    return React.createElement('div', { className: 'dm-sidebar' }, [
      React.createElement('div', { className: 'dm-sidebar-header title-medium', key: 'header' }, 'Чаты'),
      threads.length === 0
        ? React.createElement('div', { className: 'dm-empty body-medium-regular', key: 'empty' }, 'Чатов пока нет')
        : threads.map(t => React.createElement(ThreadItem, { key: t.id, thread: t, active: t.id === currentId, onSelect }))
    ]);
  }

  function ThreadItem({ thread, active, onSelect }) {
    return React.createElement('button', {
      className: 'dm-thread' + (active ? ' active' : ''),
      onClick: () => onSelect(thread.id)
    }, [
      React.createElement('img', {
        key: 'av',
        src: thread.other_user.avatar || '/wp-content/uploads/default-avatar.png',
        className: 'dm-avatar',
        alt: thread.other_user.name
      }),
      React.createElement('div', { key: 'meta', className: 'dm-thread-meta' }, [
        React.createElement('div', { key: 'name', className: 'dm-name title-small' }, thread.other_user.name),
        React.createElement('div', { key: 'time', className: 'dm-upd body-small-regular' },
          new Intl.DateTimeFormat(navigator.language, { dateStyle: 'short', timeStyle: 'short' }).format(new Date(thread.updated*1000))
        )
      ])
    ]);
  }

  function Message({ m, autoTranslate, onEdit, openMenuId, setOpenMenuId }) {
    const mine = m.author === SIMPLE_DM.currentUser.id;
    const [translated, setTranslated] = useState(null);
    const [loading, setLoading] = useState(false);
    const visible = openMenuId === m.id;
    const msgRef = useRef(null);

    const doTranslate = useCallback(async () => {
      if (translationCache.has(m.id)) {
        setTranslated(translationCache.get(m.id));
        return;
      }
      setLoading(true);
      try {
        const res = await dmApi('translate_message', {
          method: 'POST',
          body: JSON.stringify({
            text: m.content,
            target_lang: SIMPLE_DM.currentUser.language || 'ru',
            source_lang: m.lang || 'auto'
          })
        });
        if (res.translated && res.translated !== '##SKIP##') {
          translationCache.set(m.id, res.translated);
          setTranslated(res.translated);
        }
      } catch (err) {
        console.warn('Ошибка перевода:', err.message);
      } finally {
        setLoading(false);
      }
    }, [m]);

    const handleContextMenu = (e) => {
      if(mine) {
        e.preventDefault();
        setOpenMenuId(m.id);
      }
    };

    useEffect(() => {
      if(!visible) return;
      const closeMenu = () => setOpenMenuId(null);
      document.addEventListener('click', closeMenu);
      return () => document.removeEventListener('click', closeMenu);
    }, [visible, setOpenMenuId]);

    useEffect(() => {
      if(autoTranslate && !mine && !translated) doTranslate();
    }, [autoTranslate, mine, translated, doTranslate]);

    return React.createElement('div', { className: 'dm-msg ' + (mine ? 'mine' : ''), onContextMenu: handleContextMenu, ref: msgRef }, [
      React.createElement('div', { key: 'bubble', className: 'dm-bubble body-medium-regular' }, translated || m.content),
      React.createElement('div', { key: 'meta', className: 'dm-ts body-small-regular' }, [
        new Date(m.created*1000).toLocaleTimeString(),
        m.edited && React.createElement('span', { key:'edited', className:'dm-edited' }, ' (отредактировано)'),
        !mine && !autoTranslate && !translated && React.createElement('button', {
          key: 'btn',
          className: 'dm-translate-btn',
          onClick: doTranslate,
          disabled: loading
        }, loading ? 'Перевожу...' : 'Перевести')
      ]),
      visible && React.createElement('div', { key: 'menu', className: 'dm-context-menu dm-context-below' }, [
        React.createElement('div', { key: 'edit', className: 'dm-context-item', onClick: () => { onEdit(m); setOpenMenuId(null); } }, 'Редактировать'),
        React.createElement('div', { key: 'delete', className: 'dm-context-item', onClick: () => { alert('Удаление'); setOpenMenuId(null); } }, 'Удалить')
      ])
    ]);
  }

  function Composer({ onSend, editingMessage, onCancelEdit }) {
    const [value, setValue] = useState(editingMessage?.content || '');
    const textareaRef = useRef(null);

    useEffect(() => { setValue(editingMessage?.content || ''); textareaRef.current?.focus(); }, [editingMessage]);

    const send = async () => {
      if(!value.trim()) return;
      await onSend(value, editingMessage?.id);
      setValue('');
    };

    return React.createElement('div', { className:'dm-composer' }, [
      React.createElement('textarea', {
        key:'ta',
        ref: textareaRef,
        value,
        onChange: e => setValue(e.target.value),
        placeholder:'Ваше сообщение…',
        className:'body-medium-regular',
        onKeyDown: e => { if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); send(); } }
      }),
      React.createElement('div', { key:'btns', className:'dm-composer-btns' }, [
        React.createElement('button', { key:'send', onClick:send, className:'dm-send button-medium' }, '➤'),
        editingMessage && React.createElement('button', { key:'cancel', onClick:onCancelEdit, className:'dm-cancel button-medium' }, 'x')
      ])
    ]);
  }

  function App() {
    const [threads, setThreads] = useState([]);
    const [current, setCurrent] = useState(null);
    const [messages, setMessages] = useState([]);
    const [since, setSince] = useState(0);
    const [autoTranslate, setAutoTranslate] = useState(false);
    const [editingMessage, setEditingMessage] = useState(null);
    const [initialLoaded, setInitialLoaded] = useState(false);
    const [openMenuId, setOpenMenuId] = useState(null);
    
    const loadThreads = useCallback(async () => {
      const data = await dmApi('threads');
      setThreads(data);
    }, []);

    const loadMessages = useCallback(async (tid, sinceTs = 0) => {
      return dmApi(`threads/${tid}/messages` + (sinceTs ? `?since=${sinceTs}` : ''));
    }, []);

    const scrollToBottom = useCallback(() => {
      const el = document.querySelector('.dm-messages');
      if(el) el.scrollTop = el.scrollHeight;
    }, []);

    const openThread = useCallback(async (tid) => {
      setCurrent(tid);
      setMessages([]);
      setSince(0);
      setEditingMessage(null);
      setInitialLoaded(false);

      const ms = await loadMessages(tid, 0);
      const mapped = ms.map(m => ({ ...m, lang: m.lang || 'auto' }));
      setMessages(mapped);
      if(ms.length) setSince(Math.floor(ms[ms.length-1].created));
      setInitialLoaded(true);
      setTimeout(scrollToBottom, 0);
    }, [loadMessages, scrollToBottom]);

    const sendMessage = useCallback(async (content, editId = null) => {
      if(!current) return;
      if(editId) {
        const res = await dmApi(`messages/${editId}/edit`, { method: 'POST', body: JSON.stringify({ text: content }) });
        if(res.success) {
          setMessages(prev => prev.map(m => m.id === editId ? { ...m, content, edited: true } : m));
          setEditingMessage(null);
          scrollToBottom();
        }
      } else {
        await dmApi(`threads/${current}/messages`, { method: 'POST', body: JSON.stringify({ content }) });
        const ms = await loadMessages(current, since);
        const mapped = ms.map(m => ({ ...m, lang: m.lang || 'auto' }));
        setMessages(prev => [...prev, ...mapped]);
        if(ms.length) setSince(Math.floor(ms[ms.length-1].created));
        scrollToBottom();
      }
    }, [current, loadMessages, since, scrollToBottom]);

    const startEditing = (message) => setEditingMessage(message);
    const cancelEditing = () => setEditingMessage(null);

    useEffect(() => { loadThreads(); }, [loadThreads]);

    useEffect(() => {
      const params = new URLSearchParams(window.location.search);
      const tid = params.get('thread');
      if(tid && threads.length) openThread(parseInt(tid));
    }, [threads, openThread]);

    useEffect(() => {
      if(!current || !initialLoaded) return;
      const interval = setInterval(async () => {
        if(since===0) return;
        const ms = await loadMessages(current, since);
        if(ms.length) {
          setMessages(prev => [...prev, ...ms.map(m => ({ ...m, lang: m.lang || 'auto' }))]);
          setSince(Math.floor(ms[ms.length-1].created));
          scrollToBottom();
        }
      }, 3000);
      return () => clearInterval(interval);
    }, [current, since, loadMessages, scrollToBottom, initialLoaded]);

    useEffect(() => { globalOpenThread = openThread; return () => { globalOpenThread = null; }; }, [openThread]);

    const messagesWithDates = [];
    let lastDate = null;
    messages.forEach(m => {
      const msgDate = new Date(m.created * 1000);
      const dateStr = msgDate.toLocaleDateString();
      if(dateStr !== lastDate) {
        messagesWithDates.push({ type:'date', date: dateStr, id:'date-'+msgDate.getTime() });
        lastDate = dateStr;
      }
      messagesWithDates.push(m);
    });

    return React.createElement('div', { className: 'dm-wrap' }, [
      React.createElement(ChatList, { key: 'list', threads, currentId: current, onSelect: openThread }),
      React.createElement('div', { key: 'chat', className: 'dm-chat' }, [
        !current && React.createElement('div', { className: 'dm-placeholder body-medium-regular', key: 'placeholder' }, 'Выберите чат'),
        current && React.createElement('div', { className: 'dm-messages', key: 'messages' },
          messagesWithDates.map(m => m.type==='date'
            ? React.createElement('div', { key:m.id, className:'dm-date-separator body-small-regular' }, m.date)
            : React.createElement(Message, { key:m.id, m, autoTranslate, onEdit:startEditing, openMenuId, setOpenMenuId })
          )
        ),
        current && React.createElement(Composer, { key: 'composer', onSend: sendMessage, editingMessage, onCancelEdit: cancelEditing }),
        current && React.createElement('div', { key: 'translate-toggle', className: 'dm-translate-toggle' }, [
          React.createElement('button', {
            onClick: () => setAutoTranslate(!autoTranslate),
            className: 'button-medium'
          }, autoTranslate ? 'Убрать перевод' : 'Включить перевод')
        ])
      ])
    ]);
  }

  document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('simple-dm-root');
    const toggleBtn = document.getElementById('dm-toggle-btn');
    if(!root || !toggleBtn) return;
    toggleBtn.addEventListener('click', () => { root.style.display = (root.style.display==='none') ? 'block' : 'none'; });
    ReactDOM.createRoot(root).render(React.createElement(App));
  });

  window.openDmWithUser = async function(userId){
    try {
      const res = await dmApi('threads', { method:'POST', body:JSON.stringify({user_id: userId}) });
      if(globalOpenThread) globalOpenThread(res.thread_id);
      const root = document.getElementById('simple-dm-root');
      if(root) root.style.display = 'block';
    } catch(err){
      alert('Ошибка: ' + err.message);
    }
  };

  document.addEventListener('click', (e) => {
    if(e.target && e.target.classList.contains('dm-write-btn')){
      const uid = e.target.getAttribute('data-user');
      if(uid) window.openDmWithUser(uid);
    }
  });
})();

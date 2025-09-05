(function(){
  const defaultAvatar = SIMPLE_DM.defaultAvatar;
  const { useState, useEffect, useRef, useCallback } = React;

  window.dmApi = async function(path, opts={}){
    try{
      const fetchOpts = {
        headers: { 'X-WP-Nonce': SIMPLE_DM.nonce },
        credentials: 'same-origin',
        ...opts
      };
      if(opts.body) fetchOpts.headers['Content-Type'] = 'application/json';

      const res = await fetch(SIMPLE_DM.rest + path, fetchOpts);
      if(!res.ok){
        const errText = await res.text();
        throw new Error(`HTTP ${res.status}: ${errText}`);
      }
      return res.json();
    } catch(err){
      console.error('dmApi error:', err);
      throw err;
    }
  };

  const translationCache = new Map();
  let globalOpenThread = null;

  function ChatList({ threads, currentId, onSelect }){
    return React.createElement('div', { className: 'dm-sidebar' }, [
      React.createElement('div', { className: 'dm-sidebar-header title-medium', key: 'header' }, 'Чаты'),
      threads.length === 0
        ? React.createElement('div', { className: 'dm-empty body-medium-regular', key: 'empty' }, 'Чатов пока нет')
        : threads.map(t => React.createElement(ThreadItem, { key: t.id, thread: t, active: t.id === currentId, onSelect }))
    ]);
  }

  function ThreadItem({ thread, active, onSelect }) {
    const otherUser = thread.other_user || {};
    return React.createElement('button', {
      className: 'dm-thread button-medium' + (active ? ' active' : ''),
      onClick: () => onSelect(thread.id)
    }, [
      React.createElement('div', { key: 'avatar-wrapper', className: 'dm-avatar-wrapper', style: { position: 'relative' } }, [
        React.createElement('img', { 
          key: 'av', 
          src: otherUser.avatar || defaultAvatar, 
          className: 'dm-avatar', 
          alt: otherUser.name || 'Удалённый пользователь' 
        }),
        thread.unread_count > 0 && React.createElement('span', { key: 'unread', className: 'dm-unread-badge'}, thread.unread_count)
      ]),
      React.createElement('div', { key: 'meta', className: 'dm-thread-meta' }, [
        React.createElement('div', { key: 'name', className: 'dm-name title-medium' }, otherUser.name || 'Удалённый пользователь'),
        React.createElement('div', { key: 'last', className: 'dm-last body-small-regular' }, thread.last_message ? thread.last_message.slice(0,40)+'…' : 'Нет сообщений')
      ]),
      React.createElement('div', { key: 'time', className: 'dm-upd body-small-regular' }, formatDateTime(thread.updated))
    ]);
  }


  function formatDateTime(ts){
    const d = new Date(ts*1000);
    const day = String(d.getDate()).padStart(2,'0');
    const month = String(d.getMonth()+1).padStart(2,'0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
  }

  function Message({ m, autoTranslate, onEdit, openMenuId, setOpenMenuId }) {
    const mine = m.author === SIMPLE_DM.currentUser.id;
    const [translated, setTranslated] = React.useState(null);
    const [loading, setLoading] = React.useState(false);
    const visible = openMenuId === m.id;
    const msgRef = React.useRef(null);

    const doTranslate = React.useCallback(async () => {
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
      if (mine) {
        e.preventDefault();
        setOpenMenuId(m.id);
      }
    };

    React.useEffect(() => {
      if (!visible) return;
      const closeMenu = () => setOpenMenuId(null);
      document.addEventListener('click', closeMenu);
      return () => document.removeEventListener('click', closeMenu);
    }, [visible, setOpenMenuId]);

    React.useEffect(() => {
      if (autoTranslate && !mine && !translated && !m.system) doTranslate();
    }, [autoTranslate, mine, translated, doTranslate, m.system]);

    const bubbleContent = translated || m.content;
    const timestamp = new Date(m.created * 1000).toLocaleTimeString();

    return React.createElement('div', {
      className: 'dm-msg ' + (mine ? 'mine' : ''),
      onContextMenu: handleContextMenu,
      ref: msgRef
    }, [
      React.createElement('div', {
        key: 'bubble',
        className: 'dm-bubble body-medium-regular'
      }, bubbleContent),

      React.createElement('div', {
        key: 'meta',
        className: 'dm-ts body-small-regular'
      }, [timestamp,
         m.edited && React.createElement('span', { key:'edited', className:'dm-edited' }, ' (отредактировано)'),
         !mine && !autoTranslate && !translated && React.createElement('button', {
           key: 'btn',
           className: 'dm-translate-btn button-small',
           onClick: doTranslate,
           disabled: loading
         }, loading ? 'Перевожу...' : 'Перевести')]
      ),

      visible && React.createElement('div', {
        key: 'menu',
        className: 'dm-context-menu dm-context-below'
      }, [
        React.createElement('div', {
          key: 'edit',
          className: 'dm-context-item button-small link-button',
          onClick: () => { onEdit(m); setOpenMenuId(null); }
        }, 'Редактировать'),

        React.createElement('div', {
          key: 'delete',
          className: 'dm-context-item button-small link-button',
          onClick: () => { alert('Удаление'); setOpenMenuId(null); }
        }, 'Удалить')
      ])
    ]);
  }

  function Composer({ onSend, editingMessage, onCancelEdit, blocked, blockedByMe }){
    const [value, setValue] = useState(editingMessage?.content || '');
    const textareaRef = useRef(null);

    useEffect(() => { 
      setValue(editingMessage?.content || ''); 
      textareaRef.current?.focus(); 
    }, [editingMessage]);

    const send = async () => {
      if(!value.trim()) return;
      if(blocked && !blockedByMe){
        alert('Вы не можете отправлять сообщения — чат заблокирован.');
        return;
      }
      await onSend(value, editingMessage?.id);
      setValue('');
    };

    return React.createElement('div', { className:'dm-composer' }, [
      React.createElement('textarea', {
        key:'ta',
        ref: textareaRef,
        value,
        onChange: e => setValue(e.target.value),
        placeholder: blocked && !blockedByMe ? 'Вы заблокированы — отправка сообщений недоступна.' : 'Ваше сообщение…',
        className:'body-medium-regular input--primary',
        onKeyDown: e => { if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); send(); } },
        disabled: blocked && !blockedByMe
      }),
      React.createElement('div', { key:'btns', className:'dm-composer-btns' }, [
        React.createElement('button', { key:'send', onClick:send, className:'dm-send button-medium primary-button-larger', disabled: blocked && !blockedByMe }, '➤'),
        editingMessage && React.createElement('button', { key:'cancel', onClick:onCancelEdit, className:'dm-cancel button-medium secondary-button-small' }, 'x')
      ])
    ]);
  }

  function App(){
    const [threads, setThreads] = useState([]);
    const [current, setCurrent] = useState(null);
    const [messages, setMessages] = useState([]);
    const [since, setSince] = useState(0);
    const [autoTranslate, setAutoTranslate] = useState(false);
    const [editingMessage, setEditingMessage] = useState(null);
    const [initialLoaded, setInitialLoaded] = useState(false);
    const [openMenuId, setOpenMenuId] = useState(null);
    const [moreMenuOpen, setMoreMenuOpen] = useState(false);

    // expose setters globally to stop updates when chat closed
    window.appSetCurrent = setCurrent;
    window.appSetMessages = setMessages;
    window.appSetSince = setSince;
    window.appSetEditingMessage = setEditingMessage;

    const loadThreads = useCallback(async () => {
      const data = await dmApi('threads');
      setThreads(data);
    }, []);

    const loadMessages = useCallback(async (tid, sinceTs = 0) => {
      const ms = await dmApi(`threads/${tid}/messages${sinceTs ? `?since=${sinceTs}` : ''}`);

      const mapped = ms.map(m => {
        const sys = m.system || false;
        const evt = m.event_type || null;
        const created = m.created || Math.floor(new Date().getTime()/1000);
      
        return { ...m, system: sys, event: evt, created };
      });
    
      return mapped;
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

      await dmApi(`threads/${tid}/read`, { method: 'POST' });
      await loadThreads();

      setThreads(prev => prev.map(t => t.id === tid ? {...t, unread_count:0} : t));

      const ms = await loadMessages(tid, 0);
      const mapped = ms.map(m => ({ ...m, lang: m.lang || 'auto' }));
      setMessages(mapped);
      if(ms.length) setSince(Math.floor(ms[ms.length-1].created));
      setInitialLoaded(true);
      setTimeout(scrollToBottom, 0);
    }, [loadMessages, scrollToBottom, loadThreads]);

    const sendMessage = useCallback(async (content, editId=null) => {
      if (!current) return;

      const currentThread = threads.find(t => t.id === current);
      if (!currentThread) return;

      if (editId) {
        const res = await dmApi(`messages/${editId}/edit`, {
          method:'POST',
          body: JSON.stringify({ text: content })
        });
        if (res.success) {
          setMessages(prev => prev.map(m => 
            m.id === editId ? { ...m, content, edited:true } : m
          ));
          setEditingMessage(null);
          scrollToBottom();
        }
      } else {
        if (currentThread.blocked && currentThread.blocked_by !== SIMPLE_DM.currentUser.id) {
          alert('Вы заблокированы — вы не можете отправлять сообщения в этом чате.');
          return;
        }
      
        await dmApi(`threads/${current}/messages`, { 
          method:'POST', 
          body: JSON.stringify({ content }) 
        });

        const ms = await loadMessages(current, since);
        const mapped = ms.map(m => ({
          ...m,
          lang: m.lang || 'auto',
          system: m.meta?._system === 1 || m.meta?._system === "1",
          event: m.meta?._event || null
        }));
      
        setMessages(prev => [...prev, ...mapped]);
        if (ms.length) setSince(Math.floor(ms[ms.length-1].created));
        scrollToBottom();
        await loadThreads();
      }
    }, [current, threads, loadMessages, since, scrollToBottom, loadThreads]);

    const startEditing = (message) => setEditingMessage(message);
    const cancelEditing = () => setEditingMessage(null);

    useEffect(()=>{ loadThreads(); }, [loadThreads]);

    useEffect(()=>{
      const params = new URLSearchParams(window.location.search);
      const tid = params.get('thread');
      if(tid && threads.length) openThread(parseInt(tid));
    }, [threads, openThread]);

    useEffect(() => {
      const interval = setInterval(async () => {
        try { await loadThreads(); }
        catch(err){ console.warn(err); }
      }, 10000);
      return () => clearInterval(interval);
    }, [loadThreads]);

    useEffect(() => {
      if (!current || !initialLoaded) return;
    
      const interval = setInterval(async () => {
        try {
          const ms = await loadMessages(current, since || 0);
          if (ms.length) {
            setMessages(prev => [...prev, ...ms.map(m => ({ ...m, lang: m.lang || 'auto' }))]);
            setSince(Math.floor(ms[ms.length - 1].created));
          
            const updatedThreads = await dmApi('threads');
            setThreads(updatedThreads);
          
            scrollToBottom();
          }
        } catch (err) {
          console.warn('Ошибка при обновлении сообщений:', err);
        }
      }, 3000);
    
      return () => clearInterval(interval);
    }, [current, since, loadMessages, scrollToBottom, initialLoaded]);

    useEffect(()=>{ globalOpenThread = openThread; return ()=>{ globalOpenThread=null; } }, [openThread]);

    const blockUser = useCallback(async () => {
      if (!current) return;
      const currentThread = threads?.find(t => t.id === current);
      if (!currentThread) return;

      const isBlocked = !!currentThread.blocked;
      const iAmBlocker = Number(currentThread.blocked_by) === Number(SIMPLE_DM.currentUser.id);

      if (isBlocked) {
        if (!iAmBlocker) {
          alert('Вы не можете разблокировать — чат заблокирован другим пользователем.');
          return;
        }
        if (!confirm('Разблокировать чат?')) return;
      
        try {
          await dmApi(`threads/${current}/block`, { method: 'DELETE' });
          await loadThreads(); 
        } catch (err) {
          console.error('Ошибка при разблокировке:', err);
          alert('Ошибка при разблокировке: ' + err.message);
        }
      } else {
        if (!confirm('Вы уверены, что хотите заблокировать пользователя?')) return;
      
        try {
          await dmApi(`threads/${current}/block`, { method: 'POST' });
          await loadThreads();
        } catch (err) {
          console.error('Ошибка при блокировке:', err);
          alert('Ошибка при блокировке: ' + err.message);
        }
      }
    }, [current, threads, loadThreads]);

    const messagesWithDates = [];
    let lastDate = null;
    messages.forEach(m=>{
      const msgDate = new Date(m.created*1000);
      const dateStr = msgDate.toLocaleDateString();
      if(dateStr!==lastDate){
        messagesWithDates.push({ type:'date', date:dateStr, id:'date-'+msgDate.getTime() });
        lastDate = dateStr;
      }
      messagesWithDates.push(m);
    });

    const currentThread = threads.find(t=>t.id===current);
    const otherUser = currentThread?.other_user || {};

    const deleteCurrentThread = async ()=>{
      if(!current) return;
      if(!confirm('Вы уверены, что хотите удалить чат и все его сообщения?')) return;
      try{
        const res = await dmApi(`threads/${current}`, { method:'DELETE' });
        setThreads(prev => prev.filter(t=>t.id!==current));
        setCurrent(null);
        setMessages([]);
        setSince(0);
        setEditingMessage(null);
        setMoreMenuOpen(false);
        alert('Чат успешно удалён');
      } catch(err){
        console.error('Ошибка при удалении чата:', err);
        alert('Ошибка при удалении чата: ' + err.message);
      }
    };

    const isBlocked = !!currentThread?.blocked;
    const iAmBlocker = Number(currentThread?.blocked_by) === Number(SIMPLE_DM.currentUser.id);

    return React.createElement('div', { className:'dm-wrap' }, [
      React.createElement(ChatList, { key:'list', threads, currentId:current, onSelect: openThread }),
      React.createElement('div', { key:'chat', className:'dm-chat' }, [
        !current && React.createElement('div', { className:'dm-placeholder body-medium-regular', key:'placeholder' }, 'Выберите чат'),
        current && React.createElement('div', { key:'chat-header-wrapper' }, [
          React.createElement('div', { key:'chat-header', className:'dm-chat-header' }, [
            React.createElement('img', { key:'av', src: otherUser.avatar || SIMPLE_DM.defaultAvatar, className:'dm-avatar-large', alt: otherUser.name || 'Удалённый пользователь' }),
            React.createElement('div', { key:'info', className:'dm-chat-info' }, [
              React.createElement('div', { className:'dm-name title-medium', key:'name' }, otherUser.name||'Удалённый пользователь'),
              React.createElement('div', { className:'dm-start body-small-regular', key:'start' }, 'Начало переписки: '+(messages[0]?new Date(messages[0].created*1000).toLocaleDateString():'-'))
            ]),
            React.createElement('div', { className:'dm-chat-actions', key:'actions' }, [
              React.createElement('button', { className:'dm-more-btn', onClick:()=>setMoreMenuOpen(!moreMenuOpen), key:'btn' }, '⋮'),
              React.createElement('div', { className:'dm-more-menu' + (moreMenuOpen?' active':''), key:'menu' }, [
                React.createElement('div', { className:'dm-context-item', key:'delete-chat', onClick:deleteCurrentThread }, 'Удалить чат'),
                React.createElement('div', { className:'dm-context-item', key:'block-chat', onClick:async()=>await blockUser() }, iAmBlocker ? 'Разблокировать' : isBlocked ? 'Заблокирован' : 'Заблокировать'),
                React.createElement('div', { className:'dm-context-item', key:'profile', onClick:()=>openProfile(otherUser.id) }, 'Профиль')
              ]),
              React.createElement('button', { className:'dm-close-btn', onClick:()=>setCurrent(null), key:'close' }, '✕')
            ])
          ]),
          React.createElement('div', { className:'dm-translate-toggle', key:'translate' }, [
            React.createElement('button', { onClick:()=>setAutoTranslate(!autoTranslate), className:'button-medium', key:'toggle' }, autoTranslate?'Убрать авто перевод':'Включить авто перевод')
          ])
        ]),
        current && React.createElement('div', { className:'dm-messages', key:'messages' },
          messagesWithDates.map(m => {
            if (m.type === 'date') 
              return React.createElement('div', { key: m.id, className: 'dm-date-separator body-small-regular' }, m.date);
          
            if (m.system) {
              return React.createElement('div', {
                key: m.id,
                className: 'dm-message dm-system-message',
                'data-system': true
              }, [
                React.createElement('div', { key: 'txt', className: 'dm-message-content' }, m.content),
                React.createElement('div', { key: 'sig', className: 'dm-system-signature' }, 
                  `${new Date(m.created*1000).toLocaleTimeString()} - ${m.event === 'blocked' ? 'системное сообщение: заблокирован' : 'системное сообщение: разблокирован'}`)
              ]);
            }
          
            return React.createElement(Message, { key: m.id, m, autoTranslate, onEdit:startEditing, openMenuId, setOpenMenuId });
          })
        ),
        current && React.createElement(Composer, { key:'composer', onSend:sendMessage, editingMessage, onCancelEdit:cancelEditing, blocked:isBlocked, blockedByMe:iAmBlocker }),

        React.createElement('button', {
          className: 'dm-close-b',
          onClick: () => {
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            const root = document.getElementById('simple-dm-root');
            if (root) root.classList.remove('active');
            const overlay = document.querySelector('.dm-overlay');
            if (overlay) overlay.classList.remove('active');
          
            appSetCurrent(null);
            appSetMessages([]);
            appSetSince(0);
            appSetEditingMessage(null);
          },
          key: 'close-b'
        }, '✕')
      ])
    ]);
  }

  document.addEventListener('DOMContentLoaded', () => {
      const root = document.getElementById('simple-dm-root');
      const toggleBtn = document.getElementById('dm-toggle-btn');

      if (!root || !toggleBtn) return;

      // Создаём overlay один раз
      const overlay = document.createElement('div');
      overlay.className = 'dm-overlay';
      document.body.appendChild(overlay);

      const openDm = () => {
          const scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
          document.body.style.overflow = 'hidden';
          document.body.style.paddingRight = scrollBarWidth + 'px';

          root.classList.add('active');
          overlay.classList.add('active');
      };
    
      const closeDm = () => {
          document.body.style.overflow = '';
          document.body.style.paddingRight = '';

          root.classList.remove('active');
          overlay.classList.remove('active');

          appSetCurrent(null);
          appSetMessages([]);
          appSetSince(0);
          appSetEditingMessage(null);
      };
    
      toggleBtn.addEventListener('click', () => {
          const isOpening = !root.classList.contains('active');
          isOpening ? openDm() : closeDm();
      });
    
      overlay.addEventListener('click', closeDm);

      // Крестик будет внутри React App
      ReactDOM.createRoot(root).render(React.createElement(App));
  });


  window.openDmWithUser = async function(userId){
    try{
      const res = await dmApi('threads', { method:'POST', body:JSON.stringify({ user_id: userId }) });
      if(globalOpenThread) globalOpenThread(res.thread_id);
      const root = document.getElementById('simple-dm-root');
      if(root) root.style.display='block';
    } catch(err){
      alert('Ошибка: '+err.message);
    }
  };

  document.addEventListener('click', (e)=>{
    if(e.target && e.target.classList.contains('dm-write-btn')){
      const uid = e.target.getAttribute('data-user');
      if(uid) window.openDmWithUser(uid);
    }
  });
})();
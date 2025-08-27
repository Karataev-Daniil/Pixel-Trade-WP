(function(){
  const { useState, useEffect, useRef } = React;

  window.dmApi = function(path, opts={}) {
    return fetch(SIMPLE_DM.rest + path, Object.assign({
      headers: { 'X-WP-Nonce': SIMPLE_DM.nonce, 'Content-Type': 'application/json' },
      credentials: 'same-origin'
    }, opts)).then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); });
  };

  let globalOpenThread = null;
  const translationCache = {};

  function ChatList({threads, currentId, onSelect}){
    return React.createElement('div',{className:'dm-sidebar'},
      React.createElement('div',{className:'dm-sidebar-header title-medium'},'Чаты'),
      threads.length===0
        ? React.createElement('div',{className:'dm-empty body-medium-regular'},'Чатов пока нет')
        : threads.map(t=>React.createElement('button',{
            key:t.id,
            className:'dm-thread'+(t.id===currentId?' active':''), 
            onClick:()=>onSelect(t.id)
          },[
            React.createElement('img',{
              key:'av',
              src: t.other_user.avatar || '/wp-content/uploads/default-avatar.png',
              className:'dm-avatar',
              alt: t.other_user.name
            }),
            React.createElement('div',{key:'meta',className:'dm-thread-meta'},[
              React.createElement('div',{key:'name',className:'dm-name title-small'},t.other_user.name),
              React.createElement('div',{key:'time',className:'dm-upd body-small-regular'},new Date(t.updated*1000).toLocaleString())
            ])
          ])
        )
    );
  }

  function Message({m, autoTranslate, onEdit}) {
    const mine = m.author === SIMPLE_DM.currentUser.id;
    const [translated, setTranslated] = useState(null);
    const [loading, setLoading] = useState(false);

    const doTranslate = () => {
      if (translationCache[m.id]) {
        setTranslated(translationCache[m.id]);
        return;
      }
      setLoading(true);
      dmApi('translate_message', {
        method: 'POST',
        body: JSON.stringify({
          text: m.content,
          target_lang: SIMPLE_DM.currentUser.language || 'ru',
          source_lang: m.lang || 'auto'
        })
      })
      .then(res => {
        if (res.translated && res.translated !== '##SKIP##') {
          translationCache[m.id] = res.translated;
          setTranslated(res.translated);
        }
      })
      .catch(err => console.warn('Ошибка перевода:', err.message))
      .finally(() => setLoading(false));
    };

    return React.createElement('div', {className: 'dm-msg ' + (mine ? 'mine' : '')}, [
      React.createElement('div', {key:'bubble', className:'dm-bubble body-medium-regular'}, translated || m.content),
      React.createElement('div', {key:'meta', className:'dm-ts body-small-regular'}, [
        new Date(m.created*1000).toLocaleTimeString(),
        mine
          ? React.createElement('button',{
              key:'edit',
              onClick:()=>onEdit(m),
              className:'dm-edit-btn'
            },'Редактировать')
          : null,
        !mine && !autoTranslate && !translated 
          ? React.createElement('button',{key:'btn', className:'dm-translate-btn', onClick:doTranslate, disabled:loading}, loading ? 'Перевожу...' : 'Перевести')
          : null
      ])
    ]);
  }

  function Composer({onSend, editingMessage, onCancelEdit}) {
    const [value,setValue] = useState(editingMessage?.content || '');

    useEffect(() => { setValue(editingMessage?.content || ''); }, [editingMessage]);

    const send = () => {
      if(!value.trim()) return;
      onSend(value, editingMessage?.id).then(() => setValue(''));
    };

    return React.createElement('div',{className:'dm-composer'},[
      React.createElement('textarea',{
        key:'ta', 
        value, 
        onChange:e=>setValue(e.target.value),
        placeholder:'Ваше сообщение…',
        className:'body-medium-regular',
        onKeyDown:e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault(); send();}}
      }),
      React.createElement('div',{key:'btns', className:'dm-composer-btns'},[
        React.createElement('button',{key:'send', onClick:send, className:'dm-send button-medium'}, 
          editingMessage ? '➤' : '➤'
        ),
        editingMessage 
          ? React.createElement('button',{key:'cancel', onClick:onCancelEdit, className:'dm-cancel button-medium'},'x')
          : null
      ])
    ]);
  }

  function App(){
    const [threads,setThreads] = useState([]);
    const [current,setCurrent] = useState(null);
    const [messages,setMessages] = useState([]);
    const [since,setSince] = useState(0);
    const [autoTranslate, setAutoTranslate] = useState(false);
    const [editingMessage, setEditingMessage] = useState(null);
    const pollRef = useRef(null);

    const loadThreads = ()=>dmApi('threads').then(setThreads);
    const loadMessages = (tid,sinceTs=0)=>dmApi(`threads/${tid}/messages`+(sinceTs?`?since=${sinceTs}`:''));

    const openThread = tid=>{
      setCurrent(tid); setMessages([]); setSince(0); setEditingMessage(null);
      loadMessages(tid).then(ms=>{
        const mapped = ms.map(m=>({...m, lang:m.lang || 'auto'}));
        setMessages(mapped);
        if(ms.length) setSince(Math.floor(ms[ms.length-1].created));
        setTimeout(scrollToBottom,0);
      });
    };

    const scrollToBottom = ()=>{ const el = document.querySelector('.dm-messages'); if(el) el.scrollTop = el.scrollHeight; }

    const sendMessage = (content, editId=null) => {
      if(editId){
        return dmApi(`messages/${editId}/edit`, {method:'POST', body: JSON.stringify({text: content})})
          .then(res => {
            if(res.success){
              setMessages(prev => prev.map(m=>m.id===editId?{...m, content}:m));
              setEditingMessage(null);
              scrollToBottom();
            }
          });
      } else {
        return dmApi(`threads/${current}/messages`, {method:'POST', body:JSON.stringify({content})})
          .then(()=>loadMessages(current).then(ms=>{
            const mapped = ms.map(m=>({...m, lang:m.lang || 'auto'}));
            setMessages(mapped);
            if(ms.length) setSince(Math.floor(ms[ms.length-1].created));
            scrollToBottom();
          }));
      }
    };

    const startEditing = (message) => setEditingMessage(message);
    const cancelEditing = () => setEditingMessage(null);

    useEffect(()=>{ loadThreads(); },[]);

    useEffect(()=>{
      const params = new URLSearchParams(window.location.search);
      const tid = params.get('thread');
      if(tid && threads.length) openThread(parseInt(tid));
    },[threads]);

    useEffect(()=>{
      if(!current) return;
      if(pollRef.current) clearInterval(pollRef.current);
      pollRef.current = setInterval(()=>{
        loadMessages(current,since).then(ms=>{
          if(ms.length){
            const mapped = ms.map(m=>({...m, lang:m.lang || 'auto'}));
            setMessages(prev=>[...prev,...mapped]); 
            setSince(Math.floor(ms[ms.length-1].created)); 
            scrollToBottom();
          }
        });
      },3000);
      return ()=>clearInterval(pollRef.current);
    },[current,since]);

    useEffect(()=>{ globalOpenThread = openThread; return ()=>{ globalOpenThread = null; } }, [threads, current]);

    return React.createElement('div',{className:'dm-wrap'},[
      React.createElement(ChatList,{key:'list',threads,currentId:current,onSelect:openThread}),
      React.createElement('div',{key:'chat',className:'dm-chat'},[
        !current?React.createElement('div',{className:'dm-placeholder body-medium-regular'},'Выберите чат'):null,
        current?React.createElement('div',{className:'dm-messages'},messages.map(m=>React.createElement(Message,{key:m.id,m,autoTranslate,onEdit:startEditing}))):null,
        current?React.createElement(Composer,{onSend:sendMessage, editingMessage, onCancelEdit:cancelEditing}):null,
        current?React.createElement('div',{className:'dm-translate-toggle'},[
          React.createElement('button',{
            onClick:()=>setAutoTranslate(!autoTranslate),
            className:'button-medium'
          }, autoTranslate ? 'Убрать перевод' : 'Включить перевод')
        ]):null
      ])
    ]);
  }

  document.addEventListener('DOMContentLoaded',function(){
    const root = document.getElementById('simple-dm-root');
    const toggleBtn = document.getElementById('dm-toggle-btn');
    if(!root || !toggleBtn) return;
    toggleBtn.addEventListener('click', ()=>{ root.style.display = (root.style.display==='none') ? 'block' : 'none'; });
    ReactDOM.createRoot(root).render(React.createElement(App));
  });

  window.openDmWithUser = function(userId){
    dmApi('threads', {method:'POST', body:JSON.stringify({user_id: userId})})
      .then(res=>{
        if(globalOpenThread) globalOpenThread(res.thread_id);
        const root = document.getElementById('simple-dm-root');
        if(root) root.style.display = 'block';
      })
      .catch(err=>alert('Ошибка: ' + err.message));
  };

  document.addEventListener('click',function(e){
    if(e.target && e.target.classList.contains('dm-write-btn')){
      const uid = e.target.getAttribute('data-user');
      if(uid) window.openDmWithUser(uid);
    }
  });
})();

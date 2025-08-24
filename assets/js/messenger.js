(function(){
  const { useState, useEffect, useRef } = React;

  window.dmApi = function(path, opts={}) {
    return fetch(SIMPLE_DM.rest + path, Object.assign({
      headers: { 'X-WP-Nonce': SIMPLE_DM.nonce, 'Content-Type': 'application/json' },
      credentials: 'same-origin'
    }, opts)).then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); });
  };

  function ChatList({threads, currentId, onSelect}){
    return React.createElement('div',{className:'dm-sidebar'},
      React.createElement('div',{className:'dm-sidebar-header'},'Чаты'),
      threads.length===0
        ? React.createElement('div',{className:'dm-empty'},'Чатов пока нет')
        : threads.map(t=>React.createElement('button',{
            key:t.id,
            className:'dm-thread'+(t.id===currentId?' active':''),
            onClick:()=>onSelect(t.id)
          },[
            React.createElement('img',{key:'av',src:t.other_user.avatar,className:'dm-avatar',alt:''}),
            React.createElement('div',{key:'meta',className:'dm-thread-meta'},[
              React.createElement('div',{key:'name',className:'dm-name'},t.other_user.name),
              React.createElement('div',{key:'time',className:'dm-upd'},new Date(t.updated*1000).toLocaleString())
            ])
          ])
        )
    );
  }

  function Message({m}){
    const mine = m.author === SIMPLE_DM.currentUser.id;
    return React.createElement('div',{className:'dm-msg '+(mine?'mine':'')},
      React.createElement('div',{className:'dm-bubble'}, m.content),
      React.createElement('div',{className:'dm-ts'}, new Date(m.created*1000).toLocaleTimeString())
    );
  }

  function Composer({onSend}){
    const [value,setValue] = useState('');
    const send = ()=>{ if(!value.trim()) return; onSend(value).then(()=>setValue('')); };
    return React.createElement('div',{className:'dm-composer'},[
      React.createElement('textarea',{key:'ta',value,onChange:e=>setValue(e.target.value),placeholder:'Ваше сообщение…',
        onKeyDown:e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();}}}),
      React.createElement('button',{key:'btn',onClick:send,className:'dm-send'},'➤')
    ]);
  }

  function App(){
    const [threads,setThreads]=useState([]);
    const [current,setCurrent]=useState(null);
    const [messages,setMessages]=useState([]);
    const [since,setSince]=useState(0);
    const pollRef=useRef(null);

    const loadThreads = ()=>dmApi('threads').then(setThreads);
    const loadMessages=(tid,sinceTs=0)=>dmApi(`threads/${tid}/messages`+(sinceTs?`?since=${sinceTs}`:''),{method:'GET'});
    const openThread = tid=>{
      setCurrent(tid); setMessages([]); setSince(0);
      loadMessages(tid).then(ms=>{setMessages(ms); if(ms.length)setSince(Math.floor(ms[ms.length-1].created)); setTimeout(scrollToBottom,0);});
    };
    const scrollToBottom=()=>{const el=document.querySelector('.dm-messages');if(el) el.scrollTop=el.scrollHeight;}
    const sendMessage=html=>dmApi(`threads/${current}/messages`,{method:'POST',body:JSON.stringify({content:html})})
      .then(()=>loadMessages(current).then(ms=>{setMessages(prev=>[...prev,...ms]);scrollToBottom();}));

    useEffect(()=>{loadThreads();},[]);
    useEffect(()=>{
      const params = new URLSearchParams(window.location.search);
      const tid = params.get('thread');
      if(tid && threads.length) openThread(parseInt(tid));
    },[threads]);
    useEffect(()=>{
      if(!current) return;
      if(pollRef.current) clearInterval(pollRef.current);
      pollRef.current = setInterval(()=>{
        loadMessages(current,since).then(ms=>{if(ms.length){setMessages(prev=>[...prev,...ms]); setSince(Math.floor(ms[ms.length-1].created)); scrollToBottom();}});
      },3000);
      return ()=>clearInterval(pollRef.current);
    },[current,since]);

    return React.createElement('div',{className:'dm-wrap'},[
      React.createElement(ChatList,{key:'list',threads,currentId:current,onSelect:openThread}),
      React.createElement('div',{key:'chat',className:'dm-chat'},[
        !current?React.createElement('div',{className:'dm-placeholder'},'Выберите чат'):null,
        current?React.createElement('div',{className:'dm-messages'},messages.map(m=>React.createElement(Message,{key:m.id,m}))):null,
        current?React.createElement(Composer,{onSend:sendMessage}):null
      ])
    ]);
  }

  document.addEventListener('DOMContentLoaded',function(){
    const root = document.getElementById('simple-dm-root');
    const toggleBtn = document.getElementById('dm-toggle-btn');

    if(!root || !toggleBtn) return;
    toggleBtn.addEventListener('click', ()=>{root.style.display = (root.style.display==='none') ? 'block' : 'none';});
    ReactDOM.createRoot(root).render(React.createElement(App));
  });

  window.openDmWithUser=function(userId){
    dmApi('threads',{method:'POST',body:JSON.stringify({user_id:userId})})
      .then(res=>window.location.href = '?thread='+res.thread_id)
      .catch(err=>alert('Ошибка: '+err.message));
  };

  document.addEventListener('click',function(e){
    if(e.target && e.target.classList.contains('dm-write-btn')){
      const uid=e.target.getAttribute('data-user');
      if(uid) window.openDmWithUser(uid);
    }
  });
})();

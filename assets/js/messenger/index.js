import React from 'react';
import ReactDOM from 'react-dom/client';
import { App } from './App.js';
import { dmApi } from './api.js';

let globalOpenThread = null;

document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('simple-dm-root');
  const toggleBtn = document.getElementById('dm-toggle-btn');
  if(!root || !toggleBtn) return;

  toggleBtn.addEventListener('click', () => {
    root.style.display = (root.style.display==='none') ? 'block' : 'none';
  });

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

document.addEventListener('click', e => {
  if(e.target && e.target.classList.contains('dm-write-btn')){
    const uid = e.target.getAttribute('data-user');
    if(uid) window.openDmWithUser(uid);
  }
});

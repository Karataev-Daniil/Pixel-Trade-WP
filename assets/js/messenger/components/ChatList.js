import React from 'react';

export function ChatList({ threads, currentId, onSelect }) {
  return React.createElement('div', { className: 'dm-sidebar' }, [
    React.createElement('div', { className: 'dm-sidebar-header title-medium', key: 'header' }, 'Чаты'),
    threads.length === 0
      ? React.createElement('div', { className: 'dm-empty body-medium-regular', key: 'empty' }, 'Чатов пока нет')
      : threads.map(t => React.createElement(ThreadItem, { key: t.id, thread: t, active: t.id === currentId, onSelect }))
  ]);
}

function ThreadItem({ thread, active, onSelect }) {
  const formatDateTime = ts => {
    const d = new Date(ts*1000);
    return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
  };

  return React.createElement('button', {
    className: 'dm-thread button-medium' + (active ? ' active' : ''),
    onClick: () => onSelect(thread.id)
  }, [
    React.createElement('img', { key: 'av', src: thread.other_user.avatar, className: 'dm-avatar', alt: thread.other_user.name }),
    React.createElement('div', { key: 'meta', className: 'dm-thread-meta' }, [
      React.createElement('div', { key: 'name', className: 'dm-name title-medium' }, thread.other_user.name),
      React.createElement('div', { key: 'last', className: 'dm-last body-small-regular' }, thread.last_message ? thread.last_message.slice(0,40)+'…' : 'Нет сообщений')
    ]),
    React.createElement('div', { key: 'time', className: 'dm-upd body-small-regular' }, formatDateTime(thread.updated))
  ]);
}

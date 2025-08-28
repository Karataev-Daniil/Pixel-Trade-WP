import React, { useState, useEffect, useRef } from 'react';

export function Composer({ onSend, editingMessage, onCancelEdit }) {
  const [value, setValue] = useState(editingMessage?.content || '');
  const textareaRef = useRef(null);

  useEffect(() => { setValue(editingMessage?.content || ''); textareaRef.current?.focus(); }, [editingMessage]);

  const send = async () => {
    if(!value.trim()) return;
    await onSend(value, editingMessage?.id);
    setValue('');
  };

  return React.createElement('div', { className:'dm-composer' }, [
    React.createElement('textarea', { key:'ta', ref:textareaRef, value, onChange:e=>setValue(e.target.value), placeholder:'Ваше сообщение…', className:'body-medium-regular input--primary', onKeyDown:e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();}}}),
    React.createElement('div', { key:'btns', className:'dm-composer-btns' }, [
      React.createElement('button', { key:'send', onClick:send, className:'dm-send button-medium primary-button-larger' }, '➤'),
      editingMessage && React.createElement('button', { key:'cancel', onClick:onCancelEdit, className:'dm-cancel button-medium secondary-button-small' }, 'x')
    ])
  ]);
}

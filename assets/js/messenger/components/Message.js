import React, { useState, useEffect, useRef, useCallback } from 'react';
import { dmApi, getTranslationFromCache, setTranslationInCache } from '../api.js';

export function Message({ m, autoTranslate, onEdit, openMenuId, setOpenMenuId }) {
  const mine = m.author === SIMPLE_DM.currentUser.id;
  const [translated, setTranslated] = useState(null);
  const [loading, setLoading] = useState(false);
  const visible = openMenuId === m.id;
  const msgRef = useRef(null);

  const doTranslate = useCallback(async () => {
    if (getTranslationFromCache(m.id)) {
      setTranslated(getTranslationFromCache(m.id));
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
        setTranslationInCache(m.id, res.translated);
        setTranslated(res.translated);
      }
    } catch (err) {
      console.warn('Ошибка перевода:', err.message);
    } finally {
      setLoading(false);
    }
  }, [m]);

  const handleContextMenu = e => {
    if(mine){ e.preventDefault(); setOpenMenuId(m.id); }
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
      !mine && !autoTranslate && !translated && React.createElement('button', { key:'btn', className:'dm-translate-btn button-small', onClick:doTranslate, disabled:loading }, loading ? 'Перевожу...' : 'Перевести')
    ]),
    visible && React.createElement('div', { key:'menu', className:'dm-context-menu dm-context-below' }, [
      React.createElement('div', { key:'edit', className:'dm-context-item button-small link-button', onClick:()=>{onEdit(m); setOpenMenuId(null);}}, 'Редактировать'),
      React.createElement('div', { key:'delete', className:'dm-context-item button-small link-button', onClick:()=>{alert('Удаление'); setOpenMenuId(null);}}, 'Удалить')
    ])
  ]);
}

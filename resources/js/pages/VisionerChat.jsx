import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import useDocumentMeta from '../hooks/useDocumentMeta';

export default function VisionerChat() {
    const { t } = useTranslation();
    const [messages, setMessages] = useState([]);
    const [draft, setDraft] = useState('');
    const [sending, setSending] = useState(false);
    const [error, setError] = useState('');
    const bottomRef = useRef(null);

    useDocumentMeta(t('meta_chat_title', { app: t('app_name') }));

    function load() {
        api.get('/api/visioner-chat').then((res) => setMessages(res.data.data));
    }

    useEffect(load, []);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    async function send(e) {
        e.preventDefault();
        const content = draft.trim();
        if (!content || sending) return;

        setSending(true);
        setError('');
        setDraft('');

        try {
            const res = await api.post('/api/visioner-chat', { content });
            setMessages((prev) => [...prev, ...res.data.data]);
        } catch {
            setError(t('chat_error'));
            setDraft(content);
        } finally {
            setSending(false);
        }
    }

    return (
        <div className="flex h-[calc(100vh-10rem)] max-w-3xl flex-col">
            <h1 className="mb-2 font-serif text-2xl">{t('chat_title')}</h1>
            <p className="mb-6 text-sm text-ink-soft">{t('chat_hint')}</p>

            <div className="mb-4 flex-1 overflow-y-auto rounded border border-line p-4">
                {messages.length === 0 && <p className="text-ink-soft">{t('chat_empty')}</p>}
                <div className="space-y-4">
                    {messages.map((m) => (
                        <div key={m.id} className={m.role === 'user' ? 'flex justify-end' : 'flex justify-start'}>
                            <div
                                className={`max-w-[80%] rounded-lg px-4 py-2 text-sm ${
                                    m.role === 'user' ? 'bg-ink text-parchment' : 'bg-parchment-dim text-ink'
                                }`}
                            >
                                <p className="whitespace-pre-wrap">{m.content}</p>
                                {m.epic_id && (
                                    <Link
                                        to="/dashboard/progress"
                                        className="mt-2 inline-block text-xs text-brass underline hover:no-underline"
                                    >
                                        {t('chat_proposed_epic', { title: m.epic_title })}
                                    </Link>
                                )}
                            </div>
                        </div>
                    ))}
                    {sending && (
                        <div className="flex justify-start">
                            <div className="rounded-lg bg-parchment-dim px-4 py-2 text-sm text-ink-soft">
                                {t('chat_thinking')}
                            </div>
                        </div>
                    )}
                </div>
                <div ref={bottomRef} />
            </div>

            {error && (
                <p role="alert" className="mb-2 text-sm text-red-700">
                    {error}
                </p>
            )}

            <form onSubmit={send} className="flex gap-2">
                <label htmlFor="chat-input" className="sr-only">
                    {t('chat_input_label')}
                </label>
                <textarea
                    id="chat-input"
                    value={draft}
                    onChange={(e) => setDraft(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            send(e);
                        }
                    }}
                    rows={2}
                    placeholder={t('chat_input_placeholder')}
                    className="flex-1 resize-none rounded border border-line bg-parchment px-3 py-2 text-sm"
                />
                <button
                    type="submit"
                    disabled={sending || !draft.trim()}
                    className="rounded bg-ink px-4 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-30"
                >
                    {t('chat_send')}
                </button>
            </form>
        </div>
    );
}

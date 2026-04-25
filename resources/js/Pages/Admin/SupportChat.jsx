import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Avatar,
  Badge,
  Box,
  Button,
  Chip,
  Drawer,
  Divider,
  IconButton,
  InputAdornment,
  List,
  ListItemAvatar,
  ListItemButton,
  ListItemText,
  Paper,
  Stack,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';
import {
  BookmarksOutlined as SavedRepliesIcon,
  Close as CloseIcon,
  Image as ImageIcon,
  Refresh as RefreshIcon,
  Search as SearchIcon,
  Send as SendIcon,
} from '@mui/icons-material';

export default function SupportChat() {
  const { admin_app_url, flash } = usePage().props;
  const { supportAdminUserId, selectedConversationId } = usePage().props;
  const [conversationPager, setConversationPager] = useState({ data: [], current_page: 1, last_page: 1, per_page: 25 });
  const [loadedConversations, setLoadedConversations] = useState([]);
  const isAppendingRef = useRef(false);
  const isLoadingMoreRef = useRef(false);
  const convScrollRef = useRef(null);
  const [showLoadingMore, setShowLoadingMore] = useState(false);
  const [activeConversationId, setActiveConversationId] = useState(selectedConversationId || null);
  const [messages, setMessages] = useState([]);
  const [messageText, setMessageText] = useState('');
  const [isSending, setIsSending] = useState(false);
  const [isSendingImage, setIsSendingImage] = useState(false);
  const [isLoadingMessages, setIsLoadingMessages] = useState(false);
  const activeConversationIdRef = useRef(activeConversationId);
  const latestMessageIdRef = useRef(0);
  const imageInputRef = useRef(null);
  const replyInputRef = useRef(null);
  const [pendingImage, setPendingImage] = useState(null);
  const [pendingImageUrl, setPendingImageUrl] = useState('');
  const [savedReplies, setSavedReplies] = useState([]);
  const [isLoadingSavedReplies, setIsLoadingSavedReplies] = useState(false);
  const [savedRepliesDrawerOpen, setSavedRepliesDrawerOpen] = useState(false);
  const [savedRepliesQuery, setSavedRepliesQuery] = useState('');
  const [recentSavedReplyIds, setRecentSavedReplyIds] = useState([]);

  useEffect(() => {
    if (!pendingImage) {
      if (pendingImageUrl) {
        URL.revokeObjectURL(pendingImageUrl);
        setPendingImageUrl('');
      }
      return;
    }
    const url = URL.createObjectURL(pendingImage);
    setPendingImageUrl(url);
    return () => {
      URL.revokeObjectURL(url);
    };
  }, [pendingImage]);

  useEffect(() => {
    const prevBodyOverflow = document.body.style.overflow;
    const prevHtmlOverflow = document.documentElement.style.overflow;
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';
    return () => {
      document.body.style.overflow = prevBodyOverflow;
      document.documentElement.style.overflow = prevHtmlOverflow;
    };
  }, []);

  useEffect(() => {
    activeConversationIdRef.current = activeConversationId;
  }, [activeConversationId]);

  useEffect(() => {
    const last = Array.isArray(messages) && messages.length > 0 ? messages[messages.length - 1] : null;
    latestMessageIdRef.current = last?.id ? Number(last.id) : 0;
  }, [messages]);

  const selectedConversation = useMemo(() => {
    if (!Array.isArray(loadedConversations) || !activeConversationId) {
      return null;
    }
    return loadedConversations.find((c) => Number(c.id) === Number(activeConversationId)) || null;
  }, [loadedConversations, activeConversationId]);

  const messageListRef = useRef(null);

  useEffect(() => {
    const el = messageListRef.current;
    if (!el) return;
    el.scrollTop = el.scrollHeight;
  }, [activeConversationId, messages]);

  const fetchConversationsPage = (page, perPage) => {
    return window.axios
      .get(`${admin_app_url}/support-chat/conversations`, {
        params: { page, perPage },
      })
      .then((res) => res.data);
  };

  useEffect(() => {
    fetchConversationsPage(1, 25)
      .then((pager) => {
        setConversationPager(pager);
        const items = Array.isArray(pager?.data) ? pager.data : [];
        setLoadedConversations(items);

        if (items.length > 0) {
          const firstId = items[0].id;
          setActiveConversationId((prev) => {
            const next = prev || firstId;
            const url = new URL(window.location.href);
            url.searchParams.set('conversationId', String(next));
            window.history.replaceState({}, '', url.toString());
            return next;
          });
        }
      })
      .catch(() => {});
  }, [admin_app_url]);

  const loadSavedReplies = React.useCallback(() => {
    setIsLoadingSavedReplies(true);
    return window.axios
      .get(`${admin_app_url}/save-replies/options`)
      .then((res) => {
        const items = Array.isArray(res.data?.data) ? res.data.data : [];
        setSavedReplies(items);
      })
      .catch(() => {})
      .finally(() => setIsLoadingSavedReplies(false));
  }, [admin_app_url]);

  useEffect(() => {
    loadSavedReplies();
  }, [loadSavedReplies]);

  useEffect(() => {
    try {
      const raw = window.localStorage.getItem('admin-saved-replies-recent');
      const parsed = raw ? JSON.parse(raw) : [];
      if (Array.isArray(parsed)) {
        setRecentSavedReplyIds(parsed.map((v) => Number(v)).filter((v) => Number.isFinite(v) && v > 0).slice(0, 6));
      }
    } catch (e) {}
  }, []);

  const fetchMessages = (conversationId) => {
    setIsLoadingMessages(true);
    return window.axios
      .get(`${admin_app_url}/support-chat/messages`, {
        params: { conversationId, limit: 50 },
      })
      .then((res) => {
        const data = Array.isArray(res.data?.data) ? res.data.data : [];
        setMessages(data);
        setLoadedConversations((prev) => {
          const list = Array.isArray(prev) ? [...prev] : [];
          const idx = list.findIndex((c) => Number(c.id) === Number(conversationId));
          if (idx >= 0) {
            list[idx] = { ...list[idx], unread_count: 0 };
            return list;
          }
          return prev;
        });
      })
      .catch(() => {})
      .finally(() => setIsLoadingMessages(false));
  };

  useEffect(() => {
    if (!activeConversationId) return;
    fetchMessages(activeConversationId);
  }, [activeConversationId]);

  useEffect(() => {
    const poll = () => {
      const convId = activeConversationIdRef.current;
      if (!convId) return;
      if (document.visibilityState && document.visibilityState !== 'visible') return;
      if (isLoadingMessages) return;

      const afterId = Number(latestMessageIdRef.current || 0);
      window.axios
        .get(`${admin_app_url}/support-chat/messages`, {
          params: { conversationId: convId, afterId, limit: 50 },
        })
        .then((res) => {
          const incoming = Array.isArray(res.data?.data) ? res.data.data : [];
          if (incoming.length === 0) return;

          setMessages((prev) => {
            const existingIds = new Set((prev || []).map((m) => Number(m.id)));
            const merged = [...(prev || [])];
            for (const m of incoming) {
              const id = Number(m.id);
              if (!existingIds.has(id)) {
                merged.push(m);
                existingIds.add(id);
              }
            }
            return merged;
          });

          const last = incoming[incoming.length - 1];
          setLoadedConversations((prev) => {
            const list = Array.isArray(prev) ? [...prev] : [];
            const idx = list.findIndex((c) => Number(c.id) === Number(convId));
            if (idx >= 0) {
              const currentConv = list[idx];
              const updated = {
                ...currentConv,
                unread_count: 0,
                last_message_text: last?.message_text || currentConv.last_message_text,
                last_message_type: last?.message_type || currentConv.last_message_type,
                last_message_file_path: last?.file_path || currentConv.last_message_file_path,
                last_message_at: last?.created_at || currentConv.last_message_at,
              };
              list.splice(idx, 1);
              list.unshift(updated);
              return list;
            }
            return prev;
          });
        })
        .catch(() => {});
    };

    const timer = window.setInterval(poll, 3000);
    return () => window.clearInterval(timer);
  }, [admin_app_url, isLoadingMessages]);

  const openConversation = (id) => {
    setActiveConversationId(id);
    const url = new URL(window.location.href);
    url.searchParams.set('conversationId', String(id));
    window.history.replaceState({}, '', url.toString());
  };

  const loadMoreConversations = () => {
    const current = Number(conversationPager.current_page || 1);
    const last = Number(conversationPager.last_page || 1);
    if (current >= last) return;
    if (isLoadingMoreRef.current) return;

    isLoadingMoreRef.current = true;
    isAppendingRef.current = true;
    setShowLoadingMore(true);

    fetchConversationsPage(current + 1, Number(conversationPager.per_page || 25))
      .then((pager) => {
        const pageItems = Array.isArray(pager?.data) ? pager.data : [];
        setConversationPager(pager);
        setLoadedConversations((prev) => {
          const existingIds = new Set((prev || []).map((c) => Number(c.id)));
          const merged = [...(prev || [])];
          for (const c of pageItems) {
            const id = Number(c.id);
            if (!existingIds.has(id)) {
              merged.push(c);
              existingIds.add(id);
            }
          }
          return merged;
        });
      })
      .catch(() => {})
      .finally(() => {
        isAppendingRef.current = false;
        isLoadingMoreRef.current = false;
        setShowLoadingMore(false);
      });
  };

  const applySavedReply = () => {
    const selected = (savedReplies || []).find((row) => Number(row.id) === Number(selectedSavedReplyId));
    if (!selected) return;
    const nextMessage = String(selected.message || '').trim();
    if (nextMessage === '') return;
    setMessageText((prev) => {
      const current = String(prev || '').trim();
      if (current === '') {
        return nextMessage;
      }
      return `${current}\n\n${nextMessage}`;
    });
  };

  const insertReplyText = (text) => {
    const nextMessage = String(text || '').trim();
    if (nextMessage === '') return;
    setMessageText((prev) => {
      const current = String(prev || '').trim();
      if (current === '') {
        return nextMessage;
      }
      return `${current}\n\n${nextMessage}`;
    });
    window.setTimeout(() => {
      const el = replyInputRef.current;
      if (el && typeof el.focus === 'function') {
        el.focus();
      }
    }, 0);
  };

  const useSavedReply = (reply) => {
    if (!reply) return;
    insertReplyText(reply.message);
    setSavedRepliesDrawerOpen(false);

    const id = Number(reply.id);
    if (Number.isFinite(id) && id > 0) {
      setRecentSavedReplyIds((prev) => {
        const next = [id, ...(prev || []).filter((v) => Number(v) !== id)].slice(0, 6);
        try {
          window.localStorage.setItem('admin-saved-replies-recent', JSON.stringify(next));
        } catch (e) {}
        return next;
      });
    }
  };

  const filteredSavedReplies = useMemo(() => {
    const q = String(savedRepliesQuery || '').trim().toLowerCase();
    if (!q) {
      return savedReplies;
    }
    return (savedReplies || []).filter((r) => {
      const title = String(r?.title || '').toLowerCase();
      const msg = String(r?.message || '').toLowerCase();
      return title.includes(q) || msg.includes(q);
    });
  }, [savedReplies, savedRepliesQuery]);

  const recentSavedReplies = useMemo(() => {
    if (!Array.isArray(recentSavedReplyIds) || recentSavedReplyIds.length === 0) {
      return [];
    }
    const map = new Map((savedReplies || []).map((r) => [Number(r.id), r]));
    return recentSavedReplyIds.map((id) => map.get(Number(id))).filter(Boolean);
  }, [recentSavedReplyIds, savedReplies]);

  const onConversationScroll = (e) => {
    const el = e.currentTarget;
    if (!el) return;
    const distanceFromBottom = el.scrollHeight - el.scrollTop - el.clientHeight;
    if (distanceFromBottom < 160) {
      loadMoreConversations();
    }
  };

  const sendMessage = (e) => {
    e.preventDefault();
    if (!activeConversationId) return;
    const trimmed = String(messageText || '').trim();
    if (!pendingImage && trimmed === '') return;
    if (isSending) return;

    setIsSending(true);
    const hasImage = Boolean(pendingImage);
    if (hasImage) {
      setIsSendingImage(true);
    }

    const request = hasImage
      ? (() => {
          const form = new FormData();
          form.append('conversationId', String(activeConversationId));
          if (trimmed !== '') {
            form.append('messageText', trimmed);
          }
          form.append('image', pendingImage);
          return window.axios.post(`${admin_app_url}/support-chat/messages`, form, {
            headers: { 'Content-Type': 'multipart/form-data' },
          });
        })()
      : window.axios.post(`${admin_app_url}/support-chat/messages`, {
          conversationId: activeConversationId,
          messageText: trimmed,
        });

    request
      .then((res) => {
        const msg = res.data?.message;
        if (msg) {
          setMessages((prev) => [...(prev || []), msg]);
        }

        setLoadedConversations((prev) => {
          const list = Array.isArray(prev) ? [...prev] : [];
          const idx = list.findIndex((c) => Number(c.id) === Number(activeConversationId));
          if (idx >= 0) {
            const currentConv = list[idx];
            const updated = {
              ...currentConv,
              last_message_text: msg?.message_text || currentConv.last_message_text,
              last_message_type: msg?.message_type || currentConv.last_message_type,
              last_message_file_path: msg?.file_path || currentConv.last_message_file_path,
              last_message_at: res.data?.conversation?.last_message_at || currentConv.last_message_at,
            };
            list.splice(idx, 1);
            list.unshift(updated);
          }
          return list;
        });

        setMessageText('');
        setPendingImage(null);
      })
      .catch(() => {})
      .finally(() => {
        setIsSending(false);
        setIsSendingImage(false);
      });
  };

  return (
    <AdminLayout>
      <Head title="Support Chat" />

      <Box
        sx={{
          height: 'calc(100vh - 80px)',
          '@supports (height: 100dvh)': {
            height: 'calc(100dvh - 80px)',
          },
          overflow: 'hidden',
        }}
      >
      <Paper variant="outlined" sx={{ borderRadius: 2, overflow: 'hidden', height: '100%' }}>
        <Stack direction={{ xs: 'column', md: 'row' }} sx={{ height: '100%', minHeight: 0 }}>
            <Box
              sx={{
                width: { xs: '100%', md: 320 },
                flex: { xs: '0 0 40%', md: '0 0 320px' },
                borderRight: { md: '1px solid' },
                borderBottom: { xs: '1px solid', md: 'none' },
                borderColor: 'divider',
                display: 'flex',
                flexDirection: 'column',
                minHeight: 0,
              }}
            >
              <Box sx={{ p: 1.5 }}>
                <Stack direction="row" justifyContent="space-between" alignItems="center">
                  <Typography variant="subtitle2" sx={{ opacity: 0.8 }}>
                    Conversations
                  </Typography>
                  {flash?.error ? (
                    <Typography variant="caption" color="error" noWrap>
                      {flash.error}
                    </Typography>
                  ) : null}
                </Stack>
              </Box>
              <Divider />
              <Box
                ref={convScrollRef}
                onScroll={onConversationScroll}
                sx={{ flex: 1, minHeight: 0, overflow: 'auto' }}
              >
                <List dense disablePadding>
                {loadedConversations.map((c) => {
                  const isSelected = Number(c.id) === Number(activeConversationId);
                  const friendName = c.friend?.name || `User ${c.other_user_id}`;
                  const secondary =
                    c.last_message_type === 'image'
                      ? 'Sent an image'
                      : c.last_message_type === 'file'
                        ? 'Sent a file'
                        : c.last_message_text || '';

                  return (
                    <ListItemButton
                      key={c.id}
                      selected={isSelected}
                      onClick={() => openConversation(c.id)}
                      sx={{ py: 1 }}
                    >
                      <ListItemAvatar>
                        <Badge
                          color="error"
                          badgeContent={c.unread_count > 0 ? c.unread_count : 0}
                          invisible={!c.unread_count || c.unread_count <= 0}
                        >
                          <Avatar src={c.friend?.image || ''} />
                        </Badge>
                      </ListItemAvatar>
                      <ListItemText
                        primary={
                          <Stack direction="row" spacing={1} alignItems="center">
                            <Typography variant="subtitle2" sx={{ fontWeight: 700 }} noWrap>
                              {friendName}
                            </Typography>
                            {c.major ? <Chip size="small" label={c.major} /> : null}
                          </Stack>
                        }
                        secondary={
                          <Typography
                            variant="caption"
                            sx={{
                              opacity: 0.75,
                              overflow: 'hidden',
                              display: '-webkit-box',
                              WebkitBoxOrient: 'vertical',
                              WebkitLineClamp: 2,
                              lineHeight: 1.35,
                            }}
                          >
                            {secondary}
                          </Typography>
                        }
                      />
                    </ListItemButton>
                  );
                })}
                {loadedConversations.length === 0 ? (
                  <Box sx={{ p: 2 }}>
                    <Typography variant="body2" sx={{ opacity: 0.7 }}>
                      No conversations yet.
                    </Typography>
                  </Box>
                ) : null}
                </List>
                <Box sx={{ px: 1.5, py: 1.25 }}>
                  {showLoadingMore ? (
                    <Typography variant="caption" sx={{ opacity: 0.7 }}>
                      Loading more…
                    </Typography>
                  ) : Number(conversationPager.current_page || 1) < Number(conversationPager.last_page || 1) ? (
                    <Typography variant="caption" sx={{ opacity: 0.5 }}>
                      Scroll to load more
                    </Typography>
                  ) : (
                    <Typography variant="caption" sx={{ opacity: 0.5 }}>
                      End
                    </Typography>
                  )}
                </Box>
              </Box>
            </Box>

            <Box sx={{ flex: 1, display: 'flex', flexDirection: 'column', minHeight: 0 }}>
              <Box sx={{ p: 1.5, borderBottom: '1px solid', borderColor: 'divider' }}>
                <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between">
                  <Typography variant="subtitle2" sx={{ fontWeight: 700 }} noWrap>
                    {selectedConversation?.friend?.name
                      ? selectedConversation.friend.name
                      : selectedConversation
                        ? `Conversation #${selectedConversation.id}`
                        : 'Select a conversation'}
                  </Typography>
                  {selectedConversation?.major ? <Chip size="small" label={selectedConversation.major} /> : null}
                </Stack>
                {selectedConversation?.friend?.phone || selectedConversation?.friend?.email ? (
                  <Typography variant="caption" sx={{ opacity: 0.75 }} noWrap>
                    {[selectedConversation.friend.phone, selectedConversation.friend.email].filter(Boolean).join(' • ')}
                  </Typography>
                ) : null}
              </Box>

              <Box
                ref={messageListRef}
                sx={{
                  flex: 1,
                  minHeight: 0,
                  overflow: 'auto',
                  p: 2,
                  bgcolor: 'background.default',
                }}
              >
                {(messages || []).map((m) => {
                  const isMe = Number(m.sender_id) === Number(supportAdminUserId);
                  const filePath = String(m.file_path || '').trim();
                  const fileUrl = filePath
                    ? filePath.startsWith('http://') || filePath.startsWith('https://')
                      ? filePath
                      : `https://www.calamuseducation.com/${filePath.replace(/^\/+/, '')}`
                    : '';
                  const text = String(m.message_text || '').trim();
                  const isImage = String(m.message_type || '') === 'image' && !!fileUrl;
                  const isFile = String(m.message_type || '') === 'file' && !!fileUrl;
                  return (
                    <Box
                      key={m.id}
                      sx={{
                        display: 'flex',
                        justifyContent: isMe ? 'flex-end' : 'flex-start',
                        mb: 1,
                      }}
                    >
                      <Box
                        sx={{
                          maxWidth: '78%',
                          px: 1.5,
                          py: 1,
                          borderRadius: 2,
                          bgcolor: isMe ? 'primary.main' : 'background.paper',
                          color: isMe ? 'primary.contrastText' : 'text.primary',
                          border: isMe ? 'none' : '1px solid',
                          borderColor: isMe ? 'transparent' : 'divider',
                          whiteSpace: 'pre-wrap',
                          wordBreak: 'break-word',
                          p: isImage ? 0.75 : undefined,
                        }}
                      >
                        {isImage ? (
                          <Box>
                            <Box
                              component="a"
                              href={fileUrl}
                              target="_blank"
                              rel="noreferrer"
                              sx={{ display: 'block' }}
                            >
                              <Box
                                component="img"
                                src={fileUrl}
                                alt="Chat image"
                                sx={{
                                  display: 'block',
                                  width: '100%',
                                  maxWidth: 360,
                                  height: 'auto',
                                  borderRadius: 1.25,
                                }}
                              />
                            </Box>
                            {text ? (
                              <Typography variant="body2" sx={{ mt: 0.75 }}>
                                {text}
                              </Typography>
                            ) : null}
                          </Box>
                        ) : isFile ? (
                          <Typography
                            variant="body2"
                            component="a"
                            href={fileUrl}
                            target="_blank"
                            rel="noreferrer"
                            sx={{ color: 'inherit', textDecoration: 'underline' }}
                          >
                            {fileUrl}
                          </Typography>
                        ) : (
                          <Typography variant="body2">{text || fileUrl}</Typography>
                        )}
                      </Box>
                    </Box>
                  );
                })}
                {(!messages || messages.length === 0) && activeConversationId && !isLoadingMessages ? (
                  <Typography variant="body2" sx={{ opacity: 0.7 }}>
                    No messages yet.
                  </Typography>
                ) : null}
              </Box>

              <Box sx={{ p: 1.5, borderTop: '1px solid', borderColor: 'divider' }}>
                <Box component="form" onSubmit={sendMessage}>
                  {pendingImage ? (
                    <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 1 }}>
                      {pendingImageUrl ? (
                        <Box
                          component="img"
                          src={pendingImageUrl}
                          alt="Selected upload"
                          sx={{ width: 36, height: 36, borderRadius: 1, objectFit: 'cover', border: '1px solid', borderColor: 'divider' }}
                        />
                      ) : null}
                      <Chip
                        size="small"
                        label={pendingImage?.name || 'Selected image'}
                        onDelete={() => setPendingImage(null)}
                        deleteIcon={<CloseIcon />}
                        variant="outlined"
                        sx={{ maxWidth: 320 }}
                      />
                    </Stack>
                  ) : null}
                  <Stack direction="row" spacing={1} alignItems="flex-end">
                    <input
                      ref={imageInputRef}
                      type="file"
                      accept="image/*"
                      style={{ display: 'none' }}
                      onChange={(e) => {
                        const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
                        e.target.value = '';
                        if (!file) return;
                        setPendingImage(file);
                      }}
                      disabled={!activeConversationId || isSendingImage}
                    />
                    <IconButton
                      onClick={() => imageInputRef.current && imageInputRef.current.click()}
                      disabled={!activeConversationId || isSendingImage}
                      size="small"
                    >
                      <ImageIcon />
                    </IconButton>
                    <Tooltip title="Saved replies">
                      <span>
                        <IconButton
                          onClick={() => setSavedRepliesDrawerOpen(true)}
                          disabled={!activeConversationId || isSending || isSendingImage}
                          size="small"
                        >
                          <SavedRepliesIcon />
                        </IconButton>
                      </span>
                    </Tooltip>
                    <TextField
                      fullWidth
                      multiline
                      minRows={1}
                      maxRows={4}
                      size="small"
                      label="Reply"
                      value={messageText}
                      onChange={(e) => setMessageText(e.target.value)}
                      inputRef={replyInputRef}
                      disabled={!activeConversationId || isSending || isSendingImage}
                    />
                    <Button
                      variant="contained"
                      type="submit"
                      startIcon={<SendIcon />}
                      disabled={!activeConversationId || isSending || isSendingImage || (!pendingImage && String(messageText || '').trim() === '')}
                    >
                      Send
                    </Button>
                  </Stack>
                </Box>
              </Box>
            </Box>
        </Stack>
      </Paper>
      </Box>
      <Drawer
        anchor="right"
        open={savedRepliesDrawerOpen}
        onClose={() => setSavedRepliesDrawerOpen(false)}
        ModalProps={{ keepMounted: true }}
        sx={{
          zIndex: (theme) => theme.zIndex.drawer + 3,
          '& .MuiBackdrop-root': {
            zIndex: (theme) => theme.zIndex.drawer + 2,
          },
          '& .MuiDrawer-paper': {
            zIndex: (theme) => theme.zIndex.drawer + 3,
          },
        }}
        PaperProps={{
          sx: {
            width: { xs: '100%', sm: 420 },
            borderLeft: '1px solid',
            borderColor: 'divider',
          },
        }}
      >
        <Box sx={{ p: 2, display: 'flex', flexDirection: 'column', height: '100%' }}>
          <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between">
            <Box>
              <Typography variant="subtitle1" sx={{ fontWeight: 800 }}>
                Saved Replies
              </Typography>
              <Typography variant="caption" sx={{ opacity: 0.7 }}>
                Click a template to insert into the reply box
              </Typography>
            </Box>
            <Stack direction="row" spacing={0.5} alignItems="center">
              <Tooltip title="Refresh">
                <span>
                  <IconButton size="small" onClick={loadSavedReplies} disabled={isLoadingSavedReplies}>
                    <RefreshIcon fontSize="small" />
                  </IconButton>
                </span>
              </Tooltip>
              <IconButton size="small" onClick={() => setSavedRepliesDrawerOpen(false)}>
                <CloseIcon fontSize="small" />
              </IconButton>
            </Stack>
          </Stack>

          <Box sx={{ mt: 1.5 }}>
            <TextField
              size="small"
              fullWidth
              placeholder="Search saved replies"
              value={savedRepliesQuery}
              onChange={(e) => setSavedRepliesQuery(e.target.value)}
              InputProps={{
                startAdornment: (
                  <InputAdornment position="start">
                    <SearchIcon fontSize="small" />
                  </InputAdornment>
                ),
              }}
            />
          </Box>

          {recentSavedReplies.length > 0 ? (
            <Box sx={{ mt: 2 }}>
              <Typography variant="overline" sx={{ opacity: 0.7 }}>
                Recent
              </Typography>
              <Stack direction="row" spacing={1} sx={{ mt: 0.75, flexWrap: 'wrap' }}>
                {recentSavedReplies.map((r) => (
                  <Chip
                    key={`recent-sr-${r.id}`}
                    label={String(r.title || '').slice(0, 24)}
                    onClick={() => useSavedReply(r)}
                    size="small"
                    sx={{ fontWeight: 700 }}
                  />
                ))}
              </Stack>
            </Box>
          ) : null}

          <Divider sx={{ my: 2 }} />

          <Box sx={{ flex: 1, minHeight: 0, overflow: 'auto' }}>
            {filteredSavedReplies.length === 0 ? (
              <Box sx={{ py: 2 }}>
                <Typography variant="body2" sx={{ opacity: 0.75 }}>
                  {savedReplies.length === 0 ? 'No saved replies yet.' : 'No matches.'}
                </Typography>
                <Button component={Link} href={`${admin_app_url}/save-replies`} variant="outlined" size="small" sx={{ mt: 1.5 }}>
                  Manage Saved Replies
                </Button>
              </Box>
            ) : (
              <List disablePadding>
                {filteredSavedReplies.map((r) => {
                  const title = String(r?.title || '').trim() || 'Untitled';
                  const msg = String(r?.message || '').trim();
                  return (
                    <Box key={`sr-${r.id}`} sx={{ mb: 1 }}>
                      <Paper variant="outlined" sx={{ borderRadius: 2, overflow: 'hidden' }}>
                        <ListItemButton
                          onClick={() => useSavedReply(r)}
                          sx={{
                            alignItems: 'flex-start',
                            py: 1.25,
                          }}
                        >
                          <Box sx={{ width: '100%' }}>
                            <Stack direction="row" alignItems="flex-start" justifyContent="space-between" spacing={1}>
                              <Box sx={{ minWidth: 0 }}>
                                <Typography variant="subtitle2" sx={{ fontWeight: 800 }} noWrap>
                                  {title}
                                </Typography>
                                <Typography
                                  variant="body2"
                                  sx={{
                                    mt: 0.5,
                                    opacity: 0.8,
                                    overflow: 'hidden',
                                    display: '-webkit-box',
                                    WebkitBoxOrient: 'vertical',
                                    WebkitLineClamp: 3,
                                    whiteSpace: 'pre-wrap',
                                    lineHeight: 1.4,
                                  }}
                                >
                                  {msg}
                                </Typography>
                              </Box>
                              <Button
                                variant="contained"
                                size="small"
                                onClick={(e) => {
                                  e.preventDefault();
                                  e.stopPropagation();
                                  useSavedReply(r);
                                }}
                              >
                                Insert
                              </Button>
                            </Stack>
                          </Box>
                        </ListItemButton>
                      </Paper>
                    </Box>
                  );
                })}
              </List>
            )}
          </Box>

          <Divider sx={{ my: 2 }} />

          <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between">
            <Typography variant="caption" sx={{ opacity: 0.65 }}>
              Tip: keep titles short for faster selection
            </Typography>
            <Button component={Link} href={`${admin_app_url}/save-replies`} size="small" variant="text">
              Manage
            </Button>
          </Stack>
        </Box>
      </Drawer>
    </AdminLayout>
  );
}

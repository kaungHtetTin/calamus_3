import React, { useMemo } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Alert,
  Avatar,
  Box,
  Button,
  Chip,
  Divider,
  List,
  ListItemAvatar,
  ListItemButton,
  ListItemText,
  Paper,
  Stack,
  Typography,
} from '@mui/material';
import {
  NotificationsNone as NotificationsNoneIcon,
  DoneAll as DoneAllIcon,
} from '@mui/icons-material';

export default function Notifications({ notifications, unreadCount }) {
  const { admin_app_url, flash } = usePage().props;

  const rows = Array.isArray(notifications?.data) ? notifications.data : [];

  const { post, processing } = useForm({});

  const adminBase = String(admin_app_url || '').replace(/\/$/, '');

  const markAllRead = () => {
    post(`${adminBase}/notifications/mark-all-read`, { preserveScroll: true });
  };

  const markOneRead = (id) => {
    post(`${adminBase}/notifications/mark-one-read`, {
      data: { notificationId: id },
      preserveScroll: true,
    });
  };

  const markOneReadSilent = (id) => {
    if (!window.axios) return;
    window.axios.post(`${adminBase}/notifications/mark-one-read`, { notificationId: id }).catch(() => {});
  };

  const formatTime = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleString();
  };

  const titleFor = (n) => {
    const t = String(n?.type || '').toLowerCase();
    const actorName = String(n?.actor?.name || '').trim();
    if (t === 'payment.created') return 'New Payment Submitted';
    if (t === 'payment.activated') return 'Subscription Activated';
    if (t === 'chat.support') return actorName ? `Support Message • ${actorName}` : 'Support Message';
    if (t === 'chat.message') return actorName ? `New Message • ${actorName}` : 'New Message';
    if (t === 'lesson.added') return 'New Lesson Added';
    if (t === 'lesson.comment') return actorName ? `Lesson Comment • ${actorName}` : 'Lesson Comment';
    if (t === 'comment.reply') return actorName ? `New Reply • ${actorName}` : 'New Reply';
    if (t === 'comment.created' || t === 'post.comment') return actorName ? `New Comment • ${actorName}` : 'New Comment';
    if (t === 'post.like') return actorName ? `Post Liked • ${actorName}` : 'Post Liked';
    if (t === 'comment.like') return actorName ? `Comment Liked • ${actorName}` : 'Comment Liked';
    return n?.type ? String(n.type) : 'Notification';
  };

  const subtitleFor = (n) => {
    const t = String(n?.type || '').toLowerCase();
    const target = n?.target || {};
    const meta = n?.metadata || {};
    if (t === 'payment.created') {
      const amount = meta?.amount ? String(meta.amount) : '';
      const major = target?.major ? String(target.major) : '';
      return [amount ? `Amount: ${amount}` : '', major ? `Major: ${major}` : ''].filter(Boolean).join(' • ');
    }
    if (t === 'lesson.added') {
      const lessonId = target?.lessonId || meta?.lessonId || '';
      return lessonId ? `Lesson #${lessonId}` : '';
    }
    if (t === 'lesson.comment' || t === 'lesson.comment_reply') {
      const lessonId = target?.lessonId || meta?.lessonId || '';
      const commentId = target?.commentId || meta?.commentId || '';
      return [lessonId ? `Lesson #${lessonId}` : '', commentId ? `Comment #${commentId}` : ''].filter(Boolean).join(' • ');
    }
    if (target?.postId) return `Post #${target.postId}`;
    if (target?.conversationId) return `Conversation #${target.conversationId}`;
    return '';
  };

  const chips = useMemo(() => {
    const total = Number(notifications?.total || rows.length || 0);
    const unread = Number(unreadCount || 0);
    return { total, unread };
  }, [notifications?.total, rows.length, unreadCount]);

  return (
    <AdminLayout>
      <Head title="Notifications" />

      <Stack spacing={2}>
        <Stack
          direction={{ xs: 'column', md: 'row' }}
          justifyContent="space-between"
          alignItems={{ xs: 'flex-start', md: 'center' }}
          spacing={1.5}
        >
          <Box>
            <Stack direction="row" spacing={1} alignItems="center">
              <NotificationsNoneIcon color="action" />
              <Typography variant="h5" sx={{ fontWeight: 700 }}>
                Notifications
              </Typography>
            </Stack>
            <Typography variant="body2" color="text.secondary">
              Admin notifications (userId 10000) with quick navigation.
            </Typography>
          </Box>
          <Stack direction="row" spacing={1} alignItems="center">
            <Chip size="small" variant="outlined" label={`Total: ${chips.total}`} />
            <Chip size="small" color={chips.unread > 0 ? 'warning' : 'default'} variant={chips.unread > 0 ? 'filled' : 'outlined'} label={`Unseen: ${chips.unread}`} />
            <Button
              size="small"
              variant="contained"
              startIcon={<DoneAllIcon />}
              disabled={processing || chips.unread === 0}
              onClick={markAllRead}
            >
              Mark all read
            </Button>
          </Stack>
        </Stack>

        {flash?.error && <Alert severity="error">{flash.error}</Alert>}
        {flash?.success && <Alert severity="success">{flash.success}</Alert>}

        <Paper variant="outlined" sx={{ borderRadius: 2, overflow: 'hidden' }}>
          <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ p: 1.5 }}>
            <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
              Latest
            </Typography>
          </Stack>
          <Divider />

          <List dense disablePadding>
            {rows.map((n) => {
              const unread = !n.readAt;
              const href = n.adminLink ? `${adminBase}${n.adminLink}` : null;
              const actorImage = String(n?.actor?.image || '').trim();
              return (
                <ListItemButton
                  key={n.id}
                  component={href ? Link : 'div'}
                  href={href || undefined}
                  onClick={() => {
                    if (unread) {
                      markOneReadSilent(n.id);
                    }
                  }}
                  sx={{
                    py: 1,
                    bgcolor: unread ? 'rgba(245, 158, 11, 0.10)' : 'transparent',
                    '&:hover': {
                      bgcolor: unread ? 'rgba(245, 158, 11, 0.16)' : 'action.hover',
                    },
                  }}
                >
                  <ListItemAvatar>
                    <Avatar src={actorImage} sx={{ width: 34, height: 34 }} />
                  </ListItemAvatar>
                  <ListItemText
                    disableTypography
                    primary={
                      <Stack direction="row" spacing={1} alignItems="center" sx={{ minWidth: 0 }}>
                        <Typography variant="body2" sx={{ fontWeight: unread ? 800 : 600 }} noWrap>
                          {titleFor(n)}
                        </Typography>
                        <Typography variant="caption" color="text.secondary" sx={{ whiteSpace: 'nowrap' }}>
                          {formatTime(n.createdAt)}
                        </Typography>
                      </Stack>
                    }
                    secondary={
                      <Stack direction="row" spacing={1} alignItems="center" sx={{ mt: 0.25, minWidth: 0 }}>
                        <Typography
                          variant="caption"
                          color="text.secondary"
                          sx={{
                            overflow: 'hidden',
                            display: '-webkit-box',
                            WebkitBoxOrient: 'vertical',
                            WebkitLineClamp: 2,
                            lineHeight: 1.35,
                            minWidth: 0,
                          }}
                        >
                          {subtitleFor(n)}
                        </Typography>
                        {unread ? (
                          <Button
                            size="small"
                            variant="text"
                            onClick={(e) => {
                              e.preventDefault();
                              e.stopPropagation();
                              markOneRead(n.id);
                            }}
                            disabled={processing}
                            sx={{ ml: 'auto', minWidth: 0, px: 1 }}
                          >
                            Mark read
                          </Button>
                        ) : null}
                      </Stack>
                    }
                  />
                </ListItemButton>
              );
            })}

            {rows.length === 0 ? (
              <Box sx={{ p: 2 }}>
                <Typography variant="body2" color="text.secondary">
                  No notifications found.
                </Typography>
              </Box>
            ) : null}
          </List>
        </Paper>

        {notifications?.links ? (
          <Stack direction="row" spacing={1} justifyContent="flex-end">
            {(Array.isArray(notifications.links) ? notifications.links : []).map((l) => (
              <Button
                key={l.label}
                size="small"
                variant={l.active ? 'contained' : 'outlined'}
                disabled={!l.url}
                component={l.url ? Link : 'button'}
                href={l.url || undefined}
              >
                {String(l.label).replace('&laquo;', '«').replace('&raquo;', '»')}
              </Button>
            ))}
          </Stack>
        ) : null}
      </Stack>
    </AdminLayout>
  );
}

import React, { useMemo } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import { Alert, Box, Button, Chip, Paper, Stack, TextField, Typography } from '@mui/material';
import { ThumbUp as ThumbUpIcon, ChatBubble as ChatBubbleIcon, Share as ShareIcon } from '@mui/icons-material';
import CommentItem from '../../Components/Admin/CommentItem';

export default function VideoLessonDetail({ course, category, lesson, comments = [] }) {
  const { admin_app_url, flash } = usePage().props;
  const {
    data: createData,
    setData: setCreateData,
    post,
    processing,
    errors,
    reset,
  } = useForm({
    body: '',
    parent: 0,
  });

  const sortedComments = useMemo(
    () => [...comments].sort((a, b) => Number(b.time) - Number(a.time)),
    [comments]
  );
  const commentsByTime = useMemo(() => {
    const map = {};
    comments.forEach((item) => {
      map[Number(item.time || 0)] = item;
    });
    return map;
  }, [comments]);
  const threadedComments = useMemo(() => {
    const byParent = {};
    const normalized = sortedComments.map((item) => ({
      ...item,
      writerId: item.writerId || item.writer_id,
      writerName: item.writerName || item.writer_name,
      writerImage: item.writerImage || item.writer_image,
      isLiked: Number(item.isLiked || 0),
      child: [],
    }));

    normalized.forEach((item) => {
      const parent = Number(item.parent || 0);
      if (!byParent[parent]) {
        byParent[parent] = [];
      }
      byParent[parent].push(item);
    });

    const bindChildren = (node) => {
      const key = Number(node.time || 0);
      const children = byParent[key] || [];
      node.child = children.map((child) => bindChildren(child));
      return node;
    };

    return (byParent[0] || []).map((node) => bindChildren(node));
  }, [sortedComments]);
  const formatCompactCount = (value) => {
    const count = Number(value || 0);
    if (count < 1000) {
      return String(count);
    }
    if (count < 1000000) {
      return `${(count / 1000).toFixed(1).replace(/\.0$/, '')}K`;
    }
    return `${(count / 1000000).toFixed(1).replace(/\.0$/, '')}M`;
  };
  const engagementItems = useMemo(() => ([
    { key: 'likes', label: 'Likes', icon: <ThumbUpIcon fontSize="inherit" />, count: Number(lesson?.like_count || 0) },
    { key: 'comments', label: 'Comments', icon: <ChatBubbleIcon fontSize="inherit" />, count: Number(lesson?.comment_count ?? sortedComments.length) },
    { key: 'shares', label: 'Shares', icon: <ShareIcon fontSize="inherit" />, count: Number(lesson?.share_count || 0) },
  ]), [lesson?.comment_count, lesson?.like_count, lesson?.share_count, sortedComments.length]);
  const videoEmbedUrl = useMemo(() => {
    const raw = String(lesson?.link || '').trim();
    if (!raw) {
      return '';
    }
    if (raw.includes('player.vimeo.com/video/')) {
      return raw;
    }

    const idMatch = raw.match(/vimeo\.com\/(?:video\/)?(\d+)/i) || raw.match(/\/videos\/(\d+)/i) || raw.match(/^(\d+)$/);
    if (!idMatch) {
      return raw;
    }

    let queryString = '';
    try {
      const url = new URL(raw.startsWith('http') ? raw : `https://${raw}`);
      queryString = url.search || '';
    } catch (error) {
      queryString = '';
    }
    return `https://player.vimeo.com/video/${idMatch[1]}${queryString}`;
  }, [lesson?.link]);

  const submitCreate = (event) => {
    event.preventDefault();
    post(`${admin_app_url}/courses/${course.course_id}/categories/${category.id}/lessons/${lesson.id}/comments`, {
      onSuccess: () => {
        reset('body');
        setCreateData('parent', 0);
      },
    });
  };
  const submitReply = (parentTime, body) => new Promise((resolve, reject) => {
    router.post(`${admin_app_url}/courses/${course.course_id}/categories/${category.id}/lessons/${lesson.id}/comments`, {
      body,
      parent: Number(parentTime || 0),
    }, {
      preserveScroll: true,
      onSuccess: () => resolve(true),
      onError: (err) => reject(err),
    });
  });
  const submitUpdate = (_, commentTime, body, commentId) => new Promise((resolve, reject) => {
    const found = commentsByTime[Number(commentTime || 0)];
    const id = commentId || found?.id;
    if (!id) {
      reject(new Error('Comment not found'));
      return;
    }
    router.patch(`${admin_app_url}/courses/${course.course_id}/categories/${category.id}/lessons/${lesson.id}/comments/${id}`, {
      body,
    }, {
      preserveScroll: true,
      onSuccess: () => resolve(true),
      onError: (err) => reject(err),
    });
  });
  const submitDelete = (_, commentTime, commentId) => new Promise((resolve, reject) => {
    const found = commentsByTime[Number(commentTime || 0)];
    const id = commentId || found?.id;
    if (!id) {
      reject(new Error('Comment not found'));
      return;
    }
    router.delete(`${admin_app_url}/courses/${course.course_id}/categories/${category.id}/lessons/${lesson.id}/comments/${id}`, {
      preserveScroll: true,
      onSuccess: () => resolve(true),
      onError: (err) => reject(err),
    });
  });
  const submitLike = (commentTime, nextLiked) => {
    const found = commentsByTime[Number(commentTime || 0)];
    const id = found?.id;
    if (!id) {
      return;
    }
    window.axios.post(
      `${admin_app_url}/courses/${course.course_id}/categories/${category.id}/lessons/${lesson.id}/comments/${id}/like`,
      { liked: Boolean(nextLiked) }
    ).catch(() => {});
  };

  return (
    <AdminLayout>
      <Head title={`Video Detail - ${lesson.title}`} />
      <Stack spacing={1.5}>
        {Boolean(flash?.success) && <Alert severity="success">{flash.success}</Alert>}
        <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
          <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
            <Typography variant="h6" sx={{ fontWeight: 700 }}>Video Lesson Detail</Typography>
            <Button component={Link} href={`${admin_app_url}/courses/${course.course_id}/edit`} variant="outlined" size="small">
              Back to Course
            </Button>
          </Stack>
          <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
            <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
              Video Player
            </Typography>
          </Stack>
          {videoEmbedUrl ? (
            <Box sx={{ position: 'relative', width: '100%', pt: '56.25%', borderRadius: 1.5, overflow: 'hidden', bgcolor: 'black' }}>
              <Box
                component="iframe"
                src={videoEmbedUrl}
                allow="autoplay; fullscreen; picture-in-picture"
                allowFullScreen
                sx={{
                  position: 'absolute',
                  top: 0,
                  left: 0,
                  width: '100%',
                  height: '100%',
                  border: 0,
                }}
              />
            </Box>
          ) : (
            <Alert severity="warning">No video link available for this lesson.</Alert>
          )}
          <Stack direction={{ xs: 'column', md: 'row' }} justifyContent="space-between" alignItems={{ xs: 'flex-start', md: 'center' }} spacing={1} sx={{ mt: 1 }}>
            <Stack direction="row" spacing={0.75} flexWrap="wrap">
              <Chip size="small" label={`Category: ${category.category_title}`} />
              <Chip size="small" label={`Duration: ${Number(lesson.duration || 0)} sec`} />
              <Chip size="small" label={Number(lesson.isVip || 0) === 1 ? 'VIP' : 'Free'} color={Number(lesson.isVip || 0) === 1 ? 'warning' : 'default'} />
            </Stack>
            <Stack direction="row" spacing={0.75} flexWrap="wrap">
              {engagementItems.map((item) => (
                <Chip
                  key={item.key}
                  size="small"
                  icon={item.icon}
                  label={`${item.label}: ${formatCompactCount(item.count)}`}
                  sx={{ fontWeight: 600 }}
                />
              ))}
            </Stack>
          </Stack>
          <Typography variant="subtitle1" sx={{ fontWeight: 700, mt: 1 }}>{lesson.title}</Typography>
          <Typography variant="body2" color="text.secondary">{lesson.title_mini || '-'}</Typography>
          <Stack direction="row" justifyContent="flex-end" spacing={1} sx={{ mt: 1 }}>
            {lesson.link && (
              <Button component={Link} href={lesson.link} target="_blank" rel="noreferrer" size="small">
                Open Video
              </Button>
            )}
            {lesson.download_url && (
              <Button component={Link} href={lesson.download_url} target="_blank" rel="noreferrer" size="small" variant="contained">
                Download Video
              </Button>
            )}
          </Stack>
        </Paper>

        <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
          <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>Create Comment (Admin #10000)</Typography>
          <Box component="form" onSubmit={submitCreate}>
            <TextField
              multiline
              minRows={2}
              fullWidth
              size="small"
              label="Comment Body"
              value={createData.body}
              onChange={(event) => setCreateData('body', event.target.value)}
              error={Boolean(errors.body)}
              helperText={errors.body}
            />
            <Stack direction="row" justifyContent="flex-end" sx={{ mt: 1 }}>
              <Button type="submit" variant="contained" disabled={processing || !String(createData.body || '').trim()}>
                Add Comment
              </Button>
            </Stack>
          </Box>
        </Paper>

        <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
          <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>Comments ({sortedComments.length})</Typography>
          <Stack spacing={1}>
            {threadedComments.length ? threadedComments.map((comment) => (
              <CommentItem
                key={comment.id || comment.time}
                postId={lesson.id}
                comment={comment}
                currentUserId={10000}
                onDeleteComment={submitDelete}
                onUpdateComment={submitUpdate}
                onLikeComment={submitLike}
                onReplySubmit={submitReply}
                isAuthenticated
                isAdminModerator
              />
            )) : (
              <Typography variant="body2" color="text.secondary">No comments yet.</Typography>
            )}
          </Stack>
        </Paper>
      </Stack>
    </AdminLayout>
  );
}

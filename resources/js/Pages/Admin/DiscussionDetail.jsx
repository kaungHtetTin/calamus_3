import React, { useEffect, useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Alert,
  Box,
  Button,
  Chip,
  Divider,
  IconButton,
  Paper,
  Stack,
  TextField,
  Typography,
} from '@mui/material';
import {
  ArrowBack as ArrowBackIcon,
  Delete as DeleteIcon,
  Favorite as FavoriteIcon,
  Visibility as VisibilityIcon,
  VisibilityOff as VisibilityOffIcon,
  Refresh as RefreshIcon,
} from '@mui/icons-material';
import CommentItem from '../../Components/Admin/CommentItem';

export default function DiscussionDetail({ post, comments: initialComments }) {
  const { admin_app_url, flash } = usePage().props;
  const [currentPost, setCurrentPost] = useState(post || null);
  const [comments, setComments] = useState(Array.isArray(initialComments) ? initialComments : []);
  const [loading, setLoading] = useState(false);

  const [newComment, setNewComment] = useState('');
  const [posting, setPosting] = useState(false);
  const [postError, setPostError] = useState('');

  const postId = Number(currentPost?.postId || 0);

  useEffect(() => {
    setCurrentPost(post || null);
  }, [post]);

  useEffect(() => {
    setComments(Array.isArray(initialComments) ? initialComments : []);
  }, [initialComments]);

  const reload = async () => {
    if (!postId) return;
    setLoading(true);
    try {
      const resp = await window.axios.get(`${admin_app_url}/discussions/${postId}/comments`, { params: { limit: 200 } });
      const json = resp?.data;
      if (json?.success) {
        if (json?.post) {
          setCurrentPost((prev) => (prev ? { ...prev, ...json.post } : json.post));
        }
        setComments(Array.isArray(json.comments) ? json.comments : []);
      }
    } catch (e) {
      return;
    } finally {
      setLoading(false);
    }
  };

  const toggleLikePost = async () => {
    if (!postId) return;
    try {
      const resp = await window.axios.post(`${admin_app_url}/discussions/${postId}/like`);
      const json = resp?.data;
      if (!json?.success) return;
      setCurrentPost((prev) =>
        prev
          ? {
              ...prev,
              postLikes: Number(json.count || 0),
              isLiked: Number(json.isLiked || 0),
            }
          : prev
      );
    } catch (e) {
      return;
    }
  };

  const toggleHidePost = async () => {
    if (!postId) return;
    try {
      const resp = await window.axios.post(`${admin_app_url}/discussions/${postId}/hide`);
      const json = resp?.data;
      if (!json?.success) return;
      setCurrentPost((prev) => (prev ? { ...prev, hidden: Number(json.hidden || 0) } : prev));
    } catch (e) {
      return;
    }
  };

  const deletePost = async () => {
    if (!postId) return;
    if (!confirm(`Delete post ${postId}? This will also delete comments and likes.`)) return;
    try {
      const resp = await window.axios.delete(`${admin_app_url}/discussions/${postId}`);
      if (resp?.data?.success) {
        router.get(`${admin_app_url}/discussions`);
      }
    } catch (e) {
      return;
    }
  };

  const submitNewComment = async () => {
    if (!postId) return;
    const body = String(newComment || '').trim();
    if (!body) return;
    setPosting(true);
    setPostError('');
    try {
      const resp = await window.axios.post(`${admin_app_url}/discussions/${postId}/comments`, { body, parent: 0 });
      const json = resp?.data;
      if (!json?.success) {
        setPostError('Failed to post comment');
        return;
      }
      setNewComment('');
      await reload();
    } catch (e) {
      setPostError('Failed to post comment');
    } finally {
      setPosting(false);
    }
  };

  const onLikeComment = async (commentTime) => {
    if (!postId) return;
    try {
      const resp = await window.axios.post(`${admin_app_url}/discussions/comments/${commentTime}/like`);
      const json = resp?.data;
      if (!json?.success) return;
      const patch = (list) =>
        list.map((c) => {
          if (Number(c.time) === Number(commentTime)) {
            return { ...c, isLiked: Number(json.isLiked || 0), likes: Number(json.likesCount || 0) };
          }
          if (Array.isArray(c.child) && c.child.length) {
            return { ...c, child: patch(c.child) };
          }
          return c;
        });
      setComments((prev) => patch(prev));
    } catch (e) {
      return;
    }
  };

  const onReplySubmit = async (parentTime, body) => {
    if (!postId) return;
    const trimmed = String(body || '').trim();
    if (!trimmed) return;
    await window.axios.post(`${admin_app_url}/discussions/${postId}/comments`, { body: trimmed, parent: Number(parentTime || 0) });
    await reload();
  };

  const onUpdateComment = async (_postId, commentTime, body) => {
    const trimmed = String(body || '').trim();
    if (!trimmed) return;
    await window.axios.patch(`${admin_app_url}/discussions/comments/${commentTime}`, { body: trimmed });
    const patch = (list) =>
      list.map((c) => {
        if (Number(c.time) === Number(commentTime)) {
          return { ...c, body: trimmed };
        }
        if (Array.isArray(c.child) && c.child.length) {
          return { ...c, child: patch(c.child) };
        }
        return c;
      });
    setComments((prev) => patch(prev));
  };

  const onDeleteComment = async (_postId, commentTime) => {
    await window.axios.delete(`${admin_app_url}/discussions/comments/${commentTime}`);
    await reload();
  };

  const title = useMemo(() => {
    if (!postId) return 'Discussion Detail';
    return `Post ${postId}`;
  }, [postId]);

  return (
    <AdminLayout>
      <Head title={title} />
      <Stack spacing={2}>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Stack direction="row" spacing={1} alignItems="center">
            <IconButton size="small" onClick={() => router.get(`${admin_app_url}/discussions`)}>
              <ArrowBackIcon fontSize="small" />
            </IconButton>
            <Typography variant="h6" sx={{ fontWeight: 700 }}>
              {title}
            </Typography>
          </Stack>
          <Button size="small" startIcon={<RefreshIcon />} variant="outlined" onClick={reload} disabled={loading}>
            Refresh
          </Button>
        </Stack>

        {flash?.error && <Alert severity="error">{flash.error}</Alert>}
        {flash?.success && <Alert severity="success">{flash.success}</Alert>}

        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'minmax(320px, 1fr) minmax(420px, 1.4fr)' }, gap: 2 }}>
          <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
            <Stack spacing={1.25}>
              <Stack direction="row" justifyContent="space-between" alignItems="center">
                <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                  Post
                </Typography>
                <Stack direction="row" spacing={0.5}>
                  <IconButton size="small" onClick={toggleLikePost} color={Number(currentPost?.isLiked || 0) === 1 ? 'error' : 'default'}>
                    <FavoriteIcon fontSize="small" />
                  </IconButton>
                  <IconButton size="small" onClick={toggleHidePost}>
                    {Number(currentPost?.hidden || 0) === 1 ? (
                      <VisibilityIcon fontSize="small" />
                    ) : (
                      <VisibilityOffIcon fontSize="small" />
                    )}
                  </IconButton>
                  <IconButton size="small" color="error" onClick={deletePost}>
                    <DeleteIcon fontSize="small" />
                  </IconButton>
                </Stack>
              </Stack>

              <Stack direction="row" spacing={1} alignItems="center">
                <Chip size="small" label={`Likes: ${Number(currentPost?.postLikes || 0)}`} />
                <Chip size="small" label={`Comments: ${Number(currentPost?.comments || 0)}`} />
                <Chip size="small" label={Number(currentPost?.hidden || 0) === 1 ? 'Hidden' : 'Visible'} variant="outlined" />
              </Stack>

              <Divider />

              <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap' }}>
                {String(currentPost?.body || '')}
              </Typography>

              {String(currentPost?.postImage || '').trim() !== '' && (
                <Box
                  component="img"
                  src={currentPost.postImage}
                  alt=""
                  sx={{ width: '100%', borderRadius: 1, border: '1px solid', borderColor: 'divider' }}
                />
              )}
            </Stack>
          </Paper>

          <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
            <Stack spacing={1.25}>
              <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                Comments
              </Typography>

              {postError && <Alert severity="error">{postError}</Alert>}
              <Stack direction="row" spacing={1} alignItems="flex-start">
                <TextField
                  size="small"
                  fullWidth
                  multiline
                  minRows={2}
                  value={newComment}
                  onChange={(e) => setNewComment(e.target.value)}
                  placeholder="Write a comment as Admin…"
                  disabled={posting}
                />
                <Button variant="contained" onClick={submitNewComment} disabled={posting || !String(newComment).trim()}>
                  Send
                </Button>
              </Stack>

              <Divider />

              <Box sx={{ maxHeight: { xs: 'auto', md: '70vh' }, overflowY: { xs: 'visible', md: 'auto' }, pr: { md: 1 } }}>
                {comments.length === 0 && (
                  <Typography variant="body2" color="text.secondary">
                    No comments yet.
                  </Typography>
                )}

                {comments.map((c) => (
                  <CommentItem
                    key={c.id || c.time}
                    postId={postId}
                    comment={c}
                    currentUserId={10000}
                    isAuthenticated
                    isAdminModerator
                    onLikeComment={onLikeComment}
                    onReplySubmit={onReplySubmit}
                    onUpdateComment={onUpdateComment}
                    onDeleteComment={onDeleteComment}
                  />
                ))}
              </Box>
            </Stack>
          </Paper>
        </Box>
      </Stack>
    </AdminLayout>
  );
}


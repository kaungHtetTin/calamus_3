import React, { useState } from 'react';
import {
  Box,
  Stack,
  Typography,
  Avatar,
  IconButton,
  TextField,
  Menu,
  MenuItem,
  ListItemIcon,
  ListItemText,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  Snackbar,
  Alert,
  CircularProgress,
  useTheme,
  alpha,
} from '@mui/material';
import { Send as SendIcon, MoreHoriz as MoreIcon, Delete as DeleteIcon, ContentCopy as CopyIcon, Edit as EditIcon, Check as CheckIcon, Close as CloseIcon, ThumbUp as LikedIcon, ThumbUpOutlined as LikeIcon } from '@mui/icons-material';
const formatNumber = (value) => {
  const count = Number(value || 0);
  if (count < 1000) {
    return String(count);
  }
  if (count < 1000000) {
    return `${(count / 1000).toFixed(1).replace(/\.0$/, '')}K`;
  }
  return `${(count / 1000000).toFixed(1).replace(/\.0$/, '')}M`;
};
const formatRelativeTime = (time) => {
  const value = Number(time || 0);
  if (!value) {
    return '-';
  }
  const diff = Date.now() - value;
  const minute = 60 * 1000;
  const hour = 60 * minute;
  const day = 24 * hour;
  if (diff < minute) {
    return 'Just now';
  }
  if (diff < hour) {
    return `${Math.floor(diff / minute)}m`;
  }
  if (diff < day) {
    return `${Math.floor(diff / hour)}h`;
  }
  if (diff < 7 * day) {
    return `${Math.floor(diff / day)}d`;
  }
  return new Date(value).toLocaleDateString();
};

const CommentItem = ({
  postId,
  comment,
  isReply = false,
  currentUserId,
  onLikeComment,
  onDeleteComment,
  onUpdateComment,
  onReplySubmit,
  onNavigateToPost,
  isAuthenticated,
  isAdminModerator = false,
}) => {
  const [liked, setLiked] = useState(comment.isLiked === 1);
  const [likeCount, setLikeCount] = useState(comment.likes || 0);
  const [showReplyInput, setShowReplyInput] = useState(false);
  const [replyText, setReplyText] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [menuAnchor, setMenuAnchor] = useState(null);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'success' });
  const [deleted, setDeleted] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [editText, setEditText] = useState(comment.body || '');
  const [updating, setUpdating] = useState(false);
  const theme = useTheme();
  const mode = theme.palette.mode;

  const writerId = comment.writerId || comment.writer_id;
  const isOwner = isAdminModerator || (currentUserId && String(writerId) === String(currentUserId));
  const menuOpen = Boolean(menuAnchor);
  
  // Resolve user name and image from various possible keys (API vs Optimistic vs Legacy)
  const userName = comment.writerName || comment.writer_name || comment.userName || comment.learnerName || 'User';
  const userImage = comment.writerImage || comment.writer_image || comment.userImage || comment.learnerImage;

  const handleLike = (e) => {
    if (e) e.stopPropagation();
    setLiked(!liked);
    setLikeCount(liked ? likeCount - 1 : likeCount + 1);
    if (onLikeComment) onLikeComment(comment.time, !liked);
  };

  const handleMenuOpen = (e) => {
    e.stopPropagation();
    setMenuAnchor(e.currentTarget);
  };

  const handleMenuClose = () => setMenuAnchor(null);

  const handleCopyText = () => {
    handleMenuClose();
    if (comment.body && navigator.clipboard?.writeText) {
      navigator.clipboard.writeText(comment.body);
      setSnackbar({ open: true, message: 'Copied to clipboard', severity: 'success' });
    }
  };

  const handleDeleteClick = () => {
    handleMenuClose();
    setDeleteDialogOpen(true);
  };

  const handleDeleteConfirm = async () => {
    if (!onDeleteComment) return;
    setDeleting(true);
    try {
      // The API now expects just the commentId (time), not postId
      await onDeleteComment(postId, comment.time, comment.id);
      setDeleted(true);
      setSnackbar({ open: true, message: 'Comment deleted', severity: 'success' });
      setDeleteDialogOpen(false);
    } catch (err) {
      setSnackbar({ open: true, message: 'Failed to delete comment', severity: 'error' });
    } finally {
      setDeleting(false);
    }
  };

  const handleEditClick = () => {
    handleMenuClose();
    setIsEditing(true);
    setEditText(comment.body || '');
  };

  const handleCancelEdit = () => {
    setIsEditing(false);
    setEditText(comment.body || '');
  };

  const handleSaveEdit = async () => {
    const trimmedText = editText.trim();
    if (!trimmedText || !onUpdateComment) return;
    if (trimmedText === comment.body) {
      setIsEditing(false);
      return;
    }

    setUpdating(true);
    try {
      // The API now expects just the commentId (time) and body, not postId
      await onUpdateComment(postId, comment.time, trimmedText, comment.id);
      setIsEditing(false);
      setSnackbar({ open: true, message: 'Comment updated', severity: 'success' });
    } catch (err) {
      setSnackbar({ open: true, message: 'Failed to update comment', severity: 'error' });
    } finally {
      setUpdating(false);
    }
  };

  const handleReplySubmit = async () => {
    const body = replyText.trim();
    if (!body || !onReplySubmit || !isAuthenticated) return;
    setSubmitting(true);
    try {
      await onReplySubmit(comment.time, body);
      setReplyText('');
      setShowReplyInput(false);
    } catch (err) {
      setSnackbar({ open: true, message: 'Failed to post reply', severity: 'error' });
    } finally {
      setSubmitting(false);
    }
  };

  if (deleted) return null;

  return (
    <>
      <Box
        sx={{ ml: isReply ? 5 : 0, mb: 1.5, cursor: onNavigateToPost ? 'pointer' : 'default' }}
        onClick={onNavigateToPost ? () => onNavigateToPost(postId) : undefined}
      >
        <Stack direction="row" spacing={1} alignItems="flex-start">
          <Avatar
            src={userImage}
            sx={{ width: isReply ? 24 : 32, height: isReply ? 24 : 32 }}
          />
          <Box sx={{ flex: 1, minWidth: 0 }}>
            {isEditing ? (
              <Box sx={{ mb: 1 }}>
                <TextField
                  fullWidth
                  multiline
                  minRows={2}
                  maxRows={6}
                  value={editText}
                  onChange={(e) => setEditText(e.target.value)}
                  disabled={updating}
                  autoFocus
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: 2,
                      fontSize: isReply ? 13 : 14,
                    },
                  }}
                />
                <Stack direction="row" spacing={1} sx={{ mt: 1, justifyContent: 'flex-end' }}>
                  <IconButton
                    size="small"
                    onClick={handleCancelEdit}
                    disabled={updating}
                    sx={{ color: 'text.secondary' }}
                  >
                    <CloseIcon fontSize="small" />
                  </IconButton>
                  <IconButton
                    size="small"
                    color="primary"
                    onClick={handleSaveEdit}
                    disabled={!editText.trim() || updating || editText.trim() === comment.body}
                  >
                    {updating ? (
                      <CircularProgress size={16} />
                    ) : (
                      <CheckIcon fontSize="small" />
                    )}
                  </IconButton>
                </Stack>
              </Box>
            ) : (
              <Stack direction="row" alignItems="flex-start" justifyContent="space-between">
                <Box
                  sx={{
                    bgcolor: mode === 'light' ? 'grey.100' : alpha(theme.palette.common.white, 0.05),
                    borderRadius: '18px',
                    px: 2,
                    py: 1,
                    display: 'inline-block',
                    maxWidth: '100%',
                    border: '1px solid',
                    borderColor: mode === 'light' ? 'transparent' : alpha(theme.palette.common.white, 0.1),
                  }}
                >
                  <Typography variant="subtitle2" fontWeight={600} fontSize={isReply ? 12 : 13} color="text.primary" sx={{ lineHeight: 1.3 }}>
                    {userName}
                  </Typography>
                  <Typography variant="body2" fontSize={isReply ? 13 : 14} color="text.primary" sx={{ whiteSpace: 'pre-wrap', wordBreak: 'break-word', lineHeight: 1.4 }}>
                    {comment.body}
                  </Typography>
                </Box>
                {((comment.body) || (isOwner && (onDeleteComment || onUpdateComment))) && (
                  <IconButton size="small" onClick={handleMenuOpen} sx={{ mt: -0.5 }}>
                    <MoreIcon fontSize="small" />
                  </IconButton>
                )}
              </Stack>
            )}

            <Stack direction="row" spacing={1.5} alignItems="center" sx={{ mt: 0.3, ml: 1.5 }}>
              <Box 
                sx={{ 
                  display: 'flex', 
                  alignItems: 'center', 
                  gap: 0.5,
                  cursor: 'pointer',
                  color: liked ? 'primary.main' : 'text.secondary',
                  '&:hover': {
                    color: liked ? 'primary.dark' : 'text.primary',
                    '& .MuiTypography-root': { textDecoration: 'underline' }
                  }
                }}
                onClick={handleLike}
              >
                {liked ? <LikedIcon sx={{ fontSize: 14 }} /> : <LikeIcon sx={{ fontSize: 14 }} />}
                <Typography
                  variant="caption"
                  sx={{ fontWeight: liked ? 700 : 600, fontSize: 12 }}
                >
                  Like{likeCount > 0 && ` · ${formatNumber(likeCount)}`}
                </Typography>
              </Box>
              {!isReply && onReplySubmit && (
                <Typography
                  variant="caption"
                  color="text.secondary"
                  sx={{ cursor: 'pointer', fontWeight: 600, fontSize: 12, '&:hover': { textDecoration: 'underline' } }}
                  onClick={(e) => {
                    e.stopPropagation();
                    setShowReplyInput(!showReplyInput);
                  }}
                >
                  Reply
                </Typography>
              )}
              <Typography variant="caption" color="text.disabled" fontSize={12}>
                {formatRelativeTime(comment.time)}
              </Typography>
            </Stack>

            {showReplyInput && onReplySubmit && (
              <Stack direction="row" spacing={1} sx={{ mt: 1 }}>
                <TextField
                  size="small"
                  placeholder="Write a reply..."
                  fullWidth
                  value={replyText}
                  onChange={(e) => setReplyText(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && !e.shiftKey && handleReplySubmit()}
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: '20px',
                      fontSize: 13,
                      bgcolor: mode === 'light' ? 'grey.100' : alpha(theme.palette.common.white, 0.05),
                      '& fieldset': { border: 'none' },
                      transition: 'all 0.2s ease',
                      '&:hover': {
                        bgcolor: mode === 'light' ? 'grey.200' : alpha(theme.palette.common.white, 0.08),
                      },
                      '&.Mui-focused': {
                        bgcolor: mode === 'light' ? '#fff' : alpha(theme.palette.common.white, 0.1),
                        boxShadow: `0 0 0 2px ${alpha(theme.palette.primary.main, 0.3)}`,
                      }
                    },
                  }}
                />
                <IconButton size="small" color="primary" onClick={handleReplySubmit} disabled={!replyText.trim() || submitting}>
                  <SendIcon fontSize="small" />
                </IconButton>
              </Stack>
            )}

            {(comment.child && comment.child.length > 0 || comment.replies && comment.replies.length > 0) && (
              <Box sx={{ mt: 1.5 }}>
                {(comment.child || comment.replies).map((reply) => (
                  <CommentItem
                    key={reply.id || reply.time}
                    postId={postId}
                    comment={reply}
                    isReply
                    currentUserId={currentUserId}
                    onLikeComment={onLikeComment}
                    onDeleteComment={onDeleteComment}
                    onUpdateComment={onUpdateComment}
                    onReplySubmit={onReplySubmit}
                    isAuthenticated={isAuthenticated}
                    isAdminModerator={isAdminModerator}
                  />
                ))}
              </Box>
            )}
          </Box>
        </Stack>
      </Box>

      <Menu anchorEl={menuAnchor} open={menuOpen} onClose={handleMenuClose} transformOrigin={{ horizontal: 'right', vertical: 'top' }} anchorOrigin={{ horizontal: 'right', vertical: 'bottom' }}>
        {comment.body && (
          <MenuItem onClick={handleCopyText}>
            <ListItemIcon><CopyIcon fontSize="small" /></ListItemIcon>
            <ListItemText>Copy text</ListItemText>
          </MenuItem>
        )}
        {isOwner && onUpdateComment && (
          <MenuItem onClick={handleEditClick}>
            <ListItemIcon><EditIcon fontSize="small" /></ListItemIcon>
            <ListItemText>Edit</ListItemText>
          </MenuItem>
        )}
        {isOwner && onDeleteComment && (
          <MenuItem onClick={handleDeleteClick}>
            <ListItemIcon><DeleteIcon fontSize="small" color="error" /></ListItemIcon>
            <ListItemText primaryTypographyProps={{ color: 'error.main' }}>Delete</ListItemText>
          </MenuItem>
        )}
      </Menu>

      <Dialog open={deleteDialogOpen} onClose={() => !deleting && setDeleteDialogOpen(false)} maxWidth="xs" fullWidth>
        <DialogTitle>Delete Comment?</DialogTitle>
        <DialogContent>
          <Typography variant="body2" color="text.secondary">This cannot be undone.</Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDeleteDialogOpen(false)} disabled={deleting}>Cancel</Button>
          <Button onClick={handleDeleteConfirm} color="error" variant="contained" disabled={deleting}>{deleting ? 'Deleting...' : 'Delete'}</Button>
        </DialogActions>
      </Dialog>

      <Snackbar open={snackbar.open} autoHideDuration={3000} onClose={() => setSnackbar((s) => ({ ...s, open: false }))} anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}>
        <Alert severity={snackbar.severity} variant="filled">{snackbar.message}</Alert>
      </Snackbar>
    </>
  );
};

export default CommentItem;

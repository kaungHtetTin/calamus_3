import React, { useEffect, useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Alert,
  Box,
  Badge,
  Button,
  Chip,
  Divider,
  IconButton,
  Pagination,
  Paper,
  Stack,
  TextField,
  Typography,
} from '@mui/material';
import {
  Delete as DeleteIcon,
  Forum as ForumIcon,
  Refresh as RefreshIcon,
  Visibility as VisibilityIcon,
  VisibilityOff as VisibilityOffIcon,
  Favorite as FavoriteIcon,
  ChatBubbleOutline as ChatBubbleOutlineIcon,
} from '@mui/icons-material';

export default function Discussions({ posts, filters }) {
  const { admin_app_url, flash } = usePage().props;
  const languages = Array.isArray(usePage().props?.languages) ? usePage().props.languages : [];
  const [rows, setRows] = useState(Array.isArray(posts?.data) ? posts.data : []);
  const [search, setSearch] = useState(filters?.search || '');
  const [hiddenFilter, setHiddenFilter] = useState(filters?.hidden || 'all');
  const [languageFilter, setLanguageFilter] = useState(filters?.language || 'all');

  useEffect(() => {
    setRows(Array.isArray(posts?.data) ? posts.data : []);
  }, [posts?.data]);

  const pageCount = Number(posts?.last_page || 1);
  const currentPage = Number(posts?.current_page || 1);

  const handleSearch = (e) => {
    if (e) e.preventDefault();
    router.get(
      `${admin_app_url}/discussions`,
      { search, hidden: hiddenFilter, language: languageFilter, page: 1 },
      { preserveState: true }
    );
  };

  const reload = () => {
    router.reload({ only: ['posts', 'filters'] });
  };

  const toggleHide = async (postId) => {
    try {
      await window.axios.post(`${admin_app_url}/discussions/${postId}/hide`);
      reload();
    } catch (e) {
      reload();
    }
  };

  const toggleLike = async (postId) => {
    try {
      const resp = await window.axios.post(`${admin_app_url}/discussions/${postId}/like`);
      const json = resp?.data;
      if (!json?.success) return;
      setRows((prev) =>
        prev.map((p) =>
          Number(p.postId) === Number(postId)
            ? { ...p, postLikes: Number(json.count || 0), isLiked: Number(json.isLiked || 0) }
            : p
        )
      );
    } catch (e) {
      return;
    }
  };

  const deletePost = async (postId) => {
    if (!confirm(`Delete post ${postId}? This will also delete comments and likes.`)) return;
    try {
      const resp = await window.axios.delete(`${admin_app_url}/discussions/${postId}`);
      if (resp?.data?.success) {
        setRows((prev) => prev.filter((p) => Number(p.postId) !== Number(postId)));
      } else {
        reload();
      }
    } catch (e) {
      reload();
    }
  };

  const openPostDetail = (postId) => {
    router.get(`${admin_app_url}/discussions/${postId}`);
  };

  const gridTemplateColumns = useMemo(() => {
    return {
      xs: 'minmax(110px, 140px) minmax(240px, 1fr) minmax(110px, 140px) minmax(90px, 110px) 136px',
      md: '130px minmax(340px, 1fr) 140px 110px 136px',
    };
  }, []);

  return (
    <AdminLayout>
      <Head title="Discussions" />
      <Stack spacing={2}>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Stack direction="row" spacing={1} alignItems="center">
            <ForumIcon />
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Discussions
            </Typography>
          </Stack>
          <Button size="small" startIcon={<RefreshIcon />} variant="outlined" onClick={reload}>
            Refresh
          </Button>
        </Stack>

        {flash?.error && <Alert severity="error">{flash.error}</Alert>}
        {flash?.success && <Alert severity="success">{flash.success}</Alert>}

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
          <Stack direction={{ xs: 'column', md: 'row' }} spacing={1} alignItems={{ xs: 'stretch', md: 'center' }}>
            <Box component="form" onSubmit={handleSearch} sx={{ flex: 1 }}>
              <TextField
                size="small"
                fullWidth
                label="Search"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </Box>
            <TextField
              size="small"
              select
              label="Language"
              value={languageFilter}
              onChange={(e) => {
                setLanguageFilter(e.target.value);
                router.get(
                  `${admin_app_url}/discussions`,
                  { search, hidden: hiddenFilter, language: e.target.value, page: 1 },
                  { preserveState: true }
                );
              }}
              SelectProps={{ native: true }}
              sx={{ width: { xs: '100%', md: 180 } }}
            >
              <option value="all">All</option>
              {languages.map((l) => {
                const key = String(l.code || l.module_code || l.name || '').trim();
                const label = String(l.display_name || l.name || l.code || l.module_code || '').trim();
                if (!key) return null;
                return (
                  <option key={String(l.id)} value={key}>
                    {label}
                  </option>
                );
              })}
            </TextField>
            <TextField
              size="small"
              select
              label="Hidden"
              value={hiddenFilter}
              onChange={(e) => {
                setHiddenFilter(e.target.value);
                router.get(
                  `${admin_app_url}/discussions`,
                  { search, hidden: e.target.value, language: languageFilter, page: 1 },
                  { preserveState: true }
                );
              }}
              SelectProps={{ native: true }}
              sx={{ width: { xs: '100%', md: 160 } }}
            >
              <option value="all">All</option>
              <option value="visible">Visible</option>
              <option value="hidden">Hidden</option>
            </TextField>
            <Button size="small" variant="contained" onClick={handleSearch}>
              Search
            </Button>
          </Stack>
        </Paper>

        <Paper variant="outlined" sx={{ p: 0, borderRadius: 2, boxShadow: 'none' }}>
          <Box sx={{ overflowX: 'auto' }}>
            <Box sx={{ minWidth: 820 }}>
              <Box
                sx={{
                  display: 'grid',
                  gridTemplateColumns,
                  gap: 0,
                  p: 1,
                  bgcolor: 'background.default',
                  alignItems: 'center',
                }}
              >
                <Typography variant="caption" sx={{ fontWeight: 700 }} noWrap>
                  Post ID
                </Typography>
                <Typography variant="caption" sx={{ fontWeight: 700 }} noWrap>
                  Body
                </Typography>
                <Typography variant="caption" sx={{ fontWeight: 700 }} noWrap>
                  User
                </Typography>
                <Typography variant="caption" sx={{ fontWeight: 700 }} noWrap>
                  Hidden
                </Typography>
                <Typography variant="caption" sx={{ fontWeight: 700 }} noWrap>
                  Actions
                </Typography>
              </Box>
              <Divider />
              <Stack spacing={0} divider={<Divider />}>
                {rows.map((post) => (
                  <Box
                    key={post.postId}
                    sx={{
                      display: 'grid',
                      gridTemplateColumns,
                      gap: 0,
                      p: 1,
                      alignItems: 'center',
                    }}
                  >
                    <Typography variant="body2" sx={{ fontWeight: 700 }} noWrap>
                      {post.postId}
                    </Typography>
                    <Typography
                      variant="body2"
                      sx={{
                        pr: 1,
                        whiteSpace: 'normal',
                        display: '-webkit-box',
                        overflow: 'hidden',
                        WebkitBoxOrient: 'vertical',
                        WebkitLineClamp: 3,
                        lineHeight: 1.25,
                      }}
                    >
                      {String(post.body || '')}
                    </Typography>
                    <Typography variant="body2" noWrap>
                      {post.userName || 'Anonymous'}
                    </Typography>
                    <Box sx={{ minWidth: 0 }}>
                      <Chip
                        size="small"
                        label={Number(post.hidden || 0) === 1 ? 'Hidden' : 'Visible'}
                        color={Number(post.hidden || 0) === 1 ? 'warning' : 'success'}
                        variant="outlined"
                      />
                    </Box>
                    <Stack direction="row" spacing={0} justifyContent="flex-end" sx={{ minWidth: 0 }}>
                      <IconButton size="small" sx={{ p: 0.5 }} onClick={() => openPostDetail(post.postId)}>
                        <Badge
                          color="info"
                          badgeContent={Number(post.comments || 0)}
                          max={999}
                          anchorOrigin={{ vertical: 'top', horizontal: 'right' }}
                        >
                          <ChatBubbleOutlineIcon fontSize="small" />
                        </Badge>
                      </IconButton>
                      <IconButton
                        size="small"
                        sx={{ p: 0.5 }}
                        color={Number(post.isLiked || 0) === 1 ? 'error' : 'default'}
                        onClick={() => toggleLike(post.postId)}
                      >
                        <Badge
                          color="error"
                          badgeContent={Number(post.postLikes || 0)}
                          max={999}
                          anchorOrigin={{ vertical: 'top', horizontal: 'right' }}
                        >
                          <FavoriteIcon fontSize="small" />
                        </Badge>
                      </IconButton>
                      <IconButton size="small" sx={{ p: 0.5 }} onClick={() => toggleHide(post.postId)}>
                        {Number(post.hidden || 0) === 1 ? (
                          <VisibilityIcon fontSize="small" />
                        ) : (
                          <VisibilityOffIcon fontSize="small" />
                        )}
                      </IconButton>
                      <IconButton size="small" sx={{ p: 0.5 }} color="error" onClick={() => deletePost(post.postId)}>
                        <DeleteIcon fontSize="small" />
                      </IconButton>
                    </Stack>
                  </Box>
                ))}
                {rows.length === 0 && (
                  <Box sx={{ p: 2 }}>
                    <Typography variant="body2" color="text.secondary">
                      No posts found.
                    </Typography>
                  </Box>
                )}
              </Stack>
            </Box>
          </Box>
        </Paper>

        {pageCount > 1 && (
          <Stack direction="row" justifyContent="center">
            <Pagination
              count={pageCount}
              page={currentPage}
              onChange={(_, page) =>
                router.get(`${admin_app_url}/discussions`, { ...filters, search, hidden: hiddenFilter, language: languageFilter, page })
              }
            />
          </Stack>
        )}
      </Stack>
    </AdminLayout>
  );
}

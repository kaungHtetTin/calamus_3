import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Grid,
  Paper,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Box,
  Badge,
  Chip,
  Card,
  CardContent,
  List,
  ListItemAvatar,
  ListItemButton,
  ListItemText,
  Avatar,
  Stack,
  Divider,
} from '@mui/material';
import {
  Apps as AppsIcon,
  Extension as ExtensionIcon,
  Forum as ForumIcon,
  LibraryBooks as LibraryBooksIcon,
  MusicNote as MusicNoteIcon,
  NotificationsNone as NotificationsNoneIcon,
  People as PeopleIcon,
  Person as PersonIcon,
  School as SchoolIcon,
} from '@mui/icons-material';

export default function Dashboard({ stats, recentUsers, recentConversations, recentLessonCommentsByAdmin }) {
  const { admin_app_url } = usePage().props;
  const metrics = [
    { label: 'Users', value: stats.learners_count, icon: <PeopleIcon fontSize="small" />, iconColor: 'primary.main' },
    { label: 'Courses', value: stats.courses_count, icon: <SchoolIcon fontSize="small" />, iconColor: 'secondary.main' },
    { label: 'Lessons', value: stats.lessons_count, icon: <LibraryBooksIcon fontSize="small" />, iconColor: 'success.main' },
    { label: 'Posts', value: stats.posts_count, icon: <ForumIcon fontSize="small" />, iconColor: 'warning.main' },
    { label: 'Songs', value: stats.songs_count, icon: <MusicNoteIcon fontSize="small" />, iconColor: 'success.main' },
    { label: 'Artists', value: stats.artists_count, icon: <PersonIcon fontSize="small" />, iconColor: 'info.main' },
    { label: 'Notifications', value: stats.notifications_count, icon: <NotificationsNoneIcon fontSize="small" />, iconColor: 'warning.main' },
    { label: 'Apps', value: stats.apps_count, icon: <AppsIcon fontSize="small" />, iconColor: 'primary.main' },
    { label: 'Mini Programs', value: stats.mini_programs_count, icon: <ExtensionIcon fontSize="small" />, iconColor: 'secondary.main' },
  ];

  const formatEpoch = (val) => {
    const n = Number(val || 0);
    if (!Number.isFinite(n) || n <= 0) return '';
    const ms = n < 20000000000 ? n * 1000 : n;
    return new Date(ms).toLocaleString();
  };

  const formatConversationPreview = (c) => {
    const type = String(c?.last_message_type || '').toLowerCase();
    if (type === 'image') return 'Sent an image';
    if (type === 'file') return 'Sent a file';
    return String(c?.last_message_text || '').trim();
  };

  return (
    <Box>
      <Head title="Admin Dashboard" />
      
      <Stack
        direction={{ xs: 'column', md: 'row' }}
        justifyContent="space-between"
        alignItems={{ xs: 'flex-start', md: 'center' }}
        spacing={1.5}
        sx={{ mb: 2 }}
      >
        <Box>
          <Typography variant="h5" sx={{ fontWeight: 700 }}>
            Dashboard
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Overview of key areas across the platform.
          </Typography>
        </Box>
      </Stack>

      <Grid container spacing={1.5} sx={{ mb: 2 }}>
        {metrics.map((metric) => (
          <Grid item xs={12} sm={6} lg={3} key={metric.label}>
            <Card variant="outlined" sx={{ borderRadius: 2, boxShadow: 'none' }}>
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Stack direction="row" justifyContent="space-between" alignItems="center">
                  <Typography variant="body2" color="text.secondary">
                    {metric.label}
                  </Typography>
                  <Box sx={{ color: metric.iconColor || 'text.secondary', opacity: 0.95, display: 'flex' }}>{metric.icon}</Box>
                </Stack>
                <Typography variant="h5" sx={{ mt: 0.5, fontWeight: 700, lineHeight: 1.1 }}>
                  {metric.value}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>

      <Box
        sx={{
          display: { xs: 'block', sm: 'grid' },
          gridTemplateColumns: { sm: 'minmax(0, 1fr) minmax(0, 1fr)' },
          gap: 1.5,
          minWidth: 0,
          alignItems: 'start',
        }}
      >
        <Paper variant="outlined" sx={{ width: '100%', overflow: 'hidden', borderRadius: 2, boxShadow: 'none', minWidth: 0 }}>
            <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ p: 1.5 }}>
              <Box>
                <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                  Latest Lesson Comments (User #10000)
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  Recent lesson comments written by admin user 10000.
                </Typography>
              </Box>
            </Stack>
            <Divider />
            <TableContainer sx={{ minWidth: 0 }}>
              <Table size="small" sx={{ minWidth: 0, tableLayout: 'fixed' }} aria-label="latest lesson comments table">
            <TableHead sx={{ bgcolor: 'grey.50' }}>
              <TableRow>
                <TableCell sx={{ fontWeight: 'bold', color: 'text.secondary', py: 0.75 }}>LESSON</TableCell>
                <TableCell sx={{ fontWeight: 'bold', color: 'text.secondary', py: 0.75 }}>COMMENT</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {(recentLessonCommentsByAdmin || []).map((c) => (
                <TableRow
                  key={c.id}
                  hover
                  onClick={() => {
                    if (c?.course_id && c?.category_id && Number(c?.is_video || 0) === 1) {
                      router.visit(`${admin_app_url}/courses/${c.course_id}/categories/${c.category_id}/lessons/${c.lesson_id}/video-detail`);
                    }
                  }}
                  sx={{
                    cursor: c?.course_id && c?.category_id && Number(c?.is_video || 0) === 1 ? 'pointer' : 'default',
                  }}
                >
                  <TableCell sx={{ fontWeight: 600, py: 0.75, width: '35%', overflow: 'hidden' }}>
                    <Stack spacing={0.25}>
                      <Typography
                        variant="body2"
                        sx={{
                          fontWeight: 700,
                          lineHeight: 1.2,
                          overflow: 'hidden',
                          textOverflow: 'ellipsis',
                          whiteSpace: 'nowrap',
                        }}
                      >
                        {c.lesson_title || `Lesson #${c.lesson_id}`}
                      </Typography>
                      <Typography variant="caption" sx={{ opacity: 0.7, lineHeight: 1.2 }}>
                        #{c.lesson_id}
                      </Typography>
                    </Stack>
                  </TableCell>
                  <TableCell sx={{ color: 'text.secondary', py: 0.75, width: '65%', overflow: 'hidden' }}>
                    <Typography
                      variant="body2"
                      sx={{
                        lineHeight: 1.25,
                        overflow: 'hidden',
                        display: '-webkit-box',
                        WebkitBoxOrient: 'vertical',
                        WebkitLineClamp: 3,
                        overflowWrap: 'anywhere',
                      }}
                    >
                      {c.body || ''}
                    </Typography>
                  </TableCell>
                </TableRow>
              ))}
              {(!recentLessonCommentsByAdmin || recentLessonCommentsByAdmin.length === 0) ? (
                <TableRow>
                  <TableCell colSpan={2} sx={{ color: 'text.secondary', py: 1 }}>
                    No lesson comments found.
                  </TableCell>
                </TableRow>
              ) : null}
            </TableBody>
          </Table>
        </TableContainer>
          </Paper>
        <Paper variant="outlined" sx={{ width: '100%', overflow: 'hidden', borderRadius: 2, boxShadow: 'none', minWidth: 0, mt: { xs: 1.5, sm: 0 } }}>
            <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ p: 1.5 }}>
              <Box sx={{ minWidth: 0 }}>
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                Latest Conversations
              </Typography>
              <Typography variant="caption" color="text.secondary">
                Most recent conversations by activity.
              </Typography>
              </Box>
            </Stack>
            <Divider />
            <List dense disablePadding>
              {(recentConversations || []).map((c) => {
                const friendName = c?.friend?.name || `User ${c?.other_user_id || ''}`;
                const secondary = formatConversationPreview(c) || '';
                const to = `${admin_app_url}/support-chat?conversationId=${c.id}`;
                return (
                  <ListItemButton
                    key={c.id}
                    component={Link}
                    href={to}
                    sx={{ py: 1 }}
                  >
                    <ListItemAvatar>
                      <Badge
                        color="error"
                        badgeContent={c.unread_count > 0 ? c.unread_count : 0}
                        invisible={!c.unread_count || c.unread_count <= 0}
                      >
                        <Avatar src={c?.friend?.image || ''} />
                      </Badge>
                    </ListItemAvatar>
                    <ListItemText
                      primary={
                        <Stack direction="row" spacing={1} alignItems="center" sx={{ minWidth: 0 }}>
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
              {(!recentConversations || recentConversations.length === 0) ? (
                <Box sx={{ p: 2 }}>
                  <Typography variant="body2" color="text.secondary">
                    No conversations found.
                  </Typography>
                </Box>
              ) : null}
            </List>
      </Paper>
      </Box>

      <Paper variant="outlined" sx={{ width: '100%', overflow: 'hidden', borderRadius: 2, boxShadow: 'none', mt: 1.5 }}>
        <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ p: 1.5 }}>
          <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
            Recent Users
          </Typography>
        </Stack>
        <Divider />
        <TableContainer sx={{ minWidth: 0 }}>
          <Table size="small" sx={{ minWidth: 0, tableLayout: 'fixed' }} aria-label="recent users table">
            <TableHead sx={{ bgcolor: 'grey.50' }}>
              <TableRow>
                <TableCell sx={{ fontWeight: 'bold', color: 'text.secondary', py: 0.75, whiteSpace: 'nowrap', width: '16%' }}>ID</TableCell>
                <TableCell sx={{ fontWeight: 'bold', color: 'text.secondary', py: 0.75, width: '26%' }}>NAME</TableCell>
                <TableCell sx={{ fontWeight: 'bold', color: 'text.secondary', py: 0.75, width: '38%' }}>EMAIL</TableCell>
                <TableCell sx={{ fontWeight: 'bold', color: 'text.secondary', py: 0.75, whiteSpace: 'nowrap', width: '20%' }}>JOINED</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {(recentUsers || []).map((user) => (
                <TableRow key={user.id} hover>
                  <TableCell sx={{ py: 0.75, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>#{user.id}</TableCell>
                  <TableCell sx={{ fontWeight: 'medium', py: 0.75, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{user.name || ''}</TableCell>
                  <TableCell sx={{ color: 'text.secondary', fontFamily: 'monospace', py: 0.75, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{user.email}</TableCell>
                  <TableCell sx={{ color: 'text.secondary', py: 0.75, whiteSpace: 'nowrap' }}>
                    {user.created_at ? new Date(user.created_at).toLocaleDateString(undefined, {
                      year: 'numeric',
                      month: 'short',
                      day: 'numeric'
                    }) : ''}
                  </TableCell>
                </TableRow>
              ))}
              {(!recentUsers || recentUsers.length === 0) ? (
                <TableRow>
                  <TableCell colSpan={4} sx={{ color: 'text.secondary', py: 1 }}>
                    No users found.
                  </TableCell>
                </TableRow>
              ) : null}
            </TableBody>
          </Table>
        </TableContainer>
      </Paper>
    </Box>
  );
}

Dashboard.layout = page => <AdminLayout children={page} />

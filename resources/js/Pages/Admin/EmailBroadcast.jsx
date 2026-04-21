import React, { useEffect, useMemo, useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Alert,
  Box,
  Button,
  Chip,
  Divider,
  IconButton,
  LinearProgress,
  Paper,
  Snackbar,
  Stack,
  TextField,
  Typography,
} from '@mui/material';
import {
  Email as EmailIcon,
  MoreVert as MoreVertIcon,
  Refresh as RefreshIcon,
  Verified as VerifiedIcon,
} from '@mui/icons-material';

export default function EmailBroadcast({ stats = {}, broadcast = null }) {
  const { admin_app_url, flash } = usePage().props;
  const [openSnackbar, setOpenSnackbar] = useState(false);
  const [broadcastState, setBroadcastState] = useState(broadcast);
  const [broadcastLoading, setBroadcastLoading] = useState(false);

  const resolvedStats = useMemo(() => {
    return {
      totalUsers: Number(stats?.total_users || 0),
      verifiedEmailUsers: Number(stats?.verified_email_users || 0),
    };
  }, [stats]);

  const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
    title: '',
    body: '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    clearErrors();
    post(`${admin_app_url}/users/email`, {
      onSuccess: () => setOpenSnackbar(true),
    });
  };

  const refreshProgress = async () => {
    if (!broadcastState?.id) {
      return;
    }
    setBroadcastLoading(true);
    try {
      const resp = await fetch(`${admin_app_url}/users/email/progress?id=${encodeURIComponent(broadcastState.id)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await resp.json();
      if (json?.broadcast) {
        setBroadcastState(json.broadcast);
      }
    } catch (e) {
      return;
    } finally {
      setBroadcastLoading(false);
    }
  };

  useEffect(() => {
    setBroadcastState(broadcast);
  }, [broadcast?.id]);

  useEffect(() => {
    const status = String(broadcastState?.status || '');
    if (!broadcastState?.id) {
      return;
    }
    if (status !== 'queued' && status !== 'running') {
      return;
    }
    const timer = setInterval(() => {
      refreshProgress();
    }, 3000);
    return () => clearInterval(timer);
  }, [broadcastState?.id, broadcastState?.status]);

  return (
    <AdminLayout>
      <Head title="Email" />
      <Stack spacing={2}>
        <Stack
          direction={{ xs: 'column', md: 'row' }}
          justifyContent="space-between"
          alignItems={{ xs: 'flex-start', md: 'center' }}
          spacing={1.5}
          sx={{ mb: 2 }}
        >
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Email
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Send an email to all users with verified email addresses.
            </Typography>
          </Box>
          <Stack direction="row" spacing={1} alignItems="center">
            <Chip
              icon={<VerifiedIcon fontSize="small" />}
              label={`${resolvedStats.verifiedEmailUsers} verified`}
              variant="outlined"
            />
          </Stack>
        </Stack>

        {flash?.error && <Alert severity="error">{flash.error}</Alert>}
        {flash?.success && <Alert severity="success">{flash.success}</Alert>}

        {broadcastState?.id && (
          <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
            <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
              <Box>
                <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                  Delivery Progress
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  Broadcast ID: {broadcastState.id}
                </Typography>
              </Box>
              <Stack direction="row" spacing={1} alignItems="center">
                <Chip
                  size="small"
                  label={String(broadcastState.status || 'unknown')}
                  color={broadcastState.status === 'completed' ? 'success' : (broadcastState.status === 'running' ? 'info' : 'default')}
                  variant="outlined"
                />
                <Button
                  size="small"
                  variant="text"
                  startIcon={<RefreshIcon />}
                  disabled={broadcastLoading}
                  onClick={refreshProgress}
                >
                  Refresh
                </Button>
              </Stack>
            </Stack>
            <LinearProgress
              variant="determinate"
              value={Number(broadcastState.percent || 0)}
              sx={{ borderRadius: 999, height: 8, mb: 1 }}
            />
            <Stack direction={{ xs: 'column', md: 'row' }} spacing={1} justifyContent="space-between">
              <Typography variant="caption" color="text.secondary">
                {Number(broadcastState.processed || 0)} / {Number(broadcastState.total || 0)} processed ({Number(broadcastState.percent || 0)}%)
              </Typography>
              <Typography variant="caption" color="text.secondary">
                Sent: {Number(broadcastState.sent || 0)} · Failed: {Number(broadcastState.failed || 0)} · Jobs: {Number(broadcastState.jobs_done || 0)}/{Number(broadcastState.jobs_total || 0)}
              </Typography>
            </Stack>
            {broadcastState?.last_user_id && (
              <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 0.5 }}>
                Last user_id: {broadcastState.last_user_id}
              </Typography>
            )}
            <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 1 }}>
              Progress is logged under storage/logs/email_broadcast.log with keys: queued, job_start, progress, job_done.
            </Typography>
          </Paper>
        )}

        {resolvedStats.verifiedEmailUsers === 0 && (
          <Alert severity="warning">
            No verified email users found. Emails will not be sent until users verify their email addresses.
          </Alert>
        )}

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
          <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
            <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
              Message
            </Typography>
            <IconButton size="small">
              <MoreVertIcon />
            </IconButton>
          </Stack>
          <Divider sx={{ mb: 1.5 }} />
          <Stack component="form" spacing={1.5} onSubmit={handleSubmit}>
            <TextField
              size="small"
              label="Subject"
              value={data.title}
              onChange={(e) => setData('title', e.target.value)}
              error={Boolean(errors?.title)}
              helperText={errors?.title}
              fullWidth
            />
            <TextField
              size="small"
              label="Body"
              value={data.body}
              onChange={(e) => setData('body', e.target.value)}
              error={Boolean(errors?.body)}
              helperText={errors?.body}
              fullWidth
              multiline
              minRows={7}
            />
            <Stack direction="row" spacing={1}>
              <Button
                type="submit"
                size="small"
                startIcon={<EmailIcon />}
                variant="contained"
                disabled={processing || resolvedStats.verifiedEmailUsers === 0}
              >
                Send Email
              </Button>
              <Button
                type="button"
                size="small"
                variant="text"
                onClick={() => reset('title', 'body')}
              >
                Clear
              </Button>
            </Stack>
          </Stack>
        </Paper>
      </Stack>

      <Snackbar
        open={openSnackbar}
        autoHideDuration={3000}
        onClose={() => setOpenSnackbar(false)}
        message="Email request submitted"
      />
    </AdminLayout>
  );
}
